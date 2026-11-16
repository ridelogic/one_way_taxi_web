<?php

namespace App\Models;

use App\Enums\EnquiryStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PartnerEnquiry extends Model
{
    public const PARTNER_TYPES = [
        'driver' => 'Driver (I need a vehicle)',
        'owner_driver' => 'Owner-driver (I have my own vehicle)',
        'fleet_owner' => 'Fleet owner / operator',
        'travel_agent' => 'Travel agent / referral partner',
    ];

    public const VEHICLE_TYPES = [
        'hatchback' => 'Hatchback (Mini)',
        'sedan' => 'Sedan',
        'suv' => 'SUV',
        'muv' => 'MUV / Innova',
        'tempo_traveller' => 'Tempo Traveller',
        'auto' => 'Auto rickshaw',
        'bike' => 'Motorcycle / scooter',
        'none' => 'No vehicle',
    ];

    protected $fillable = [
        'reference', 'name', 'phone', 'email', 'city', 'partner_type', 'vehicle_type', 'vehicle_count',
        'experience_years', 'has_commercial_licence', 'message', 'status', 'admin_notes', 'ip_address',
    ];

    protected $hidden = ['phone', 'email', 'ip_address', 'admin_notes'];

    protected function casts(): array
    {
        return [
            'status' => EnquiryStatus::class,
            'has_commercial_licence' => 'boolean',
            'vehicle_count' => 'integer',
            'experience_years' => 'integer',
        ];
    }

    public function partnerTypeLabel(): string
    {
        return self::PARTNER_TYPES[$this->partner_type] ?? $this->partner_type;
    }

    public function vehicleTypeLabel(): string
    {
        return self::VEHICLE_TYPES[$this->vehicle_type] ?? ($this->vehicle_type ?: '—');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $term === '' ? $query : $query->where(fn (Builder $q) => $q
            ->where('reference', 'like', "%{$term}%")
            ->orWhere('name', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->orWhere('city', 'like', "%{$term}%"));
    }
}
