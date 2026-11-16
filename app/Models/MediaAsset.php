<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    public const ASPECT_RATIOS = [
        'original' => 'Original',
        '21:9' => '21:9 Cinematic',
        '16:9' => '16:9 Widescreen',
        '3:2' => '3:2 Landscape',
        '4:3' => '4:3 Classic',
        '1:1' => '1:1 Square',
        '3:4' => '3:4 Portrait',
    ];

    public const OBJECT_FITS = ['cover' => 'Cover (fill & crop)', 'contain' => 'Contain (show whole image)'];

    /** Public web directory for each disk (relative to public/). */
    private const DISK_DIRECTORIES = [
        'uploads' => 'uploads',
        'demo' => 'images/demo',
    ];

    public const PLACEHOLDER = 'images/placeholder.svg';

    protected $fillable = [
        'disk', 'path', 'original_name', 'mime_type', 'size_bytes', 'width', 'height',
        'title', 'alt_text', 'credit', 'aspect_ratio', 'object_fit', 'focal_x', 'focal_y', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'focal_x' => 'integer',
            'focal_y' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (MediaAsset $media) {
            if ($media->disk === 'uploads') {
                Storage::disk('uploads')->delete($media->path);
            }
        });
    }

    public function url(): string
    {
        $directory = self::DISK_DIRECTORIES[$this->disk] ?? 'uploads';

        return asset($directory.'/'.ltrim($this->path, '/'));
    }

    public function fileExists(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    /** URL that is guaranteed to resolve: falls back to the placeholder when the file is missing. */
    public function safeUrl(): string
    {
        return $this->fileExists() ? $this->url() : asset(self::PLACEHOLDER);
    }

    public function objectPosition(): string
    {
        return $this->focal_x.'% '.$this->focal_y.'%';
    }

    /** CSS aspect-ratio value ("16 / 9") or null for the original ratio. */
    public function aspectRatioCss(): ?string
    {
        if ($this->aspect_ratio === 'original' || ! str_contains($this->aspect_ratio, ':')) {
            return $this->width && $this->height ? $this->width.' / '.$this->height : null;
        }

        return str_replace(':', ' / ', $this->aspect_ratio);
    }

    public function humanSize(): string
    {
        $bytes = $this->size_bytes;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        return number_format(max($bytes, 0) / 1024, 0).' KB';
    }

    public function altText(?string $fallback = null): string
    {
        return $this->alt_text ?: ($fallback ?? $this->title ?? '');
    }

    /** Count of records that reference this asset (used to prevent deleting images in use). */
    public function usageCount(): int
    {
        return Page::where('hero_media_id', $this->id)->count()
            + PageSection::where('media_id', $this->id)->count()
            + PageSection::query()->get(['content'])->filter(fn ($s) => $this->referencedIn($s->content ?? []))->count()
            + City::where('media_id', $this->id)->count()
            + TaxiRoute::where('media_id', $this->id)->count()
            + VehicleCategory::where('media_id', $this->id)->count()
            + Service::where('media_id', $this->id)->count()
            + HeroSlide::where('media_id', $this->id)->orWhere('foreground_media_id', $this->id)->count()
            + SiteSetting::whereIn('key', ['logo_media_id', 'favicon_media_id', 'default_og_media_id', 'app_qr_media_id'])
                ->where('value', (string) $this->id)->count();
    }

    /** True when a section's JSON content (including repeater items) points at this asset. */
    private function referencedIn(array $content): bool
    {
        foreach ($content as $key => $value) {
            if (is_array($value) && $this->referencedIn($value)) {
                return true;
            }
            if (is_string($key) && (str_ends_with($key, 'media_id') || $key === 'image') && (int) $value === $this->id) {
                return true;
            }
        }

        return false;
    }

    public function toPickerArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->safeUrl(),
            'title' => $this->title ?: $this->original_name,
            'alt' => $this->alt_text,
            'width' => $this->width,
            'height' => $this->height,
            'position' => $this->objectPosition(),
        ];
    }
}
