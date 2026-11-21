<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\DistrictPage;
use Illuminate\Database\Seeder;

/**
 * Creates the 38 Tamil Nadu district landing pages from database/seeders/data/tamil-nadu-districts.php.
 * Safe to re-run: pages are only created when missing, so content edited in Admin → District pages is kept.
 */
class DistrictPageSeeder extends Seeder
{
    /** Taxi photography rotated across districts (neighbouring districts get different hero images). */
    protected const HERO_POOL = [
        'routes-sedan-highway-sunset.jpg', 'about-driver-with-passenger.jpg', 'service-white-suv-indian-highway.jpg',
        'why-chauffeur-opening-door.jpg', 'service-airport-traveller.jpg', 'cta-mumbai-taxi-driver.jpg',
        'service-corporate-rear-seat.jpg', 'rental-chauffeur-luxury-suv.jpg', 'service-couple-loading-luggage.jpg',
        'services-chauffeur-door.jpg', 'partner-driver-at-wheel.jpg', 'corporate-executive-arrival.jpg',
        'trip-one-way-white-sedan-sunrise.jpg',
    ];

    public function run(): void
    {
        $districts = require __DIR__.'/data/tamil-nadu-districts.php';
        $extras = require __DIR__.'/data/tamil-nadu-district-extras.php';
        $drops = require __DIR__.'/data/tamil-nadu-district-drops.php';
        $hqs = array_map(fn (array $d) => $d['hq'], $districts);
        $index = 0;

        foreach ($districts as $slug => $d) {
            $pooled = MediaSeeder::id(self::HERO_POOL[$index % count(self::HERO_POOL)]);
            $index++;
            $extra = $extras[$slug] ?? [];
            // District landmark photo (falls back to the rotated taxi photo when the file is not registered).
            $hero = (isset($extra['photo']) ? MediaSeeder::id($extra['photo']) : null) ?? $pooled;

            $local = $this->localContent($d, $extra, $drops[$slug] ?? [], $hqs);

            $page = DistrictPage::firstOrNew(['slug' => $slug]);
            if ($page->exists) {
                // Existing pages only gain what is still empty; editor changes are never overwritten.
                $updates = array_filter([
                    // FAQs and routes of existing districts are managed in Admin → District Management (rows), never re-seeded.
                    'drop_locations' => $page->drop_locations ? null : $local['drop_locations'],
                    'summary' => $page->summary ? null : ($extra['summary'] ?? null),
                    'popular_destinations' => $page->popular_destinations ? null : ($extra['destinations'] ?? null),
                    // Swap the previously seeded taxi photo for the district landmark (only if it is still the seeded one).
                    'hero_media_id' => ($page->hero_media_id === null || (int) $page->hero_media_id === (int) $pooled) && $hero !== $pooled ? $hero : null,
                ], fn ($v) => $v !== null);
                if ($updates) {
                    $page->update($updates);
                }

                continue;
            }

            $pickups = $d['pickups'];
            $page->fill([
                'name' => $d['name'],
                'state' => 'Tamil Nadu',
                'headquarters' => $d['hq'],
                'region' => $d['region'],
                'heading' => 'Reliable Taxi Services in '.$d['name'],
                'intro' => sprintf('One-way drops, round trips and local rides across %s district, with pickups from %s, %s and %s.',
                    $d['name'], $pickups[0], $pickups[1], $pickups[2]),
                'content' => $this->content($d),
                'summary' => $extra['summary'] ?? null,
                'pickup_locations' => $pickups,
                'popular_destinations' => $extra['destinations'] ?? null,
                'nearest_airport' => $d['airport'],
                'related_districts' => $d['nearby'],
                'hero_media_id' => $hero,
                'seo_title' => 'Taxi Service in '.$d['name'],
                'seo_description' => sprintf('Book one-way, round-trip and local taxis in %s with One Way Taxi — pickups from %s, %s and more. See fares and book your trip.',
                    $d['name'], $pickups[0], $pickups[1]),
                'is_published' => true,
                'sort_order' => 0,
                'drop_locations' => $local['drop_locations'],
            ])->save();

            $cityIds = City::whereIn('name', $d['cities'] ?? [])->pluck('id')->all();
            $page->cities()->sync($cityIds);

            // Draft FAQs and routes as district rows (editable in Admin → District Management → FAQs / Routes).
            foreach ($local['faqs'] as $i => $faq) {
                $page->districtFaqs()->create($faq + ['sort_order' => $i + 1, 'is_active' => true]);
            }
            foreach ((array) $local['popular_routes'] as $i => $route) {
                $page->districtRoutes()->create(['from_location' => $route['from'], 'to_location' => $route['to'], 'sort_order' => $i + 1, 'is_active' => true]);
            }
        }
    }

    /**
     * Drop points, curated routes and draft FAQs. Every answer is built from the district's own facts (its travel
     * patterns, airport and rail access, pickup points) and makes no fare, office or guaranteed-availability claims:
     * trips are requested online and confirmed by phone, which is how the booking system works.
     *
     * @return array{drop_locations: list<string>, popular_routes: list<array{from: string, to: string}>, faqs: list<array{question: string, answer: string}>}
     */
    protected function localContent(array $d, array $extra, array $drops, array $hqs = []): array
    {
        $name = $d['name'];
        $from = $d['hq'];
        $routes = $this->curatedRoutes($d, $extra, $hqs);
        $pickups = $d['pickups'];
        $pickupList = implode(', ', array_slice($pickups, 0, -1)).' and '.end($pickups);

        return [
            'drop_locations' => $drops ?: null,
            'popular_routes' => $routes ?: null,
            'faqs' => [
                [
                    'question' => 'Can I book a one way taxi from '.$name.'?',
                    'answer' => 'Yes. '.$d['trips'].' On a one-way booking you pay for the distance you travel, not for the driver’s return. Enter your pickup and drop to see the fare estimate, and our team confirms the trip by phone.',
                ],
                [
                    'question' => 'Which airport and railway stations are closest to '.$name.'?',
                    'answer' => $d['access'].' You can book an airport or station drop as a One Way trip.',
                ],
                [
                    'question' => 'Where can I be picked up in '.$name.'?',
                    'answer' => 'Common pickup points include '.$pickupList.'. You can also give your home, hotel or office address — the exact pickup point is confirmed with you when our team calls.',
                ],
                [
                    'question' => 'Can I book a local taxi or a car with driver for the day in '.$name.'?',
                    'answer' => 'You can request a Local trip for point-to-point rides within '.$name.', or a Rental for a car with driver by the hour or package. Our team confirms availability and the fare for your date before the trip.',
                ],
            ],
        ];
    }

    /**
     * Intercity routes from the district headquarters: the nearest airport, the neighbouring districts' headquarters
     * and Chennai — deliberately different from the "nearby destinations" list so the two sections never repeat.
     *
     * @return list<array{from: string, to: string}>
     */
    protected function curatedRoutes(array $d, array $extra, array $hqs): array
    {
        $from = $d['hq'];
        $airport = trim(explode(' / ', $d['airport'])[0]);
        $targets = array_merge([$airport], array_map(fn ($slug) => $hqs[$slug] ?? null, $d['nearby']), ['Chennai']);
        $skip = array_map('strtolower', array_merge([$from], $extra['destinations'] ?? []));
        $routes = [];
        foreach (array_filter($targets) as $to) {
            if (! in_array(strtolower($to), $skip, true)) {
                $routes[strtolower($to)] = ['from' => $from, 'to' => $to];
            }
        }

        return array_values($routes);
    }

    /** District-specific article: three sections built from that district's own facts. */
    protected function content(array $d): string
    {
        $e = fn (string $text) => e($text);

        return '<h2>Taxi service in '.$e($d['name']).'</h2>'
            .'<p>'.$e($d['about']).'</p>'
            .'<h2>One-way and outstation trips from '.$e($d['name']).'</h2>'
            .'<p>'.$e($d['trips']).' With one-way pricing you pay only for the distance you travel, not for the driver’s return.</p>'
            .'<h2>Airport and rail connections</h2>'
            .'<p>'.$e($d['access']).'</p>';
    }
}
