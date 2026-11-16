<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    public const STATUSES = ['draft' => 'Draft', 'published' => 'Published'];

    public const TEMPLATES = [
        'default' => 'Standard content page',
        'about' => 'About page (adds highlights & stats)',
        'legal' => 'Legal / policy page',
    ];

    /** Slugs that are served by dedicated controllers and cannot be used for CMS pages. */
    public const RESERVED_SLUGS = [
        'admin', 'routes', 'fleet', 'services', 'faqs', 'contact', 'book', 'booking', 'fare-estimate',
        'sitemap.xml', 'robots.txt', 'uploads', 'images', 'css', 'js', 'up', 'login', 'logout', 'image-credits',
        'corporate', 'become-a-partner', 'download-app', 'cabs', 'build',
    ];

    protected $fillable = [
        'title', 'slug', 'template', 'heading', 'subheading', 'body', 'hero_media_id', 'status',
        'show_in_nav', 'nav_order', 'is_system', 'seo_title', 'seo_description', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'show_in_nav' => 'boolean',
            'is_system' => 'boolean',
            'nav_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function heroMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'hero_media_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function url(): string
    {
        return $this->slug === 'home' ? route('home') : route('pages.show', $this->slug);
    }
}
