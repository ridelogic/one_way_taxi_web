<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = ['name', 'slug', 'state', 'description', 'media_id', 'is_popular_destination', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_popular_destination' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_id');
    }

    public function departingRoutes(): HasMany
    {
        return $this->hasMany(TaxiRoute::class, 'origin_city_id');
    }

    public function arrivingRoutes(): HasMany
    {
        return $this->hasMany(TaxiRoute::class, 'destination_city_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /** Match free text typed by a customer ("chennai", "Chennai, Tamil Nadu") to an active city. */
    public static function resolve(?string $text): ?self
    {
        $text = trim((string) $text);
        if ($text === '') {
            return null;
        }

        $name = trim(explode(',', $text)[0]);

        return static::active()
            ->where(fn (Builder $q) => $q->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->orWhereRaw('LOWER(slug) = ?', [mb_strtolower($name)]))
            ->first();
    }

    public function label(): string
    {
        return $this->state ? $this->name.', '.$this->state : $this->name;
    }
}
