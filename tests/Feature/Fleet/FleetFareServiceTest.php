<?php

namespace Tests\Feature\Fleet;

use App\Services\Fleet\FleetFareService;
use App\Services\Fleet\FleetMongo;
use App\Services\Fleet\FleetTrip;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Fleet fares must match the admin "Add Booking" screen. Fixtures are copies of the UAT fleet data
 * (operating_zone_fare zone 1, package_plans, motor_model fare fields, siteinfo settings); expected totals are
 * worked out by hand from the admin's formulas.
 */
class FleetFareServiceTest extends TestCase
{
    // A point in Coimbatore (inside the TamilNadu zone only) and one in Salem (TamilNadu → Salem sub-zone).
    private const CBE = [11.0168, 76.9558];

    private const SALEM = [11.6643, 78.1460];

    private FakeFleetMongo $mongo;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['fleet.enabled' => true, 'fleet.surcharge_at_pickup' => true, 'fleet.timezone' => 'Asia/Kolkata']);

        $this->mongo = new FakeFleetMongo([
            'siteinfo' => [[
                '_id' => 1, 'tax' => '0', 'fare_calculation_type' => '3', 'zonefare_available_status' => 1,
                'min_outstation_kms' => '60', 'default_oneway_trip_duration' => '24', 'local_zone_match_status' => 1,
                'default_unit' => '0', 'admin_commission' => 0,
            ]],
            'zone_taxidetails' => [
                ['_id' => 1, 'geofence_name' => 'TamilNadu', 'status' => 'A', 'parent_zone_id' => null, 'bbox' => [76, 8, 80.5, 13.5], 'loc' => ['coordinates' => [[[76, 8], [80.5, 8], [80.5, 13.5], [76, 13.5]]]]],
                ['_id' => 2, 'geofence_name' => 'Salem', 'status' => 'A', 'parent_zone_id' => 1, 'bbox' => [77.9, 11.5, 78.3, 11.8], 'loc' => ['coordinates' => [[[77.9, 11.5], [78.3, 11.5], [78.3, 11.8], [77.9, 11.8]]]]],
            ],
            'operating_zone_fare' => [[
                '_id' => 1, 'zone_id' => 1, 'zone_status' => 'A',
                'models' => [
                    ['model_id' => 1, 'model_name' => 'SUV', 'status' => 'A', 'rental_status' => 'I', 'min_km' => 1, 'min_fare' => 2000, 'base_fare' => 0,
                        'additional_fare_per_km' => 2, 'fare_per_minute' => 2, 'waiting_charge_per_minute' => 2, 'peak1_from' => '', 'peak1_to' => '', 'peak1_percent' => 0,
                        'peak2_from' => '', 'peak2_to' => '', 'peak2_percent' => 0, 'rental' => [], 'outstation_oneway_status' => 'I', 'outstation_roundtrip_status' => 'I',
                        'outstation_oneway' => [], 'outstation_roundtrip' => []],
                    ['model_id' => 3, 'model_name' => 'Sedan', 'status' => 'I', 'rental_status' => 'I', 'min_km' => 0, 'min_fare' => 0, 'additional_fare_per_km' => 0,
                        'fare_per_minute' => 0, 'rental' => [], 'outstation_oneway_status' => 'A', 'outstation_roundtrip_status' => 'I',
                        'outstation_oneway' => [['distance' => 50, 'base_fare' => 50000, 'additional_fare_per_distance' => 300, 'driver_beta' => 600]], 'outstation_roundtrip' => []],
                ],
            ]],
            'motor_model' => [
                ['_id' => 1, 'model_name' => 'SUV', 'model_size' => 7, 'model_status' => 'A', 'enable_local' => 1, 'enable_rental' => 1, 'enable_outstation' => 1,
                    'base_fare' => 0, 'min_fare' => 250, 'min_km' => 4, 'km_wise_fare' => 1, 'additional_fare_per_km' => 40, 'minutes_fare' => 1,
                    'night_charge' => 1, 'night_timing_from' => '17:00', 'night_timing_to' => '20:30', 'night_fare' => 10,
                    'evening_charge' => 1, 'evening_timing_from' => '07:30', 'evening_timing_to' => '10:00', 'evening_fare' => 10],
                ['_id' => 3, 'model_name' => 'Sedan', 'model_size' => 4, 'model_status' => 'A', 'enable_local' => 1, 'enable_rental' => 1, 'enable_outstation' => 1],
                ['_id' => 4, 'model_name' => 'Hatchback', 'model_size' => 3, 'model_status' => 'A', 'enable_local' => 1, 'enable_rental' => 0, 'enable_outstation' => 1],
            ],
            'package_plans' => [
                ['_id' => 21, 'package_type' => '2', 'os_type' => '1', 'taxi_modelid' => 1, 'plan_status' => 'A', 'plan_duration' => 0, 'distance' => 130, 'base_fare' => 2470, 'additional_fare_per_distance' => 19, 'additional_fare_per_hour' => 0, 'driver_beta' => '300', 'distance_unit' => 'km'],
                ['_id' => 23, 'package_type' => '2', 'os_type' => '1', 'taxi_modelid' => 1, 'plan_status' => 'A', 'plan_duration' => 0, 'distance' => 225, 'base_fare' => 4275, 'additional_fare_per_distance' => 18, 'additional_fare_per_hour' => 0, 'driver_beta' => '300', 'distance_unit' => 'km'],
                ['_id' => 24, 'package_type' => '2', 'os_type' => '1', 'taxi_modelid' => 1, 'plan_status' => 'A', 'plan_duration' => 0, 'distance' => 400, 'base_fare' => 7200, 'additional_fare_per_distance' => 18, 'additional_fare_per_hour' => 0, 'driver_beta' => '500', 'distance_unit' => 'km'],
                ['_id' => 40, 'package_type' => '2', 'os_type' => '2', 'taxi_modelid' => 1, 'plan_status' => 'A', 'plan_duration' => 60, 'distance' => 250, 'base_fare' => 4000, 'additional_fare_per_distance' => 16, 'additional_fare_per_hour' => 0, 'driver_beta' => '300', 'distance_unit' => 'km'],
                ['_id' => 48, 'package_type' => '1', 'taxi_modelid' => 3, 'plan_status' => 'A', 'plan_duration' => 180, 'distance' => 30, 'base_fare' => 900, 'additional_fare_per_distance' => 15, 'additional_fare_per_hour' => 300, 'driver_beta' => '0', 'distance_unit' => 'km'],
            ],
        ]);
        $this->app->instance(FleetMongo::class, $this->mongo);
    }

    private function fares(): FleetFareService
    {
        return $this->app->make(FleetFareService::class);
    }

    private function trip(int $type, int $model, array $from, ?array $to, float $km, int $sec, array $extra = []): FleetTrip
    {
        return new FleetTrip(
            tripType: $type, modelId: $model,
            pickupLat: $from[0], pickupLng: $from[1], dropLat: $to[0] ?? null, dropLng: $to[1] ?? null,
            distanceKm: $km, durationSec: $sec,
            pickupAt: CarbonImmutable::parse($extra['at'] ?? '2026-12-01 12:00', 'Asia/Kolkata'),
            osTripType: $extra['os'] ?? 1, days: $extra['days'] ?? 1, packageId: $extra['package'] ?? null,
        );
    }

    private function setSite(array $values): void
    {
        $this->mongo->data['siteinfo'][0] = array_merge($this->mongo->data['siteinfo'][0], $values);
        Cache::flush();
    }

    public function test_local_uses_the_operating_zone_fare(): void
    {
        // min fare 2000 for the first 1 km + 9 km × ₹2 + 30 min × ₹2 = 2078
        $r = $this->fares()->estimate($this->trip(1, 1, self::CBE, [11.05, 76.99], 10, 1800));

        $this->assertTrue($r['available']);
        $this->assertSame('2078.00', $r['total']);
        $this->assertSame('operating_zone', $r['basis']);
    }

    public function test_sub_zone_without_fare_hands_over_to_its_parent_zone(): void
    {
        // Salem has no Operating Zone fare, so TamilNadu's applies (5 km, 10 min): 2000 + 4×2 + 10×2 = 2028
        $r = $this->fares()->estimate($this->trip(1, 1, self::SALEM, [11.66, 78.15], 5, 600));

        $this->assertTrue($r['available']);
        $this->assertSame('2028.00', $r['total']);
    }

    public function test_local_is_blocked_when_the_model_is_inactive_in_the_zone(): void
    {
        $r = $this->fares()->estimate($this->trip(1, 3, self::CBE, [11.05, 76.99], 10, 1800));

        $this->assertFalse($r['available']);
        $this->assertSame('This vehicle type is not available in this zone right now.', $r['message']);
    }

    public function test_local_is_blocked_above_the_outstation_minimum_distance(): void
    {
        $r = $this->fares()->estimate($this->trip(1, 1, self::CBE, [11.9, 77.5], 75, 5400));

        $this->assertFalse($r['available']);
        $this->assertStringContainsString('above the Local km limit', $r['message']);
    }

    public function test_tax_is_added_on_top(): void
    {
        $this->setSite(['tax' => '5']);
        $r = $this->fares()->estimate($this->trip(1, 1, self::CBE, [11.05, 76.99], 10, 1800));

        $this->assertSame('2181.90', $r['total']); // 2078 + 5%
        $this->assertSame(2078.0, $r['fleet']['subtotal']);
    }

    public function test_local_common_model_fare_when_zone_fares_are_off(): void
    {
        $this->setSite(['zonefare_available_status' => 0]);

        // km-wise: min fare 250 (first 4 km) + 6 km × ₹40 = 490, + 20 min × ₹1 = 510 (12:00 — no night/evening fare)
        $this->assertSame('510.00', $this->fares()->estimate($this->trip(1, 1, self::CBE, [11.05, 76.99], 10, 1200))['total']);

        // 18:00 is inside the 17:00–20:30 night window: +10% → 561
        $this->assertSame('561.00', $this->fares()->estimate($this->trip(1, 1, self::CBE, [11.05, 76.99], 10, 1200, ['at' => '2026-12-01 18:00']))['total']);
    }

    public function test_outstation_one_way_uses_the_zone_tier(): void
    {
        // Sedan zone tier: up to 50 km ₹50,000, then ₹300/km, beta ₹600 → 100 km: 50000 + 50×300 + 600 = 65600
        $r = $this->fares()->estimate($this->trip(3, 3, self::CBE, [11.9, 78.0], 100, 7200));

        $this->assertTrue($r['available']);
        $this->assertSame('65600.00', $r['total']);
        $this->assertSame('zone', $r['fleet']['plan']['source']);
    }

    public function test_outstation_is_blocked_when_the_zone_has_it_switched_off(): void
    {
        // SUV: outstation_oneway_status = I in TamilNadu
        $r = $this->fares()->estimate($this->trip(3, 1, self::CBE, [11.9, 78.0], 100, 7200));

        $this->assertFalse($r['available']);
        $this->assertSame('Outstation service is not available in this zone right now.', $r['message']);
    }

    public function test_outstation_common_package_keeps_the_admins_nearest_below_choice(): void
    {
        $this->setSite(['zonefare_available_status' => 0]);

        // 200 km: packages 130/225/400 → the admin picks 130 (nearest below the trip) → 2470 + 70×19 + 300 = 4100
        $r = $this->fares()->estimate($this->trip(3, 1, self::CBE, [12.5, 78.5], 200, 14400));

        $this->assertSame('4100.00', $r['total']);
        $this->assertSame(21, $r['fleet']['plan']['id']);
    }

    public function test_round_trip_multiplies_base_fare_distance_and_beta_by_days(): void
    {
        $this->setSite(['zonefare_available_status' => 0]);

        // 150 km each way → 300 km; package #40 (250 km, ₹4000, beta ₹300) × 2 days → 500 km, ₹8000, beta ₹600 → 8600
        $r = $this->fares()->estimate($this->trip(3, 1, self::CBE, [12.0, 78.0], 150, 10800, ['os' => 2, 'days' => 2]));

        $this->assertSame('8600.00', $r['total']);
        $this->assertSame('300', $r['distance_km']);
    }

    public function test_outstation_below_minimum_distance_is_blocked(): void
    {
        $r = $this->fares()->estimate($this->trip(3, 3, self::CBE, [11.2, 77.1], 40, 3600));

        $this->assertFalse($r['available']);
        $this->assertStringContainsString('below the minimum Outstation distance', $r['message']);
    }

    public function test_rental_package_fare(): void
    {
        $this->setSite(['zonefare_available_status' => 0]);

        // Sedan package #48: 3 hrs / 30 km for ₹900, no drop → 900
        $r = $this->fares()->estimate($this->trip(2, 3, self::CBE, null, 0, 0, ['package' => 48]));

        $this->assertTrue($r['available']);
        $this->assertSame('900.00', $r['total']);
    }

    public function test_rental_lists_only_the_zone_packages_when_the_pickup_is_in_an_operating_zone(): void
    {
        // Zone fares on and Sedan rental is off in TamilNadu → no packages (no fallback to the common plans)
        $list = $this->fares()->packages(2, 3, self::CBE[0], self::CBE[1]);

        $this->assertSame('zone', $list['source']);
        $this->assertSame([], $list['packages']);
    }

    public function test_models_respect_the_trip_type_flags(): void
    {
        $this->assertSame([1, 3], array_column($this->fares()->models(2), 'model_id')); // Hatchback has rental off
        $this->assertSame([1, 3, 4], array_column($this->fares()->models(3), 'model_id'));
    }
}
