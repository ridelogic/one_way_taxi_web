<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\DistrictPage;

/**
 * Creates the Union Territory landing pages (Puducherry) from database/seeders/data/union-territories.php, using the
 * same page structure as the Tamil Nadu districts but with the territory as its own state (never Tamil Nadu).
 * Insert-only and safe to re-run: an existing page is never changed, so Admin edits are kept, and the 38 district
 * pages are not touched.
 */
class TerritoryPageSeeder extends DistrictPageSeeder
{
    public function run(): void
    {
        $territories = require __DIR__.'/data/union-territories.php';
        $districts = require __DIR__.'/data/tamil-nadu-districts.php';
        $hqs = array_map(fn (array $d) => $d['hq'], $districts + $territories);

        foreach ($territories as $slug => $d) {
            if (DistrictPage::where('slug', $slug)->exists()) {
                continue;
            }

            $local = $this->localContent($d, $d, $d['drops'] ?? [], $hqs);
            $pickups = $d['pickups'];

            $page = DistrictPage::create([
                'name' => $d['name'],
                'slug' => $slug,
                'state' => $d['name'],
                'headquarters' => $d['hq'],
                'region' => $d['region'],
                'heading' => 'Reliable Taxi Services in '.$d['name'],
                'intro' => sprintf('One-way drops, round trips and local rides across %s, with pickups from %s, %s and %s.',
                    $d['name'], $pickups[0], $pickups[1], $pickups[2]),
                'content' => $this->content($d),
                'summary' => $d['summary'] ?? null,
                'pickup_locations' => $pickups,
                'popular_destinations' => $d['destinations'] ?? null,
                'drop_locations' => $local['drop_locations'],
                'nearest_airport' => $d['airport'],
                'related_districts' => $d['nearby'],
                'hero_media_id' => isset($d['photo']) ? MediaSeeder::id($d['photo']) : null,
                'image_alt' => 'Taxi service in '.$d['name'],
                'seo_title' => 'Taxi Service in '.$d['name'],
                'seo_description' => sprintf('Book one-way, round-trip and local taxis in %s with One Way Taxi — pickups from %s, %s and more. See fares and book your trip.',
                    $d['name'], $pickups[0], $pickups[1]),
                'is_published' => true,
                // After the Tamil Nadu districts (sort 0) in every list.
                'sort_order' => 1,
            ]);

            // Link the existing booking city of the same name, so its existing routes and fares appear on the page.
            $page->cities()->sync(City::whereIn('name', $d['cities'] ?? [])->pluck('id')->all());

            foreach ($local['faqs'] as $i => $faq) {
                $page->districtFaqs()->create($faq + ['sort_order' => $i + 1, 'is_active' => true]);
            }
            foreach ((array) $local['popular_routes'] as $i => $route) {
                $page->districtRoutes()->create(['from_location' => $route['from'], 'to_location' => $route['to'], 'sort_order' => $i + 1, 'is_active' => true]);
            }
        }
    }
}
