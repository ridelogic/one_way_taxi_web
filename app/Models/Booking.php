<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\TripType;
use App\Support\Reference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    protected $fillable = [
        'reference', 'trip_type', 'rental_package', 'return_date', 'customer_name', 'customer_phone', 'customer_email', 'pickup_city_id', 'destination_city_id', 'pickup_location', 'drop_location',
        'pickup_address', 'drop_address', 'taxi_route_id', 'vehicle_category_id', 'travel_date', 'travel_time',
        'passengers', 'customer_notes', 'estimated_fare', 'fare_breakdown', 'status', 'admin_notes',
        'contacted_at', 'confirmed_at', 'source', 'ip_address', 'user_agent',
        'pickup_lat', 'pickup_lng', 'drop_lat', 'drop_lng', 'distance_km', 'duration_sec',
        'fleet_model_id', 'fleet_trip_id', 'fleet_synced_at', 'fleet_sync_error',
    ];

    /** Customer data never leaks through accidental serialisation. */
    protected $hidden = ['customer_phone', 'customer_email', 'ip_address', 'user_agent', 'admin_notes'];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'trip_type' => TripType::class,
            'return_date' => 'date',
            'travel_date' => 'date',
            'estimated_fare' => 'decimal:2',
            'fare_breakdown' => 'array',
            'passengers' => 'integer',
            'contacted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'fleet_synced_at' => 'datetime',
            'distance_km' => 'decimal:2',
        ];
    }

    public function pickupCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'pickup_city_id');
    }

    public function destinationCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'destination_city_id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TaxiRoute::class, 'taxi_route_id');
    }

    public function vehicleCategory(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class);
    }

    /** Unique, human-friendly reference such as OWT-261010-7K3QX. */
    public static function generateReference(): string
    {
        return Reference::generate('OWT', static::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('reference', 'like', "%{$term}%")
                ->orWhere('customer_name', 'like', "%{$term}%")
                ->orWhere('customer_phone', 'like', "%{$term}%")
                ->orWhere('customer_email', 'like', "%{$term}%")
                ->orWhere('pickup_address', 'like', "%{$term}%")
                ->orWhere('drop_address', 'like', "%{$term}%");
        });
    }

    public function pickupLabel(): string
    {
        return $this->pickupCity?->name ?? $this->pickup_location ?? '—';
    }

    public function destinationLabel(): string
    {
        return $this->destinationCity?->name ?? $this->drop_location ?? '—';
    }
}
