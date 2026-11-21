<?php

namespace App\Services\Fleet;

/**
 * Zone rules, ported from the fleet admin's Commonfunction (modules_new/commonfunction/classes/kohana/commonfunction.php):
 * resolve_deepest_zone_for_point, is_operating_zone, is_zone_to_zone_route, get_zone_outstation_tier,
 * get_zone_rental_packages, pick_distance_tier, calculate_operating_zone_fare and zone_snapshot.
 * Keep the behaviour identical to the admin so the website and dispatcher quote the same fare.
 */
class ZoneResolver
{
    private const ZONE_COLLECTION = 'zone_taxidetails';

    private const ZONE_MAX_LEVEL = 3;

    /** @var array<string, array|null> */
    private array $zoneCache = [];

    /** @var array<int, array> */
    private array $fareCache = [];

    public function __construct(private readonly FleetMongo $mongo, private readonly FleetSettings $settings) {}

    /** Most specific active zone containing the point (highest nesting level, then smallest area). */
    public function deepestZone(float $lat, float $lng): ?array
    {
        $key = $lat.','.$lng;
        if (array_key_exists($key, $this->zoneCache)) {
            return $this->zoneCache[$key];
        }

        $candidates = $this->mongo->find(self::ZONE_COLLECTION, [
            'status' => 'A',
            'loc' => ['$geoIntersects' => ['$geometry' => ['type' => 'Point', 'coordinates' => [$lng, $lat]]]],
        ], ['projection' => ['_id' => 1, 'geofence_name' => 1, 'loc' => 1, 'parent_zone_id' => 1]]);

        $best = null;
        $bestLevel = -1;
        $bestArea = null;
        foreach ($candidates as $candidate) {
            $level = $this->zoneLevel((int) $candidate['_id']);
            $area = $this->polygonArea($candidate['loc']['coordinates'][0] ?? []);
            if ($level > $bestLevel || ($level === $bestLevel && ($bestArea === null || $area < $bestArea))) {
                [$best, $bestLevel, $bestArea] = [$candidate, $level, $area];
            }
        }

        return $this->zoneCache[$key] = $best;
    }

    /**
     * Port of Commonfunction::is_operating_zone().
     *
     * @param  int|null  $tripType  1/null Local, 2 Rental, 3 Outstation One Way, 4 Outstation Round Trip
     * @return array{zone_found: bool, zone_id: ?int, zone_name: ?string, drop_in_zone: ?bool, model_active: bool, available: bool, fare: ?array, message: string}
     */
    public function operatingZone(?float $pickupLat, ?float $pickupLng, ?float $dropLat, ?float $dropLng, int $modelId, ?int $tripType = null): array
    {
        $result = [
            'zone_found' => false, 'zone_id' => null, 'zone_name' => null, 'drop_in_zone' => null,
            'model_active' => false, 'available' => false, 'fare' => null,
            'message' => 'No service available in this area right now.',
        ];

        if (! $pickupLat || ! $pickupLng) {
            return $result;
        }

        $zone = $this->deepestZone($pickupLat, $pickupLng);
        if (! $zone) {
            return $result;
        }

        // A zone that is turned off or has no fare hands over to its parent zone (Region → City → State).
        $effective = $zone;
        $candidate = $zone;
        for ($depth = 0; $candidate && $depth < 5; $depth++) {
            $candidateId = (int) $candidate['_id'];
            if ($this->zoneOperatingStatus($candidateId) === 'A' && count($this->zoneFare($candidateId)) > 0) {
                $effective = $candidate;
                break;
            }
            $parentId = (int) ($candidate['parent_zone_id'] ?? 0);
            $candidate = $parentId ? $this->mongo->findOne(self::ZONE_COLLECTION, ['_id' => $parentId, 'status' => 'A'], ['projection' => ['_id' => 1, 'geofence_name' => 1, 'parent_zone_id' => 1]]) : null;
        }
        $zone = $effective;

        $result['zone_found'] = true;
        $result['zone_id'] = (int) $zone['_id'];
        $result['zone_name'] = $zone['geofence_name'] ?? '';

        if ($dropLat && $dropLng) {
            $result['drop_in_zone'] = $this->mongo->count(self::ZONE_COLLECTION, [
                'status' => 'A',
                '_id' => $result['zone_id'],
                'loc' => ['$geoIntersects' => ['$geometry' => ['type' => 'Point', 'coordinates' => [$dropLng, $dropLat]]]],
            ]) > 0;
        }

        if ($this->zoneOperatingStatus($result['zone_id']) !== 'A') {
            $result['message'] = 'Operating Zone is currently turned off for this area.';

            return $result;
        }

        $isLocal = ! in_array((int) $tripType, [2, 3, 4], true);
        if ($this->settings->localZoneMatch() && $isLocal && $dropLat && $dropLng && ! $result['drop_in_zone']) {
            $result['message'] = 'Your Travel Distance is above Local Km. Kindly choose Outstation Trip';

            return $result;
        }

        if (! $modelId) {
            $result['available'] = true;
            $result['message'] = '';

            return $result;
        }

        $row = $this->zoneFare($result['zone_id'])[$modelId] ?? null;
        if (! $row) {
            $result['message'] = 'This vehicle type is not available in this zone right now.';

            return $result;
        }

        $ok = match ((int) $tripType) {
            2 => ($row['rental_status'] ?? '') === 'A',
            3 => ($row['outstation_oneway_status'] ?? '') === 'A',
            4 => ($row['outstation_roundtrip_status'] ?? '') === 'A',
            default => ($row['status'] ?? '') === 'A',
        };

        if ($ok) {
            $result['model_active'] = true;
            $result['available'] = true;
            $result['fare'] = $row;
            $result['message'] = '';
        } else {
            $result['message'] = match ((int) $tripType) {
                2 => 'Rental service is not available in this zone right now.',
                3, 4 => 'Outstation service is not available in this zone right now.',
                default => 'This vehicle type is not available in this zone right now.',
            };
        }

        return $result;
    }

    /**
     * Port of Commonfunction::is_zone_to_zone_route().
     *
     * @param  string  $osType  '1' one way, '2' round trip
     * @return array{route_found: bool, route_id: ?int, model_active: bool}
     */
    public function zoneToZoneRoute(float $pickupLat, float $pickupLng, float $dropLat, float $dropLng, int $modelId, string $osType = '1'): array
    {
        $result = ['route_found' => false, 'route_id' => null, 'model_active' => false];
        if (! $pickupLat || ! $pickupLng || ! $dropLat || ! $dropLng) {
            return $result;
        }

        $pickupZone = $this->deepestZone($pickupLat, $pickupLng);
        $dropZone = $pickupZone ? $this->deepestZone($dropLat, $dropLng) : null;
        if (! $pickupZone || ! $dropZone) {
            return $result;
        }

        $route = $this->mongo->findOne('zone_to_zone_route', [
            'origin_zone_id' => (int) $pickupZone['_id'],
            'destination_zone_id' => (int) $dropZone['_id'],
            'status' => ['$ne' => 'D'],
        ]);
        if (! $route) {
            return $result;
        }

        $result['route_found'] = true;
        $result['route_id'] = (int) $route['_id'];

        $statusRow = $route['model_status'][$modelId] ?? $route['model_status'][(string) $modelId] ?? null;
        if (is_scalar($statusRow)) {
            $result['model_active'] = (string) $statusRow === 'A'; // legacy flat value: both directions
        } else {
            $result['model_active'] = is_array($statusRow) && ($statusRow[$osType === '2' ? 'roundtrip' : 'oneway'] ?? '') === 'A';
        }

        return $result;
    }

    /** @return array<int, array> active Zone to Zone fare tier rows for a route + model + direction */
    public function zoneToZoneRows(int $routeId, int $modelId, string $osType): array
    {
        return $this->mongo->find('zone_to_zone_fare', [
            'route_id' => $routeId,
            'taxi_modelid' => $modelId,
            'os_type' => $osType,
            'plan_status' => 'A',
        ]);
    }

    /**
     * Port of Commonfunction::get_zone_outstation_tier(): Zone to Zone route tier, else the pickup zone's own
     * flat Outstation tier, else null. Gated by the pickup zone's Outstation toggle.
     */
    public function zoneOutstationTier(float $pickupLat, float $pickupLng, ?float $dropLat, ?float $dropLng, int $modelId, string $osType, float $tripDistance): ?array
    {
        $flat = $this->operatingZone($pickupLat, $pickupLng, $dropLat, $dropLng, $modelId, $osType === '2' ? 4 : 3);
        if (! $flat['available']) {
            return null;
        }

        if ($dropLat && $dropLng) {
            $zz = $this->zoneToZoneRoute($pickupLat, $pickupLng, $dropLat, $dropLng, $modelId, $osType);
            if ($zz['route_found'] && $zz['model_active']) {
                $selected = $this->pickDistanceTier($this->zoneToZoneRows($zz['route_id'], $modelId, $osType), $tripDistance);
                if ($selected !== null) {
                    return $selected;
                }
            }
        }

        if (! empty($flat['fare'])) {
            $rows = $flat['fare'][$osType === '2' ? 'outstation_roundtrip' : 'outstation_oneway'] ?? [];

            return $this->pickDistanceTier($rows, $tripDistance);
        }

        return null;
    }

    /**
     * Port of Commonfunction::get_zone_rental_packages(). Packages are shaped like package_plans rows
     * (plan_duration in minutes); the key is the row's permanent _id (or its position for old rows).
     *
     * @return array{zone_found: bool, zone_name: string, packages: array<int, array>}
     */
    public function zoneRentalPackages(float $pickupLat, float $pickupLng, int $modelId): array
    {
        $op = $this->operatingZone($pickupLat, $pickupLng, null, null, $modelId, 2);
        $result = ['zone_found' => $op['zone_found'], 'zone_name' => (string) ($op['zone_name'] ?? ''), 'packages' => []];
        if (! $op['available'] || empty($op['fare']['rental'])) {
            return $result;
        }

        foreach ($op['fare']['rental'] as $idx => $row) {
            $duration = isset($row['plan_duration']) ? (float) $row['plan_duration'] : (float) ($row['plan_duration_hour'] ?? 0) * 60;
            $id = ! empty($row['_id']) ? (int) $row['_id'] : (int) $idx;
            $result['packages'][$id] = [
                '_id' => $id,
                'plan_duration' => $duration,
                'distance' => (float) ($row['distance'] ?? 0),
                'base_fare' => (float) ($row['base_fare'] ?? 0),
                'additional_fare_per_distance' => (float) ($row['additional_fare_per_distance'] ?? 0),
                'additional_fare_per_hour' => (float) ($row['additional_fare_per_hour'] ?? 0),
                'driver_beta' => (float) ($row['driver_beta'] ?? 0),
                'notes' => '',
                'distance_unit' => $this->settings->unitName(),
                'zone_id' => $op['zone_id'],
            ];
        }

        return $result;
    }

    /** Smallest tier whose distance covers the trip, or the largest tier when the trip exceeds them all. */
    public function pickDistanceTier(iterable $rows, float $tripDistance): ?array
    {
        $rows = is_array($rows) ? array_values($rows) : iterator_to_array($rows, false);
        if (! $rows) {
            return null;
        }

        $selected = null;
        foreach ($rows as $row) {
            $d = (float) ($row['distance'] ?? 0);
            if ($d >= $tripDistance && ($selected === null || $d < (float) $selected['distance'])) {
                $selected = $row;
            }
        }

        if ($selected === null) {
            usort($rows, fn ($a, $b) => (float) $b['distance'] <=> (float) $a['distance']);
            $selected = $rows[0];
        }

        return $selected;
    }

    /** base fare + extra distance beyond the tier + driver beta (Commonfunction::apply_distance_tier_overage). */
    public function distanceTierFare(array $tier, float $tripDistance): float
    {
        $total = (float) ($tier['base_fare'] ?? 0);
        $planDistance = (float) ($tier['distance'] ?? 0);
        if ($tripDistance > $planDistance) {
            $total += ($tripDistance - $planDistance) * (float) ($tier['additional_fare_per_distance'] ?? 0);
        }

        return round($total + (float) ($tier['driver_beta'] ?? 0), 2);
    }

    /**
     * Port of Commonfunction::calculate_operating_zone_fare(): min fare for the first min km, per-km beyond it,
     * per-minute, then the peak-hour percentage surcharges (checked against the pickup time).
     *
     * @return array{total: float, min_fare: float, extra_km: float, distance_fare: float, minutes_fare: float, peak_percent: float}
     */
    public function operatingZoneFare(array $row, float $distanceKm, float $durationMin, \DateTimeInterface $at): array
    {
        $minKm = (float) ($row['min_km'] ?? 0);
        $minFare = (float) ($row['min_fare'] ?? 0);
        $extraKm = $distanceKm > $minKm ? $distanceKm - $minKm : 0;
        $distanceFare = $extraKm * (float) ($row['additional_fare_per_km'] ?? 0);
        $minutesFare = $durationMin * (float) ($row['fare_per_minute'] ?? 0);

        $total = $minFare + $distanceFare + $minutesFare;

        $now = $at->format('H:i');
        $peakPercent = 0.0;
        foreach (['peak1', 'peak2'] as $peak) {
            $from = (string) ($row[$peak.'_from'] ?? '');
            $to = (string) ($row[$peak.'_to'] ?? '');
            $percent = (float) ($row[$peak.'_percent'] ?? 0);
            if ($from !== '' && $to !== '' && $percent > 0 && $this->timeInRange($now, $from, $to)) {
                $total += $total * ($percent / 100);
                $peakPercent += $percent;
            }
        }

        return [
            'total' => round($total, 2),
            'min_fare' => $minFare,
            'extra_km' => $extraKm,
            'distance_fare' => round($distanceFare, 2),
            'minutes_fare' => round($minutesFare, 2),
            'peak_percent' => $peakPercent,
        ];
    }

    /** Port of Commonfunction::zone_snapshot() — informational, saved on the booking as booking_zone. */
    public function snapshot(float $pickupLat, float $pickupLng, ?float $dropLat, ?float $dropLng): array
    {
        $snap = [
            'pickup_zone_id' => null, 'pickup_zone_name' => '', 'pickup_in_zone' => false,
            'drop_zone_id' => null, 'drop_zone_name' => '', 'drop_in_zone' => null,
            'same_zone' => false, 'route_found' => false, 'route_id' => null,
        ];

        try {
            if ($pickupLat && $pickupLng && ($z = $this->deepestZone($pickupLat, $pickupLng))) {
                $snap['pickup_zone_id'] = (int) $z['_id'];
                $snap['pickup_zone_name'] = $z['geofence_name'] ?? '';
                $snap['pickup_in_zone'] = true;
            }
            if ($dropLat && $dropLng) {
                $snap['drop_in_zone'] = false;
                if ($z = $this->deepestZone($dropLat, $dropLng)) {
                    $snap['drop_zone_id'] = (int) $z['_id'];
                    $snap['drop_zone_name'] = $z['geofence_name'] ?? '';
                    $snap['drop_in_zone'] = true;
                }
            }
            $snap['same_zone'] = $snap['pickup_zone_id'] !== null && $snap['pickup_zone_id'] === $snap['drop_zone_id'];
            if ($snap['pickup_zone_id'] !== null && $snap['drop_zone_id'] !== null && ! $snap['same_zone']) {
                $route = $this->mongo->findOne('zone_to_zone_route', [
                    'origin_zone_id' => $snap['pickup_zone_id'],
                    'destination_zone_id' => $snap['drop_zone_id'],
                    'status' => ['$ne' => 'D'],
                ], ['projection' => ['_id' => 1]]);
                if ($route) {
                    $snap['route_found'] = true;
                    $snap['route_id'] = (int) $route['_id'];
                }
            }
        } catch (\Throwable) {
            // informational only — never block a booking on it
        }

        return $snap;
    }

    /** @return array<int, array> fare rows of an Operating Zone, keyed by model id */
    private function zoneFare(int $zoneId): array
    {
        if (isset($this->fareCache[$zoneId])) {
            return $this->fareCache[$zoneId];
        }

        $byModel = [];
        foreach ($this->mongo->find('operating_zone_fare', ['zone_id' => $zoneId]) as $doc) {
            foreach ($doc['models'] ?? [] as $m) {
                if (isset($m['model_id'])) {
                    $byModel[(int) $m['model_id']] = $m;
                }
            }
        }

        return $this->fareCache[$zoneId] = $byModel;
    }

    private function zoneOperatingStatus(int $zoneId): string
    {
        $doc = $this->mongo->findOne('operating_zone_fare', ['zone_id' => $zoneId], ['projection' => ['zone_status' => 1]]);

        return ($doc['zone_status'] ?? 'A') === 'I' ? 'I' : 'A';
    }

    private function zoneLevel(int $zoneId): int
    {
        $level = 1;
        $current = $zoneId;
        for ($i = 0; $i < self::ZONE_MAX_LEVEL; $i++) {
            $zone = $this->mongo->findOne(self::ZONE_COLLECTION, ['_id' => $current], ['projection' => ['parent_zone_id' => 1]]);
            if (! $zone || empty($zone['parent_zone_id'])) {
                break;
            }
            $level++;
            $current = (int) $zone['parent_zone_id'];
        }

        return $level;
    }

    private function polygonArea(array $ring): float
    {
        $n = count($ring);
        if ($n < 3) {
            return 0.0;
        }
        $area = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $j = ($i + 1) % $n;
            $area += (float) $ring[$i][0] * (float) $ring[$j][1] - (float) $ring[$j][0] * (float) $ring[$i][1];
        }

        return abs($area / 2);
    }

    private function timeInRange(string $now, string $from, string $to): bool
    {
        return $from <= $to ? ($now >= $from && $now <= $to) : ($now >= $from || $now <= $to);
    }
}
