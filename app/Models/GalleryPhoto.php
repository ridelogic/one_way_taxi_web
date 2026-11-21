<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A gallery photo. One record can show on the homepage gallery and on any number of district galleries
 * (district_page_gallery_photo), so the same image is never stored twice. The file itself is a Media Library asset.
 */
class GalleryPhoto extends Model
{
    /** Active photos shown per placement (homepage, and each district page). */
    public const MAX_PER_PLACEMENT = 24;

    protected $fillable = ['media_id', 'title', 'description', 'show_on_home', 'show_on_districts', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'show_on_home' => 'boolean',
            'show_on_districts' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_id');
    }

    public function districts(): BelongsToMany
    {
        return $this->belongsToMany(DistrictPage::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('id');
    }

    public function scopeForHome(Builder $query): Builder
    {
        return $query->active()->where('show_on_home', true)->whereHas('media')->ordered()->limit(self::MAX_PER_PLACEMENT);
    }

    public function scopeForDistrict(Builder $query, DistrictPage $district): Builder
    {
        return $query->active()->where('show_on_districts', true)->whereHas('media')
            ->whereHas('districts', fn (Builder $q) => $q->whereKey($district->id))
            ->ordered()->limit(self::MAX_PER_PLACEMENT);
    }

    public function altText(): string
    {
        return $this->title ?: ($this->media?->altText() ?: 'OneWayTrip Taxi photo');
    }
}
