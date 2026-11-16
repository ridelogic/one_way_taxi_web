<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteFare extends Model
{
    protected $fillable = ['taxi_route_id', 'vehicle_category_id', 'fare'];

    protected function casts(): array
    {
        return ['fare' => 'decimal:2'];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TaxiRoute::class, 'taxi_route_id');
    }

    public function vehicleCategory(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class);
    }
}
