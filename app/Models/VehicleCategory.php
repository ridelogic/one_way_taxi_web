<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VehicleCategory extends Model
{
    protected $fillable = [
        'name', 'slug', 'category', 'example_models', 'media_id', 'passenger_capacity', 'luggage_capacity',
        'short_description', 'description', 'features', 'sort_order', 'is_active', 'seo_title', 'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'is_active' => 'boolean',
            'passenger_capacity' => 'integer',
            'luggage_capacity' => 'integer',
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

    public function fareRule(): HasOne
    {
        return $this->hasOne(FareRule::class);
    }

    public function routeFares(): HasMany
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
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
