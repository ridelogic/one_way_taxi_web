<?php

namespace App\Services\Fleet;

use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Fare calculation with the same rules as the fleet admin's "Add Booking" screen
 * (uat.onewaytriptaxi.com/add/booking — application/views/admin/taxi_dispatch/booking.php):
 *
 *  Local      → tdispatch/get_citymodel_fare_details_new (Controller_FleeteraTdispatch, trip type 1)
 *  Rental     → package list (Commonfunction::package_plan_options) + calculatetotalfare_package()
 *  Outstation → users/get_outdynamic_package_detail + calculatetotalfare_package()
 *
 * calculatetotalfare_package() runs in the admin's browser; it is ported here so the website never trusts
 * a fare sent by a visitor. When zone fares are on (siteinfo zonefare_available_status = 1), Operating Zone
 * and Zone to Zone fares win over the common plans, exactly as in the admin.
 */
class FleetFareService
{
    public const LOCAL = 1;

    public const RENTAL = 2;

    public const OUTSTATION = 3;

    public function __construct(
        private readonly FleetMongo $mongo,
        private readonly FleetSettings $settings,
        private readonly ZoneResolver $zones,
    ) {}

    public function available(): bool
    {
        return $this->mongo->available();
    }

    /**
     * Active vehicle models enabled for a trip type (Model_FleeteraFind::get_all_model_details).
     *
     * @return array<int, array{model_id: int, model_name: string, model_size: int}>
     */
    public function models(int $tripType): array
    {
        return Cache::remember('fleet.models.'.$tripType, config('fleet.cache_seconds', 300), function () use ($tripType) {
            $where = ['model_status' => 'A'];
            $flag = [self::LOCAL => 'enable_local', self::RENTAL => 'enable_rental', self::OUTSTATION => 'enable_outstation'][$tripType] ?? null;

            $rows = $this->mongo->find('motor_model', $where, ['projection' => ['model_name' => 1, 'model_size' => 1, 'enable_local' => 1, 'enable_rental' => 1, 'enable_outstation' => 1], 'sort' => ['_id' => 1]]);

            $models = [];
            foreach ($rows as $row) {
                // A missing enable_* flag means enabled ($ifNull … 1 in the admin query).
                if ($flag && (int) ($row[$flag] ?? 1) !== 1) {
                    continue;
                }
                $models[] = ['model_id' => (int) $row['_id'], 'model_name' => (string) ($row['model_name'] ?? ''), 'model_size' => (int) ($row['model_size'] ?? 0)];
            }

            return $models;
        });
    }

    public function modelName(int $modelId): string
    {
        foreach ($this->models(0) as $m) {
            if ($m['model_id'] === $modelId) {
                return $m['model_name'];
            }
        }

        return 'Model #'.$modelId;
    }

    /**
     * Rental / outstation package list for a model (Commonfunction::package_plan_options). Rental with a pickup inside
     * an Operating Zone lists that zone's packages (no common-plan fallback); otherwise the common package_plans.
     *
     * @return array{source: string, zone_name: string, packages: array<int, array>}
     */
    public function packages(int $tripType, int $modelId, ?float $pickupLat = null, ?float $pickupLng = null): array
    {
        if ($tripType === self::RENTAL && $pickupLat && $pickupLng && $this->settings->zoneFareEnabled()) {
            $zone = $this->zones->zoneRentalPackages($pickupLat, $pickupLng, $modelId);
            if ($zone['zone_found']) {
                return ['source' => 'zone', 'zone_name' => $zone['zone_name'], 'packages' => array_values(array_map(fn ($p) => $this->packageRow($p), $zone['packages']))];
            }
        }

        $type = $tripType === self::RENTAL ? '1' : '2';
        $plans = $this->mongo->find('package_plans', ['plan_status' => 'A', 'package_type' => $type, 'taxi_modelid' => $modelId]);

        return ['source' => 'common', 'zone_name' => '', 'packages' => array_map(fn ($p) => $this->packageRow($p), $plans)];
    }

    /**
     * Price a trip.
     *
     * @return array<string, mixed> available, message, lines, total, … plus 'fleet' with the values the booking insert needs
     */
    public function estimate(FleetTrip $trip): array
    {
        return match ($trip->tripType) {
            self::LOCAL => $this->local($trip),
            self::RENTAL => $this->rental($trip),
            default => $this->outstation($trip),
        };
    }

    /* ------------------------------------------------------------------ Local */

    /** Port of Controller_FleeteraTdispatch::action_get_citymodel_fare_details_new(), trip type 1. */
    private function local(FleetTrip $trip): array
    {
        $minOutstation = $this->settings->minOutstationKm();
        if ($minOutstation > 0 && $trip->distanceKm > $minOutstation) {
            return $this->unavailable($trip, 'Your travel distance is above the Local km limit — please choose One Way or Round Trip.');
        }

        $zoneFare = $this->settings->zoneFareEnabled();
        $op = ['available' => true, 'fare' => null];
        if ($zoneFare) {
            $op = $this->zones->operatingZone($trip->pickupLat, $trip->pickupLng, $trip->dropLat, $trip->dropLng, $trip->modelId);
            if (! $op['available']) {
                return $this->unavailable($trip, $op['message']);
            }
        }

        $distance = $trip->distanceKm;
        $minutes = (int) round($trip->durationSec / 60);
        $surchargeAt = $this->surchargeTime($trip);
        $lines = [];
        $split = ['nightfare_applicable' => 0, 'nightfare' => 0, 'eveningfare_applicable' => 0, 'eveningfare' => 0, 'minutes_fare' => 0, 'base_fare' => 0, 'model_fare_type' => 0];

        if ($zoneFare && ! empty($op['fare'])) {
            // Operating Zone fare wins over the common model fare (and the legacy zone return fare).
            $zf = $this->zones->operatingZoneFare($op['fare'], $distance, $minutes, $surchargeAt);
            $total = $zf['total'];
            $lines[] = ['label' => 'Minimum fare (first '.$this->num($op['fare']['min_km'] ?? 0).' km)', 'amount' => $zf['min_fare']];
            if ($zf['distance_fare'] > 0) {
                $lines[] = ['label' => $this->num($zf['extra_km']).' km × '.Money::format($op['fare']['additional_fare_per_km'] ?? 0).'/km', 'amount' => $zf['distance_fare']];
            }
            if ($zf['minutes_fare'] > 0) {
                $lines[] = ['label' => $minutes.' min × '.Money::format($op['fare']['fare_per_minute'] ?? 0).'/min', 'amount' => $zf['minutes_fare']];
            }
            if ($zf['peak_percent'] > 0) {
                $lines[] = ['label' => 'Peak hour surcharge ('.$this->num($zf['peak_percent']).'%)', 'amount' => round($total - $zf['min_fare'] - $zf['distance_fare'] - $zf['minutes_fare'], 2)];
            }
            $split['minutes_fare'] = $zf['minutes_fare'];
            $basis = 'operating_zone';
            $fareInfo = $op['fare'];
        } else {
            $f = $this->commonModelFare($trip->modelId, $trip->pickupLat, $trip->pickupLng);
            if (! $f) {
                return $this->unavailable($trip, 'Fares for this vehicle type are not configured yet. Submit your request and we will call you with a quote.');
            }
            [$total, $lines, $split] = $this->commonLocalFare($f, $distance, $trip->durationSec, $surchargeAt);
            $basis = 'model_fare';
            $fareInfo = $f;
        }

        return $this->result($trip, $total, $lines, $basis, $split + ['trip_fare' => round($total, 2)], [
            'fare_info' => $fareInfo,
        ]);
    }

    /**
     * Common per-model fare (Model_FleeteraTdispatch::get_citymodel_fare_details_new): the motor_model fare,
     * raised by the pickup city's city_model_fare percentage (city_geofencing polygon).
     */
    public function commonModelFare(int $modelId, float $pickupLat, float $pickupLng): ?array
    {
        $city = $this->mongo->findOne('city_geofencing', [
            'polygons' => ['$geoIntersects' => ['$geometry' => ['type' => 'Point', 'coordinates' => [$pickupLng, $pickupLat]]]],
        ], ['projection' => ['_id' => 1, 'city_model_fare' => 1]]);
        $pct = (float) ($city['city_model_fare'] ?? 0) / 100;

        $m = $this->mongo->findOne('motor_model', ['_id' => $modelId]);
        if (! $m) {
            return null;
        }

        $up = fn ($key) => (float) ($m[$key] ?? 0) * (1 + $pct);
        $raw = fn ($key, $default = 0) => $m[$key] ?? $default;

        return [
            'base_fare' => $up('base_fare'), 'min_fare' => $up('min_fare'), 'below_km' => $up('below_km'), 'above_km' => $up('above_km'),
            'minutes_fare' => $up('minutes_fare'), 'night_fare' => $up('night_fare'), 'evening_fare' => $up('evening_fare'),
            'additional_fare_per_km' => $up('additional_fare_per_km'),
            'night_charge' => $raw('night_charge'), 'night_timing_from' => $raw('night_timing_from', ''), 'night_timing_to' => $raw('night_timing_to', ''),
            'evening_charge' => $raw('evening_charge'), 'evening_timing_from' => $raw('evening_timing_from', ''), 'evening_timing_to' => $raw('evening_timing_to', ''),
            'cancellation_fare' => $raw('cancellation_fare'), 'waiting_time' => $raw('waiting_time'), 'min_km' => (float) $raw('min_km'),
            'below_above_km' => $raw('below_above_km'), 'km_wise_fare' => (int) $raw('km_wise_fare', 2),
            'model_fare_stage1_from' => (float) $raw('model_fare_stage1_from'), 'model_fare_stage1_to' => (float) $raw('model_fare_stage1_to'), 'model_fare_stage1_fare' => (float) $raw('model_fare_stage1_fare'),
            'model_fare_stage2_from' => (float) $raw('model_fare_stage2_from'), 'model_fare_stage2_to' => (float) $raw('model_fare_stage2_to'), 'model_fare_stage2_fare' => (float) $raw('model_fare_stage2_fare'),
            'model_fare_stage3_from' => (float) $raw('model_fare_stage3_from'), 'model_fare_stage3_to' => (float) $raw('model_fare_stage3_to'), 'model_fare_stage3_fare' => (float) $raw('model_fare_stage3_fare'),
            'city_model_fare' => $pct * 100, 'model_id' => $modelId, 'model_name' => $m['model_name'] ?? '', 'model_size' => $m['model_size'] ?? 0,
        ];
    }

    /**
     * The common-fare branch of get_citymodel_fare_details_new (FARE_CALCULATION_TYPE 1 or 3 without a legacy zone
     * row), then minutes fare (type 2/3), night and evening percentages. Ported line by line — including the admin's
     * own stage arithmetic — so the website quotes exactly what the dispatcher sees.
     */
    private function commonLocalFare(array $f, float $distance, int $durationSec, \DateTimeInterface $at): array
    {
        $type = $this->settings->fareCalculationType();
        $minKm = $f['min_km'];
        $minFare = $f['min_fare'];
        $baseFare = $f['base_fare'];
        $total = $baseFare;
        $lines = [];

        if ($type === 1 || $type === 3) {
            if ($f['km_wise_fare'] === 1 && $distance > $minKm) {
                $extra = ($distance - $minKm) * $f['additional_fare_per_km'];
                $total = $minFare + $extra + $baseFare;
                $lines[] = ['label' => 'Minimum fare (first '.$this->num($minKm).' km)', 'amount' => $minFare];
                if ($baseFare > 0) {
                    $lines[] = ['label' => 'Base fare', 'amount' => $baseFare];
                }
                $lines[] = ['label' => $this->num($distance - $minKm).' km × '.Money::format($f['additional_fare_per_km']).'/km', 'amount' => round($extra, 2)];
            } else {
                $total = $minFare;
                $calKm = 0;
                $stageTotal = 0;
                if ($f['model_fare_stage1_from'] <= $distance) {
                    $cap = $f['model_fare_stage1_to'] - $minKm;
                    $calKm = $minKm - $distance;
                    $stage = ($calKm - $cap) >= 0 ? $cap * $f['model_fare_stage1_fare'] : $calKm * $f['model_fare_stage1_fare'];
                    $calKm -= $cap;
                    $total = $minFare + $stage;
                    $stageTotal += $stage;
                }
                if ($f['model_fare_stage2_from'] <= $distance) {
                    $cap = $f['model_fare_stage2_to'] - $f['model_fare_stage1_to'];
                    $stage = ($calKm - $cap) >= 0 ? $cap * $f['model_fare_stage2_fare'] : $calKm * $f['model_fare_stage2_fare'];
                    $calKm -= $cap;
                    $total += $stage;
                    $stageTotal += $stage;
                }
                if ($f['model_fare_stage3_from'] <= $distance) {
                    $cap = $f['model_fare_stage3_to'] - $f['model_fare_stage2_to'];
                    $stage = ($calKm - $cap) >= 0 ? $cap * $f['model_fare_stage3_fare'] : $calKm * $f['model_fare_stage3_fare'];
                    $total += $stage;
                    $stageTotal += $stage;
                }
                $lines[] = ['label' => 'Minimum fare', 'amount' => $minFare];
                if ($stageTotal != 0) {
                    $lines[] = ['label' => 'Distance fare ('.$this->num($distance).' km)', 'amount' => round($stageTotal, 2)];
                }
            }
        } else {
            $lines[] = ['label' => 'Base fare', 'amount' => $baseFare];
        }

        $minutesCost = 0;
        if (($type === 2 || $type === 3) && $f['minutes_fare'] > 0) {
            $minutes = round($durationSec / 60);
            $minutesCost = $minutes * $f['minutes_fare'];
            $total += $minutesCost;
            $lines[] = ['label' => $minutes.' min × '.Money::format($f['minutes_fare']).'/min', 'amount' => round($minutesCost, 2)];
        }

        $split = ['nightfare_applicable' => 0, 'nightfare' => 0, 'eveningfare_applicable' => 0, 'eveningfare' => 0, 'minutes_fare' => round($minutesCost, 2), 'base_fare' => $baseFare, 'model_fare_type' => $f['km_wise_fare']];

        if ((float) $f['night_charge'] != 0 && $this->inNightWindow($at, (string) $f['night_timing_from'], (string) $f['night_timing_to'])) {
            $night = ($f['night_fare'] / 100) * $total;
            $total += $night;
            $split['nightfare_applicable'] = 1;
            $split['nightfare'] = round($night, 2);
            $lines[] = ['label' => 'Night fare ('.$this->num($f['night_fare']).'%)', 'amount' => round($night, 2)];
        }

        if ((float) $f['evening_charge'] != 0 && $this->inSameDayWindow($at, (string) $f['evening_timing_from'], (string) $f['evening_timing_to'])) {
            $evening = ($f['evening_fare'] / 100) * $total;
            $total += $evening;
            $split['eveningfare_applicable'] = 1;
            $split['eveningfare'] = round($evening, 2);
            $lines[] = ['label' => 'Evening fare ('.$this->num($f['evening_fare']).'%)', 'amount' => round($evening, 2)];
        }

        return [$total, $lines, $split];
    }

    /* ----------------------------------------------------------------- Rental */

    /** Rental package chosen on the form, then calculatetotalfare_package() with rental_os = 2. */
    private function rental(FleetTrip $trip): array
    {
        if (! $trip->packageId && $trip->packageId !== 0) {
            return $this->unavailable($trip, 'Please choose a rental package.');
        }

        $list = $this->packages(self::RENTAL, $trip->modelId, $trip->pickupLat, $trip->pickupLng);
        $pkg = collect($list['packages'])->firstWhere('id', $trip->packageId);
        if (! $pkg) {
            return $this->unavailable($trip, $list['source'] === 'zone' && ! $list['packages']
                ? 'Rental service is not available in this zone right now.'
                : 'This rental package is not available for the selected vehicle.');
        }

        $distance = $trip->distanceKm;
        $travelMin = $trip->durationSec / 60;
        $extraDistanceFare = $distance > $pkg['distance'] ? ($distance - $pkg['distance']) * $pkg['additional_fare_per_distance'] : 0;
        $extraDurationFare = $travelMin > $pkg['plan_duration'] ? (($travelMin - $pkg['plan_duration']) / 60) * $pkg['additional_fare_per_hour'] : 0;
        $total = round($pkg['base_fare'] + $extraDurationFare + $extraDistanceFare + $pkg['driver_beta'], 2);

        $lines = [['label' => 'Package — '.$pkg['label'], 'amount' => $pkg['base_fare']]];
        if ($extraDistanceFare > 0) {
            $lines[] = ['label' => 'Extra '.$this->num($distance - $pkg['distance']).' km × '.Money::format($pkg['additional_fare_per_distance']).'/km', 'amount' => round($extraDistanceFare, 2)];
        }
        if ($extraDurationFare > 0) {
            $lines[] = ['label' => 'Extra time × '.Money::format($pkg['additional_fare_per_hour']).'/hr', 'amount' => round($extraDurationFare, 2)];
        }
        if ($pkg['driver_beta'] > 0) {
            $lines[] = ['label' => 'Driver beta', 'amount' => $pkg['driver_beta']];
        }

        return $this->result($trip, $total, $lines, 'rental_package', ['base_fare' => $pkg['base_fare']], [
            'plan' => $pkg + ['source' => $list['source']],
            'excluded' => ['Extra km '.Money::format($pkg['additional_fare_per_distance']).'/km and extra hour '.Money::format($pkg['additional_fare_per_hour']).'/hr beyond the package'],
        ]);
    }

    /* ------------------------------------------------------------- Outstation */

    /** users/get_outdynamic_package_detail + calculatetotalfare_package() with rental_os = 3. */
    private function outstation(FleetTrip $trip): array
    {
        $minOutstation = $this->settings->minOutstationKm();
        if ($minOutstation > 0 && $trip->distanceKm < $minOutstation) {
            return $this->unavailable($trip, 'Your travel distance is below the minimum Outstation distance ('.$this->num($minOutstation).' km) — please choose Local.');
        }

        $travelMin = $trip->durationSec / 60;
        if (! $trip->isRoundTrip() && $travelMin > $this->settings->defaultOnewayDurationMinutes()) {
            return $this->unavailable($trip, 'This journey is too long for a One Way trip — please choose Round Trip.');
        }

        $osType = $trip->isRoundTrip() ? '2' : '1';
        $tripDistance = $trip->isRoundTrip() ? round($trip->distanceKm * 2, 2) : $trip->distanceKm;

        // Common outstation package by distance (Model_FleeteraFind::getoutpack_basedon_distance)…
        $plan = $this->outstationPackageByDistance($tripDistance, $trip->modelId, $osType);
        $source = $plan ? 'common' : null;

        // …overridden by the zone fare when zone fares are on.
        if ($this->settings->zoneFareEnabled() && $trip->pickupLat && $trip->pickupLng) {
            $op = $this->zones->operatingZone($trip->pickupLat, $trip->pickupLng, $trip->dropLat, $trip->dropLng, $trip->modelId, $osType === '2' ? 4 : 3);
            if (! $op['available']) {
                return $this->unavailable($trip, $op['message']);
            }
            $tier = $this->zones->zoneOutstationTier($trip->pickupLat, $trip->pickupLng, $trip->dropLat, $trip->dropLng, $trip->modelId, $osType, $tripDistance);
            if ($tier !== null) {
                $plan = [
                    'id' => (int) ($tier['_id'] ?? 0),
                    'base_fare' => (float) ($tier['base_fare'] ?? 0),
                    'distance' => (float) ($tier['distance'] ?? 0),
                    'plan_duration' => (float) ($plan['plan_duration'] ?? 0),
                    'additional_fare_per_distance' => (float) ($tier['additional_fare_per_distance'] ?? 0),
                    'additional_fare_per_hour' => (float) ($plan['additional_fare_per_hour'] ?? 0),
                    'driver_beta' => (float) ($tier['driver_beta'] ?? 0),
                    'distance_unit' => $plan['distance_unit'] ?? 'km',
                ];
                $source = 'zone';
            }
        }

        if (! $plan) {
            return $this->unavailable($trip, 'Outstation fares for this vehicle are not configured yet. Submit your request and we will call you with a quote.');
        }

        // calculatetotalfare_package()
        $day = $trip->isRoundTrip() ? max(1, $trip->days) : 0;
        $double = $trip->isRoundTrip() && $this->settings->outstationRoundDoubleFare();
        $planDistance = $double ? $day * $plan['distance'] : $plan['distance'];
        $baseFare = $double ? $day * $plan['base_fare'] : $plan['base_fare'];
        $extraDistance = $tripDistance > $planDistance ? $tripDistance - $planDistance : 0;
        $extraDistanceFare = $extraDistance * $plan['additional_fare_per_distance'];
        $driverBeta = $trip->isRoundTrip() ? $plan['driver_beta'] * $day : $plan['driver_beta'];
        $total = round($baseFare + $extraDistanceFare + $driverBeta, 2);

        $lines = [['label' => ($trip->isRoundTrip() ? 'Round trip fare — '.$day.' day'.($day > 1 ? 's' : '').', ' : 'One way fare — ').'up to '.$this->num($planDistance).' km', 'amount' => $baseFare]];
        if ($extraDistanceFare > 0) {
            $lines[] = ['label' => 'Extra '.$this->num($extraDistance).' km × '.Money::format($plan['additional_fare_per_distance']).'/km', 'amount' => round($extraDistanceFare, 2)];
        }
        if ($driverBeta > 0) {
            $lines[] = ['label' => 'Driver beta'.($day > 1 ? ' ('.$day.' days)' : ''), 'amount' => $driverBeta];
        }

        return $this->result($trip, $total, $lines, 'outstation_package', ['base_fare' => $baseFare, 'distance_fare' => round($extraDistanceFare, 2)], [
            'plan' => $plan + ['source' => $source, 'driver_beta_total' => $driverBeta],
            'trip_distance' => $tripDistance,
            'excluded' => ['Tolls, state permit and parking as applicable'],
        ]);
    }

    /** Port of Model_FleeteraFind::getoutpack_basedon_distance() — including its nearest-below preference. */
    private function outstationPackageByDistance(float $tripDistance, int $modelId, string $osType): ?array
    {
        $packages = $this->mongo->find('package_plans', [
            'taxi_modelid' => $modelId, 'package_type' => '2', 'plan_status' => 'A', 'os_type' => $osType,
        ], ['sort' => ['distance' => 1]]);

        $gte = null;
        $lte = null;
        foreach ($packages as $p) {
            $d = (float) ($p['distance'] ?? 0);
            if ($d >= $tripDistance && $gte === null) {
                $gte = $p;
            }
            if ($d <= $tripDistance) {
                $lte = $p;
            }
        }

        $selected = ($gte && $lte) ? ((float) $lte['distance'] < $tripDistance ? $lte : $gte) : ($gte ?? $lte);

        return $selected ? $this->packageRow($selected) : null;
    }

    /* ---------------------------------------------------------------- helpers */

    /** @return array{id: int, value: string, label: string, plan_duration: float, distance: float, distance_unit: string, base_fare: float, additional_fare_per_distance: float, additional_fare_per_hour: float, driver_beta: float, notes: string} */
    private function packageRow(array $p): array
    {
        $minutes = (float) ($p['plan_duration'] ?? 0);
        $unit = (string) ($p['distance_unit'] ?? $this->settings->unitName());
        $distance = (float) ($p['distance'] ?? 0);
        $hours = intdiv((int) $minutes, 60);
        $mins = (int) $minutes % 60;

        return [
            'id' => (int) $p['_id'],
            'value' => $minutes.'-'.$distance.'-'.$unit.'-'.(int) $p['_id'],
            'label' => trim(($hours ? $hours.' hr'.($hours > 1 ? 's' : '') : '').($mins ? ' '.$mins.' min' : '')).' / '.$this->num($distance).' '.$unit,
            'plan_duration' => $minutes,
            'distance' => $distance,
            'distance_unit' => $unit,
            'base_fare' => (float) ($p['base_fare'] ?? 0),
            'additional_fare_per_distance' => (float) ($p['additional_fare_per_distance'] ?? 0),
            'additional_fare_per_hour' => (float) ($p['additional_fare_per_hour'] ?? 0),
            'driver_beta' => (float) ($p['driver_beta'] ?? 0),
            'notes' => (string) ($p['notes'] ?? ''),
        ];
    }

    /** Builds the response in the website's estimate format, adding tax like the admin (TAX % on the subtotal). */
    private function result(FleetTrip $trip, float $subtotal, array $lines, string $basis, array $split, array $extra = []): array
    {
        $taxRate = $this->settings->tax();
        $tax = round($subtotal * $taxRate / 100, 2);
        $total = round($subtotal + $tax, 2);
        if ($tax > 0) {
            $lines[] = ['label' => 'Tax ('.$this->num($taxRate).'%)', 'amount' => $tax];
        }

        $distance = $trip->isRoundTrip() ? round($trip->distanceKm * 2, 2) : $trip->distanceKm;
        $minutes = $trip->durationSec / 60 * ($trip->isRoundTrip() ? 2 : 1);

        return [
            'available' => true,
            'starting_from' => false,
            'message' => null,
            'route_id' => null,
            'route_name' => null,
            'distance_km' => (string) round($distance, 2),
            'duration' => $this->durationLabel((int) round($minutes * 60)),
            'vehicle' => $this->modelName($trip->modelId),
            'basis' => $basis,
            'lines' => array_map(fn ($l) => ['label' => $l['label'], 'amount' => number_format((float) $l['amount'], 2, '.', ''), 'formatted' => Money::format($l['amount'])], $lines),
            'excluded' => $extra['excluded'] ?? [],
            'total' => number_format($total, 2, '.', ''),
            'total_formatted' => Money::format($total),
            'is_sample' => false,
            'fleet' => [
                'trip_type' => $trip->tripType,
                'os_trip_type' => $trip->tripType === self::OUTSTATION ? $trip->osTripType : null,
                'days' => $trip->isRoundTrip() ? max(1, $trip->days) : null,
                'model_id' => $trip->modelId,
                'subtotal' => round($subtotal, 2),
                'tax_amount' => $tax,
                'tax_rate' => $taxRate,
                'total' => $total,
                'distance_km' => $trip->distanceKm,
                'duration_sec' => $trip->durationSec,
                'plan' => $extra['plan'] ?? null,
                'fare_info' => $extra['fare_info'] ?? null,
                'split' => $split,
            ],
        ];
    }

    private function unavailable(FleetTrip $trip, string $message): array
    {
        return [
            'available' => false,
            'no_service_available' => true,
            'starting_from' => false,
            'message' => $message,
            'route_id' => null,
            'route_name' => null,
            'distance_km' => (string) round($trip->distanceKm, 2),
            'duration' => $this->durationLabel($trip->durationSec),
            'vehicle' => $this->modelName($trip->modelId),
            'basis' => null,
            'lines' => [],
            'excluded' => [],
            'total' => null,
            'total_formatted' => null,
            'is_sample' => false,
        ];
    }

    /**
     * Time used for night / evening / peak surcharges. The admin Add Booking screen sends no pickup time, so its
     * preview applies them at the current time; set FLEET_SURCHARGE_AT_PICKUP=true to use the pickup time instead.
     */
    private function surchargeTime(FleetTrip $trip): CarbonImmutable
    {
        return config('fleet.surcharge_at_pickup') ? $trip->pickupAt : CarbonImmutable::now(config('fleet.timezone'));
    }

    /** Night window that may cross midnight (the admin's "NIGHT FARE ISSUE FIX" logic). */
    private function inNightWindow(\DateTimeInterface $at, string $from, string $to): bool
    {
        if ($from === '' || $to === '') {
            return false;
        }
        $t = CarbonImmutable::instance($at);
        $day = $t->format('Y-m-d');
        $fromN = (float) str_replace(':', '', $from);
        $toN = (float) str_replace(':', '', $to);

        if ($fromN < $toN) {
            return $t->between(CarbonImmutable::parse($day.' '.$from, $t->tz), CarbonImmutable::parse($day.' '.$to, $t->tz));
        }

        $prev = $t->subDay()->format('Y-m-d');
        $next = $t->addDay()->format('Y-m-d');

        return $t->between(CarbonImmutable::parse($prev.' '.$from, $t->tz), CarbonImmutable::parse($day.' '.$to, $t->tz))
            || $t->between(CarbonImmutable::parse($day.' '.$from, $t->tz), CarbonImmutable::parse($next.' '.$to, $t->tz));
    }

    private function inSameDayWindow(\DateTimeInterface $at, string $from, string $to): bool
    {
        if ($from === '' || $to === '') {
            return false;
        }
        $secs = fn (string $hms) => array_sum(array_map(fn ($p, $m) => (int) $p * $m, array_pad(explode(':', $hms), 3, 0), [3600, 60, 1]));
        $now = $secs($at->format('H:i:s'));

        return $now >= $secs($from) && $now <= $secs($to);
    }

    private function durationLabel(int $seconds): string
    {
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);

        return trim(($h ? $h.' hr'.($h > 1 ? 's' : '').' ' : '').($m || ! $h ? $m.' min' : ''));
    }

    private function num(float|int|string|null $n): string
    {
        return rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
    }
}
