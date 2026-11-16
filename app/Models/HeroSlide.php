<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class HeroSlide extends Model
{
    protected $fillable = [
        'eyebrow', 'heading', 'highlight', 'subheading', 'subtitle', 'media_id', 'foreground_media_id',
        'primary_label', 'primary_url', 'secondary_label', 'secondary_url',
        'overlay_strength', 'text_align', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'overlay_strength' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_id');
    }

    public function foreground(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'foreground_media_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The highlighted heading line as safe HTML: words wrapped in *asterisks* get the accent colour
     * ("*One-Way* Ride"); without asterisks the whole line is accented.
     */
    public function highlightHtml(string $class = 'home-hero__accent'): string
    {
        $text = e((string) $this->highlight);
        $accent = fn (string $words) => '<span class="'.e($class).'">'.$words.'</span>';

        if (! str_contains($text, '*')) {
            return $accent($text);
        }

        return str_replace('*', '', preg_replace_callback('/\*([^*]+)\*/', fn ($m) => $accent($m[1]), $text));
    }

    /** Relative paths ("/book") resolve against the app base URL so the site works in a sub-folder. */
    public static function href(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        return Str::startsWith($url, ['http://', 'https://', 'tel:', 'mailto:', '#']) ? $url : url($url);
    }
}
