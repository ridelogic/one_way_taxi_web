<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A one-way route between two cities. Named TaxiRoute to avoid clashing with Laravel's Route facade.
 */
class TaxiRoute extends Model
{
    protected $fillable = [
        'origin_city_id', 'destination_city_id', 'slug', 'distance_km', 'duration_minutes', 'base_fare',
        'media_id', 'short_description', 'description', 'seo_title', 'seo_description',
        'is_active', 'is_popular', 'is_sample_fare', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:1',
            'base_fare' => 'decimal:2',
            'duration_minutes' => 'integer',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'is_sample_fare' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function origin(): BelongsTo
    {
        return $this->belongsTo(City::class, 'origin_city_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(City::class, 'destination_city_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_id');
    }

    public function fares(): HasMany
    {
        return $this->hasMany(RouteFare::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function name(): string
    {
        return ($this->origin?->name ?? '?').' to '.($this->destination?->name ?? '?');
    }

    public function durationLabel(): string
    {
        $hours = intdiv($this->duration_minutes, 60);
        $minutes = $this->duration_minutes % 60;

        return trim(($hours ? $hours.' hr'.($hours > 1 ? 's' : '') : '').($minutes ? ' '.$minutes.' min' : ''));
    }

    public function distanceLabel(): string
    {
        $km = (float) $this->distance_km;

        return (fmod($km, 1.0) === 0.0 ? number_format($km, 0) : number_format($km, 1)).' km';
    }

    /** Image for the route: its own thumbnail, else the destination city's photo. */
    public function displayMedia(): ?MediaAsset
    {
        return $this->media ?? $this->destination?->media;
    }
}
