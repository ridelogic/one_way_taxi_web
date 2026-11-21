<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A popular route on a district page (Admin → District Management → Routes). Fares are never typed here: when the
 * route is linked to an existing priced route (Admin → Routes), the page shows the fare estimate from that data.
 */
class DistrictRoute extends Model
{
    protected $fillable = ['district_page_id', 'from_location', 'to_location', 'description', 'taxi_route_id', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(DistrictPage::class, 'district_page_id');
    }

    public function taxiRoute(): BelongsTo
    {
        return $this->belongsTo(TaxiRoute::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
