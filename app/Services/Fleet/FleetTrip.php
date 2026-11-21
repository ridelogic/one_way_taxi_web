<?php

namespace App\Services\Fleet;

use Carbon\CarbonImmutable;

/**
 * One trip to price, in the fleet backend's terms.
 *
 * tripType:   1 Local, 2 Rental, 3 Outstation
 * osTripType: 1 one way, 2 round trip (Outstation only)
 */
final class FleetTrip
{
    public function __construct(
        public readonly int $tripType,
        public readonly int $modelId,
        public readonly float $pickupLat,
        public readonly float $pickupLng,
        public readonly ?float $dropLat,
        public readonly ?float $dropLng,
        public readonly float $distanceKm,      // one-way road distance, pickup → drop
        public readonly int $durationSec,        // one-way road time, pickup → drop
        public readonly CarbonImmutable $pickupAt,
        public readonly int $osTripType = 1,
        public readonly int $days = 1,
        public readonly ?int $packageId = null,  // Rental package (package_plans _id, or the zone rental package id)
    ) {}

    public function isRoundTrip(): bool
    {
        return $this->tripType === 3 && $this->osTripType === 2;
    }
}
