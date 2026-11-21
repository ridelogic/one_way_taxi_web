<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A YouTube video promotion (Admin → Video Promotions). Videos are embedded from YouTube, never stored here.
 * One record can show on the homepage and on any number of district pages (district_page_promo_video).
 */
class PromoVideo extends Model
{
    /** Active videos shown per placement (homepage, and each district page). */
    public const MAX_ACTIVE_PER_PLACEMENT = 10;

    public const CATEGORIES = [
        'promotional' => 'Taxi service promotion',
        'testimonial' => 'Customer testimonial',
        'driver' => 'Driver introduction',
        'vehicle' => 'Vehicle showcase',
        'travel' => 'Travel experience',
        'brand' => 'Brand video',
    ];

    /** Homepage sections a video can appear in. */
    public const HOME_SECTIONS = [
        'featured' => 'Featured Taxi Videos',
        'promotional' => 'Taxi Promotional Videos',
    ];

    protected $fillable = [
        'title', 'description', 'youtube_url', 'youtube_id', 'embed_status', 'embed_checked_at', 'thumbnail_media_id', 'category',
        'show_on_home', 'home_section', 'show_on_districts', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'embed_checked_at' => 'datetime',
            'show_on_home' => 'boolean',
            'show_on_districts' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function districts(): BelongsToMany
    {
        return $this->belongsToMany(DistrictPage::class);
    }

    public function thumbnail(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'thumbnail_media_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Hides videos YouTube reports as private, deleted or missing (they would only show an error). */
    public function scopePlayable(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('embed_status')->orWhere('embed_status', '!=', 'unavailable'));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('id');
    }

    /** Active homepage videos, optionally for one homepage section (featured / promotional). */
    public function scopeForHome(Builder $query, ?string $section = null): Builder
    {
        return $query->active()->playable()->where('show_on_home', true)
            ->when($section, fn (Builder $q) => $q->where('home_section', $section))
            ->ordered()->limit(self::MAX_ACTIVE_PER_PLACEMENT);
    }

    public function scopeForDistrict(Builder $query, DistrictPage $district): Builder
    {
        return $query->active()->playable()->where('show_on_districts', true)
            ->whereHas('districts', fn (Builder $q) => $q->whereKey($district->id))
            ->ordered()->limit(self::MAX_ACTIVE_PER_PLACEMENT);
    }

    /**
     * The 11-character video ID from any common YouTube link: watch?v=, youtu.be/, /shorts/, /embed/, /live/,
     * m. and music. subdomains and youtube-nocookie.com. Returns null for anything that is not a YouTube video link.
     */
    public static function parseYoutubeId(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }
        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $host = preg_replace('/^(www\.|m\.|music\.)/', '', $host);
        $path = $parts['path'] ?? '';
        $id = null;

        if ($host === 'youtu.be') {
            $id = explode('/', trim($path, '/'))[0] ?? null;
        } elseif (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
            if (preg_match('#^/(?:shorts|embed|live|v)/([^/?]+)#', $path, $m)) {
                $id = $m[1];
            } elseif ($path === '/watch' || $path === '/watch/') {
                parse_str($parts['query'] ?? '', $query);
                $id = is_string($query['v'] ?? null) ? $query['v'] : null;
            }
        }

        return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : null;
    }

    /** False when the owner has disabled embedding: the site then shows a "Watch on YouTube" card instead of a player. */
    public function canEmbed(): bool
    {
        return $this->embed_status !== 'blocked' && $this->embed_status !== 'unavailable';
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Video';
    }

    /** The custom thumbnail when one is chosen, else YouTube's own high-quality thumbnail. */
    public function thumbnailUrl(): string
    {
        return $this->thumbnail?->safeUrl() ?? 'https://i.ytimg.com/vi/'.$this->youtube_id.'/hqdefault.jpg';
    }

    /** Privacy-enhanced embed; autoplay applies only after the visitor clicks a video (the player is created on click). */
    public function embedUrl(bool $autoplay = true): string
    {
        return 'https://www.youtube-nocookie.com/embed/'.$this->youtube_id.'?rel=0&modestbranding=1&playsinline=1'.($autoplay ? '&autoplay=1' : '');
    }

    public function watchUrl(): string
    {
        return 'https://www.youtube.com/watch?v='.$this->youtube_id;
    }
}
