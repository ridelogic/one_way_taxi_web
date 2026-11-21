<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\VehicleCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Puducherry is a supported city, found by its official name and by the names customers still type (Pondicherry, Pondy). */
class PuducherryCityTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_official_name_and_aliases_resolve_to_the_same_city(): void
    {
        $puducherry = City::where('slug', 'puducherry')->firstOrFail();

        foreach (['Puducherry', 'puducherry', 'Pondicherry', 'PONDICHERRY', 'Pondy', 'Pondicherry, Puducherry'] as $typed) {
            $this->assertTrue($puducherry->is(City::resolve($typed)), $typed);
        }
    }

    public function test_existing_cities_still_resolve_and_unknown_names_do_not(): void
    {
        $this->assertSame('chennai', City::resolve('Chennai, Tamil Nadu')?->slug);
        $this->assertSame('coimbatore', City::resolve('coimbatore')?->slug);
        $this->assertNull(City::resolve('Pondicherr'));
        $this->assertNull(City::resolve('Atlantis'));
    }

    public function test_alias_never_matches_an_inactive_city(): void
    {
        City::where('slug', 'puducherry')->update(['is_active' => false]);

        $this->assertNull(City::resolve('Pondicherry'));
        $this->assertNull(City::resolve('Puducherry'));
    }

    public function test_fare_estimate_works_when_customer_types_pondicherry(): void
    {
        $vehicle = VehicleCategory::where('slug', 'economy-sedan')->firstOrFail()->id;

        $official = $this->postJson('/fare-estimate', ['trip_type' => 'one_way', 'pickup_location' => 'Chennai', 'destination_location' => 'Puducherry', 'vehicle_category_id' => $vehicle])
            ->assertOk()->assertJson(['available' => true])->json();
        $alias = $this->postJson('/fare-estimate', ['trip_type' => 'one_way', 'pickup_location' => 'Chennai', 'destination_location' => 'Pondicherry', 'vehicle_category_id' => $vehicle])
            ->assertOk()->assertJson(['available' => true])->json();

        $this->assertSame($official['total'], $alias['total']);
        $this->assertSame($official['route_id'], $alias['route_id']);
    }

    public function test_puducherry_without_a_published_route_is_not_given_an_invented_fare(): void
    {
        $vehicle = VehicleCategory::where('slug', 'economy-sedan')->firstOrFail()->id;

        // No Puducherry → Coimbatore route is configured, so the team quotes it.
        $this->postJson('/fare-estimate', ['trip_type' => 'one_way', 'pickup_location' => 'Pondicherry', 'destination_location' => 'Coimbatore', 'vehicle_category_id' => $vehicle])
            ->assertOk()->assertJson(['available' => false, 'total' => null]);
    }

    public function test_puducherry_is_offered_in_pickup_and_drop_suggestions(): void
    {
        foreach (['/', route('booking.create')] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('<option value="Puducherry">Puducherry · also Pondicherry, Pondy</option>', false)
                ->assertSee('<option value="Chennai">Tamil Nadu</option>', false);
        }
    }
}
