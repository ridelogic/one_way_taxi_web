<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\TaxiRoute;
use App\Models\VehicleCategory;
use App\Services\FareCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FareCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function estimate(string $from, string $to, string $vehicle): array
    {
        return app(FareCalculator::class)->estimate(
            City::where('name', $from)->first(),
            City::where('name', $to)->first(),
            VehicleCategory::where('slug', $vehicle)->first(),
        );
    }

    public function test_per_km_fare_with_driver_allowance_and_rounding(): void
    {
        // 505 km × ₹13 = ₹6,565 + ₹400 allowance = ₹6,965 → rounded up to ₹6,970
        $e = $this->estimate('Chennai', 'Coimbatore', 'economy-sedan');

        $this->assertTrue($e['available']);
        $this->assertSame('6970.00', $e['total']);
        $this->assertSame('per_km', $e['basis']);
        $this->assertContains('Toll charges — as applicable on the route', $e['excluded']);
    }

    public function test_minimum_chargeable_distance_applies_to_short_trips(): void
    {
        // 86 km is below the 130 km minimum: 130 × ₹13 + ₹400 = ₹2,090
        $e = $this->estimate('Coimbatore', 'Ooty', 'economy-sedan');

        $this->assertSame('2090.00', $e['total']);
        $this->assertStringContainsString('minimum distance', $e['lines'][0]['label']);
    }

    public function test_route_specific_fare_overrides_rule(): void
    {
        $route = TaxiRoute::where('slug', 'chennai-to-coimbatore-taxi')->first();
        $suv = VehicleCategory::where('slug', 'suv')->first();
        $route->fares()->create(['vehicle_category_id' => $suv->id, 'fare' => '8499.50']);

        $e = $this->estimate('Chennai', 'Coimbatore', 'suv');

        $this->assertSame('route_fare', $e['basis']);
        $this->assertSame('8500.00', $e['total']); // rounded up to ₹10
    }

    public function test_fixed_additional_charges_are_added_exactly(): void
    {
        $vehicle = VehicleCategory::where('slug', 'premium-sedan')->first();
        $vehicle->fareRule->update(['additional_charges' => [['label' => 'Airport entry', 'amount' => '0.10', 'note' => null]]]);
        settings()->setMany(['fare_round_to' => 1]);

        // 346 km × ₹15 = ₹5,190 + ₹400 + ₹0.10 = ₹5,590.10 → rounded up to whole rupee ₹5,591
        $e = $this->estimate('Bengaluru', 'Chennai', 'premium-sedan');

        $this->assertSame('5591.00', $e['total']);
    }

    public function test_unknown_route_has_no_estimate(): void
    {
        $e = $this->estimate('Ooty', 'Salem', 'economy-sedan');

        $this->assertFalse($e['available']);
        $this->assertNotEmpty($e['message']);
    }
}
