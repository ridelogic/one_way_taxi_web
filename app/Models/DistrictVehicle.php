<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A car offered in one district (Admin → District Management → Cars). It points at a Vehicle Master record
 * (vehicle_categories), which supplies the category, default seats, default image and the fare rules used by booking;
 * the district row adds its own car name/model, AC, image, description, order and availability.
 */
class DistrictVehicle extends Model
{
    protected $fillable = ['district_page_id', 'vehicle_category_id', 'name', 'seats', 'is_ac', 'media_id', 'description', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'seats' => 'integer',
            'is_ac' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(DistrictPage::class, 'district_page_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id');
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function displayName(): string
    {
        return $this->name ?: (string) $this->vehicle?->name;
    }

    public function seatCount(): ?int
    {
        return $this->seats ?: $this->vehicle?->passenger_capacity;
    }

    public function image(): ?MediaAsset
    {
        return $this->media ?? $this->vehicle?->media;
    }
}
