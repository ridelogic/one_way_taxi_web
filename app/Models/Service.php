<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    protected $fillable = [
        'title', 'slug', 'icon', 'short_description', 'body', 'highlights', 'media_id',
        'show_on_home', 'is_active', 'sort_order', 'seo_title', 'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'highlights' => 'array',
            'show_on_home' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_id');
    }

    /**
     * Services withdrawn from the public website: never listed (Services page, homepage, menus, sitemap) and their
     * pages redirect to /services. Records, admin screens and booking functionality are kept.
     */
    public const WITHDRAWN = ['bike-taxi', 'auto-taxi'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Active services shown on the public website (excludes WITHDRAWN). */
    public function scopePublicListing(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNotIn('slug', self::WITHDRAWN);
    }

    public function isWithdrawn(): bool
    {
        return in_array($this->slug, self::WITHDRAWN, true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }
}
