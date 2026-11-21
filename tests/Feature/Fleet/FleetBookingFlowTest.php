<?php

namespace Tests\Feature\Fleet;

use App\Models\Booking;
use App\Models\VehicleCategory;
use App\Services\Fleet\FleetBookingService;
use App\Services\Fleet\FleetMongo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

/** Website booking page with a vehicle linked to a fleet model: fleet fare, server-side re-check, MongoDB push. */
class FleetBookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private int $vehicleId;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['fleet.enabled' => true, 'fleet.push_bookings' => true, 'fleet.google_maps_key' => '']);

        $this->app->instance(FleetMongo::class, new FakeFleetMongo([
            'siteinfo' => [['_id' => 1, 'tax' => '0', 'fare_calculation_type' => '3', 'zonefare_available_status' => 1, 'min_outstation_kms' => '60', 'local_zone_match_status' => 1, 'default_unit' => '0']],
            'zone_taxidetails' => [['_id' => 1, 'geofence_name' => 'TamilNadu', 'status' => 'A', 'parent_zone_id' => null, 'bbox' => [76, 8, 80.5, 13.5], 'loc' => ['coordinates' => [[[76, 8], [80.5, 8], [80.5, 13.5], [76, 13.5]]]]]],
            'operating_zone_fare' => [['_id' => 1, 'zone_id' => 1, 'zone_status' => 'A', 'models' => [
                ['model_id' => 1, 'status' => 'A', 'min_km' => 1, 'min_fare' => 2000, 'additional_fare_per_km' => 2, 'fare_per_minute' => 2],
            ]]],
            'motor_model' => [['_id' => 1, 'model_name' => 'SUV', 'model_size' => 7, 'model_status' => 'A', 'enable_local' => 1, 'enable_rental' => 1, 'enable_outstation' => 1]],
        ]));

        $this->vehicleId = VehicleCategory::where('slug', 'suv')->value('id');
        VehicleCategory::whereKey($this->vehicleId)->update(['fleet_model_id' => 1]);
    }

    /** Coimbatore → Peelamedu: ~5 km straight line, 10 km / 30 min by road. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'source' => 'booking-page',
            'trip_type' => 'local',
            'pickup_location' => 'Gandhipuram, Coimbatore, Tamil Nadu, India',
            'destination_location' => 'Peelamedu, Coimbatore, Tamil Nadu, India',
            'pickup_lat' => '11.0168', 'pickup_lng' => '76.9558',
            'drop_lat' => '11.0500', 'drop_lng' => '76.9900',
            'route_km' => '10', 'route_sec' => '1800',
            'pickup_city' => 'Coimbatore',
            'travel_date' => now()->addDays(3)->toDateString(),
            'travel_time' => '10:30',
            'vehicle_category_id' => $this->vehicleId,
            'customer_name' => 'Asha',
            'customer_phone' => '98400 12345',
            'consent' => '1',
        ], $overrides);
    }

    public function test_estimate_uses_the_fleet_fare_for_a_linked_vehicle(): void
    {
        $this->postJson('/fare-estimate', $this->payload())
            ->assertOk()
            ->assertJson(['available' => true, 'total' => '2078.00', 'basis' => 'operating_zone', 'vehicle' => 'SUV']);
    }

    public function test_an_implausible_browser_distance_is_not_priced(): void
    {
        // 2 km by road for points ~5 km apart in a straight line is impossible.
        $this->postJson('/fare-estimate', $this->payload(['route_km' => '2', 'route_sec' => '300']))
            ->assertOk()
            ->assertJson(['available' => false])
            ->assertJsonFragment(['message' => 'We could not measure the road distance for this route. Submit your request and our team will call you with the fare.']);
    }

    public function test_booking_is_saved_with_the_fleet_fare_and_sent_to_the_dispatcher(): void
    {
        $push = Mockery::mock(FleetBookingService::class);
        $push->shouldReceive('create')->once()->withArgs(function (array $b, array $fleet) {
            return $b['phone'] === '+919840012345' && $b['pickup_lat'] === 11.0168 && $b['city_name'] === 'Coimbatore'
                && $fleet['trip_type'] === 1 && $fleet['model_id'] === 1 && $fleet['total'] === 2078.0;
        })->andReturn(501);
        $this->app->instance(FleetBookingService::class, $push);

        $this->post('/book', $this->payload())->assertRedirect();

        $booking = Booking::latest('id')->first();
        $this->assertSame('2078.00', $booking->estimated_fare);
        $this->assertSame(501, (int) $booking->fleet_enquiry_id);
        $this->assertNull($booking->fleet_trip_id); // set only once the dispatcher moves it to Manage Trips
        $this->assertSame('10.00', $booking->distance_km);
        $this->assertSame(1, (int) $booking->fleet_model_id);
        $this->assertSame('fleet', $booking->fare_breakdown['source']);
        $this->assertNull($booking->fleet_sync_error);
    }

    public function test_a_mongo_failure_keeps_the_website_booking_and_records_the_error(): void
    {
        $push = Mockery::mock(FleetBookingService::class);
        $push->shouldReceive('create')->once()->andThrow(new \RuntimeException('connection timed out'));
        $this->app->instance(FleetBookingService::class, $push);

        $this->post('/book', $this->payload())->assertRedirect();

        $booking = Booking::latest('id')->first();
        $this->assertNull($booking->fleet_trip_id);
        $this->assertSame('connection timed out', $booking->fleet_sync_error);
        $this->assertSame('2078.00', $booking->estimated_fare);
    }

    public function test_unlinked_vehicles_keep_the_website_fare_rules(): void
    {
        VehicleCategory::whereKey($this->vehicleId)->update(['fleet_model_id' => null]);
        $push = Mockery::mock(FleetBookingService::class);
        $push->shouldNotReceive('create');
        $this->app->instance(FleetBookingService::class, $push);

        $this->postJson('/fare-estimate', $this->payload())->assertOk()->assertJsonMissing(['basis' => 'operating_zone']);
    }

    public function test_booking_page_offers_google_places_and_marks_linked_vehicles(): void
    {
        config(['fleet.google_maps_key' => 'test-key']);

        $this->get('/book')->assertOk()
            ->assertSee('data-maps-key="test-key"', false)
            ->assertSee('data-fleet-types="local rental one_way outstation"', false)
            ->assertSee('name="pickup_lat"', false)
            ->assertSee('name="fleet_package_id"', false);
    }

    public function test_vehicle_list_is_the_admin_manage_models_list(): void
    {
        $this->get('/book')->assertOk()
            ->assertSee('SUV · 7 seats')
            ->assertDontSee('Innova Crysta')   // website-only vehicles are hidden while the fleet connection is on
            ->assertDontSee('Tempo Traveller');
    }

    public function test_vehicle_list_falls_back_to_website_vehicles_without_the_fleet_connection(): void
    {
        config(['fleet.enabled' => false]);
        $this->app->instance(FleetMongo::class, new FleetMongo);

        $this->get('/book')->assertOk()->assertSee('Innova Crysta');
    }
}
