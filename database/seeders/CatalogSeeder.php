<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\FareRule;
use App\Models\TaxiRoute;
use App\Models\VehicleCategory;
use App\Services\FareCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * DEMONSTRATION DATA — cities, fleet categories, fare rules and routes.
 * Distances and durations are approximate road figures; all fares are SAMPLE values
 * (routes are flagged is_sample_fare) until the business owner verifies them in the admin panel.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $cities = $this->seedCities();
        $vehicles = $this->seedVehicles();
        $this->seedRoutes($cities, $vehicles['economy-sedan']);
    }

    /** @return array<string, City> */
    private function seedCities(): array
    {
        $rows = [
            // name, state, image, popular destination, description
            ['Chennai', 'Tamil Nadu', 'city-chennai.jpg', true, 'Capital of Tamil Nadu, known for Marina Beach, temples and a busy international airport.'],
            ['Coimbatore', 'Tamil Nadu', 'city-coimbatore.jpg', true, 'Industrial hub and gateway to the Nilgiris hill stations.'],
            ['Ooty', 'Tamil Nadu', 'city-ooty.jpg', true, 'The “Queen of Hill Stations”, with tea gardens, lakes and the Nilgiri Mountain Railway.'],
            ['Bengaluru', 'Karnataka', 'city-bengaluru.jpg', true, 'India’s technology capital with gardens, heritage buildings and a lively food scene.'],
            ['Madurai', 'Tamil Nadu', 'city-madurai.jpg', true, 'Ancient temple city, home to the Meenakshi Amman Temple.'],
            ['Mysuru', 'Karnataka', 'city-mysuru.jpg', true, 'Royal heritage city famous for Mysore Palace and the Dasara festival.'],
            ['Puducherry', 'Puducherry', 'city-puducherry.jpg', true, 'Coastal town with French-quarter streets, cafés and a seaside promenade.'],
            ['Kodaikanal', 'Tamil Nadu', 'city-kodaikanal.jpg', true, 'Misty hill station in the Palani Hills with a star-shaped lake.'],
            ['Tiruchirappalli', 'Tamil Nadu', null, false, 'Temple city on the Kaveri river, home to the Rockfort.'],
            ['Salem', 'Tamil Nadu', null, false, 'Gateway to the Yercaud hills.'],
        ];

        $cities = [];
        foreach ($rows as $i => [$name, $state, $image, $popular, $description]) {
            $cities[$name] = City::firstOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'state' => $state,
                'description' => $description,
                'media_id' => $image ? MediaSeeder::id($image) : null,
                'is_popular_destination' => $popular,
                'is_active' => true,
                'sort_order' => $i + 1,
            ]);
        }

        return $cities;
    }

    /** @return array<string, VehicleCategory> */
    private function seedVehicles(): array
    {
        $extras = [
            ['label' => 'Toll charges', 'amount' => null, 'note' => 'as applicable on the route'],
            ['label' => 'Inter-state permit', 'amount' => null, 'note' => 'when crossing state borders'],
            ['label' => 'Parking fees', 'amount' => null, 'note' => 'as applicable'],
        ];

        $rows = [
            'economy-sedan' => [
                'name' => 'Economy Sedan', 'category' => 'Sedan', 'example_models' => 'Maruti Swift Dzire, Toyota Etios or similar',
                'image' => 'fleet-economy-sedan.jpg', 'passengers' => 4, 'luggage' => 2,
                'short' => 'Comfortable and budget-friendly for solo travellers, couples and small families.',
                'features' => ['Air conditioned', '4 passengers', '2 medium bags', 'Ideal for intercity drops'],
                'fare' => ['per_km_rate' => '13.00', 'minimum_km' => 130, 'driver_allowance' => '400.00'],
            ],
            'premium-sedan' => [
                'name' => 'Premium Sedan', 'category' => 'Sedan', 'example_models' => 'Honda City, Hyundai Verna or similar',
                'image' => 'fleet-premium-sedan.jpg', 'passengers' => 4, 'luggage' => 3,
                'short' => 'Extra legroom and a smoother ride for business trips and long journeys.',
                'features' => ['Air conditioned', '4 passengers', '3 bags', 'Spacious rear seats'],
                'fare' => ['per_km_rate' => '15.00', 'minimum_km' => 130, 'driver_allowance' => '400.00'],
            ],
            'suv' => [
                'name' => 'SUV', 'category' => 'SUV', 'example_models' => 'Mahindra XUV700, Toyota Fortuner or similar',
                'image' => 'fleet-suv-fortuner.jpg', 'passengers' => 6, 'luggage' => 4,
                'short' => 'Powerful and roomy — great for hill stations, families and extra luggage.',
                'features' => ['Air conditioned', '6 passengers', '4 bags', 'Comfortable on ghat roads'],
                'fare' => ['per_km_rate' => '18.00', 'minimum_km' => 130, 'driver_allowance' => '500.00'],
            ],
            'innova-crysta' => [
                'name' => 'Innova Crysta', 'category' => 'MUV', 'example_models' => 'Toyota Innova Crysta',
                'image' => 'fleet-innova.jpg', 'passengers' => 7, 'luggage' => 4,
                'short' => 'India’s favourite family and group vehicle with captain-seat comfort.',
                'features' => ['Air conditioned', '7 passengers', '4 bags', 'Captain seats (subject to availability)'],
                'fare' => ['per_km_rate' => '20.00', 'minimum_km' => 130, 'driver_allowance' => '500.00'],
            ],
        ];

        $vehicles = [];
        $order = 1;
        foreach ($rows as $slug => $row) {
            $vehicle = VehicleCategory::firstOrCreate(['slug' => $slug], [
                'name' => $row['name'],
                'category' => $row['category'],
                'example_models' => $row['example_models'],
                'media_id' => MediaSeeder::id($row['image']),
                'passenger_capacity' => $row['passengers'],
                'luggage_capacity' => $row['luggage'],
                'short_description' => $row['short'],
                'description' => $row['short'].' All vehicles are cleaned before every trip and driven by experienced outstation drivers. Exact model is subject to availability.',
                'features' => $row['features'],
                'sort_order' => $order++,
                'is_active' => true,
            ]);

            FareRule::firstOrCreate(['vehicle_category_id' => $vehicle->id], [
                'base_fare' => '0.00',
                'per_km_rate' => $row['fare']['per_km_rate'],
                'minimum_km' => $row['fare']['minimum_km'],
                'minimum_fare' => '0.00',
                'driver_allowance' => $row['fare']['driver_allowance'],
                'additional_charges' => $extras,
                'notes' => 'SAMPLE fare rule — verify before going live.',
                'is_active' => true,
            ]);

            $vehicles[$slug] = $vehicle;
        }

        return $vehicles;
    }

    /** @param array<string, City> $cities */
    private function seedRoutes(array $cities, VehicleCategory $startingVehicle): void
    {
        $rows = [
            // origin, destination, km, minutes, popular, thumbnail
            ['Chennai', 'Coimbatore', 505, 510, true, 'city-coimbatore.jpg'],
            ['Coimbatore', 'Ooty', 86, 165, true, 'city-ooty.jpg'],
            ['Bengaluru', 'Chennai', 346, 390, true, 'city-chennai.jpg'],
            ['Chennai', 'Bengaluru', 346, 390, true, 'city-bengaluru.jpg'],
            ['Chennai', 'Puducherry', 151, 195, true, 'city-puducherry.jpg'],
            ['Bengaluru', 'Mysuru', 145, 195, true, 'city-mysuru.jpg'],
            ['Chennai', 'Madurai', 462, 480, false, 'city-madurai.jpg'],
            ['Madurai', 'Kodaikanal', 116, 210, false, 'city-kodaikanal.jpg'],
            ['Bengaluru', 'Ooty', 268, 390, false, 'city-ooty.jpg'],
            ['Coimbatore', 'Chennai', 505, 510, false, 'city-chennai.jpg'],
            ['Coimbatore', 'Madurai', 213, 255, false, 'city-madurai.jpg'],
            ['Chennai', 'Tiruchirappalli', 332, 360, false, 'scenic-western-ghats.jpg'],
            ['Salem', 'Chennai', 340, 345, false, 'city-chennai.jpg'],
        ];

        $calculator = app(FareCalculator::class);

        foreach ($rows as $i => [$from, $to, $km, $minutes, $popular, $image]) {
            $origin = $cities[$from];
            $destination = $cities[$to];

            $route = TaxiRoute::firstOrCreate(
                ['origin_city_id' => $origin->id, 'destination_city_id' => $destination->id],
                [
                    'slug' => Str::slug($from.' to '.$to.' taxi'),
                    'distance_km' => $km,
                    'duration_minutes' => $minutes,
                    'base_fare' => '0.00',
                    'media_id' => MediaSeeder::id($image),
                    'short_description' => "One-way taxi from {$from} to {$to}. Pay only for a single journey — no return fare.",
                    'description' => "<p>Travel from {$from} to {$destination->name} in a clean, air-conditioned cab with an experienced outstation driver. With a one-way drop you pay only for the distance you travel — there is no charge for the driver’s return journey.</p><p>Pickup is available from your home, hotel, railway station or airport in {$from}. Tell us your preferred time and vehicle and our team will confirm your booking by phone.</p>",
                    'seo_title' => "{$from} to {$to} One Way Taxi — Cab Fare & Booking",
                    'seo_description' => "Book a one-way taxi from {$from} to {$to}. Approx. {$km} km. Sedans, SUVs and Innova Crysta with transparent fare estimates.",
                    'is_active' => true,
                    'is_popular' => $popular,
                    'is_sample_fare' => true,
                    'sort_order' => $i + 1,
                ],
            );

            // Starting fare = estimate for the cheapest vehicle under the sample fare rules.
            $estimate = $calculator->estimate($origin, $destination, $startingVehicle);
            if ($route->wasRecentlyCreated && $estimate['available']) {
                $route->update(['base_fare' => $estimate['total']]);
            }
        }
    }
}
