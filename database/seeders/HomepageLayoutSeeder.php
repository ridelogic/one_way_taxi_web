<?php

namespace Database\Seeders;

use App\Models\FareRule;
use App\Models\HeroSlide;
use App\Models\MediaAsset;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\VehicleCategory;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Homepage layout v2 (slider + tabbed booking widget + multi-section homepage).
 *
 * Safe to re-run: new records are created only when missing and the one-time layout changes
 * (section order, header menu) are applied once, tracked by the `homepage_layout_version` setting.
 * DEMONSTRATION content — replace in the admin panel.
 */
class HomepageLayoutSeeder extends Seeder
{
    private const VERSION = 16;

    public function run(): void
    {
        $this->seedSlides();
        $this->seedServices();
        $this->seedMiniVehicle();
        $sections = $this->seedSections();

        $applied = (int) SiteSetting::where('key', 'homepage_layout_version')->value('value');
        if ($applied < 2) {
            $this->applyLayout($sections);
            $this->applyNavigation();
        }
        if ($applied < 3) {
            $this->applyBrightRedesign();
        }
        if ($applied < 4) {
            $this->applyTripCards();
        }
        if ($applied < 5) {
            $this->applyMockupDesign();
        }
        if ($applied < 6) {
            $this->applyBookingPanel();
        }
        if ($applied < 7) {
            $this->applyTealBookingHero();
        }
        if ($applied < 8) {
            $this->applyPremiumHero();
        }
        if ($applied < 9) {
            $this->applyForestGoldPalette();
        }
        if ($applied < 10) {
            $this->applyCarFreeHero();
        }
        if ($applied < 11) {
            $this->applyTakeoffScene();
        }
        if ($applied < 12) {
            $this->applyMidnightRedesign();
        }
        if ($applied < 13) {
            $this->applyAirportTaxiHero();
        }
        if ($applied < 14) {
            $this->applyBlueReferenceDesign();
        }
        if ($applied < 15) {
            $this->applyVisibleHeroTaxi();
        }
        if ($applied < 16) {
            $this->applyReferencePalette();
        }
        if ($applied < self::VERSION) {
            SiteSetting::updateOrCreate(['key' => 'homepage_layout_version'], ['value' => (string) self::VERSION, 'group' => 'system']);
            app(Settings::class)->flush();
        }
    }

    /**
     * v5 — homepage matching the supplied design mockup: photographic hero with the SUV scene,
     * stats bar, booking card with tabs, horizontal route cards with map/scenic art, five-car fleet row.
     */
    private function applyMockupDesign(): void
    {
        $home = Page::where('slug', 'home')->firstOrFail();
        $section = fn (string $key) => PageSection::where('page_id', $home->id)->where('key', $key)->first();
        $merge = function (string $key, array $attributes, array $content = []) use ($section) {
            if ($s = $section($key)) {
                $s->update($attributes + ['content' => array_merge($s->content ?? [], $content)]);
            }
        };

        HeroSlide::query()->ordered()->first()?->update([
            'eyebrow' => 'India’s trusted one-way taxi service',
            'heading' => 'Your Journey.',
            'highlight' => 'Your Way.',
            'subheading' => 'One Way Taxi Across India',
            'subtitle' => 'Comfortable rides, transparent fares and hassle-free booking for your one-way journey.',
            'media_id' => MediaSeeder::id('design-hero-scene.webp'),
            'primary_label' => null, 'primary_url' => null, 'secondary_label' => null, 'secondary_url' => null,
            'is_active' => true,
        ]);

        $merge('hero', [], ['items' => [
            ['icon' => 'rupee', 'title' => 'Transparent Fares', 'text' => ''],
            ['icon' => 'user', 'title' => 'Professional Drivers', 'text' => ''],
            ['icon' => 'headset', 'title' => '24/7 Customer Support', 'text' => ''],
        ]]);
        $merge('stats', ['is_active' => true], ['items' => [
            ['icon' => 'users', 'value' => '50,000+', 'label' => 'Happy Customers'],
            ['icon' => 'map-pin', 'value' => '500+', 'label' => 'Routes Across India'],
            ['icon' => 'star', 'value' => '4.8/5', 'label' => 'Customer Rating'],
            ['icon' => 'award', 'value' => '5+', 'label' => 'Years of Service'],
        ]]);
        $merge('booking', ['title' => 'Book Your One-Way Ride', 'subtitle' => 'Get instant fare estimate for your journey'], [
            'comfort_message' => 'Safe · Comfortable · Affordable',
            'cta_label' => 'GET A QUOTE',
            'items' => [
                ['icon' => 'rupee', 'title' => 'No Hidden Charges', 'text' => ''],
                ['icon' => 'shield-check', 'title' => 'Best Price Guarantee', 'text' => ''],
                ['icon' => 'zap', 'title' => 'Quick Confirmation', 'text' => ''],
            ],
        ]);
        $merge('routes', ['title' => 'Most Booked One-Way Taxi Routes', 'subtitle' => null], [
            'eyebrow' => 'Popular routes & fares', 'limit' => 3, 'button_label' => 'View All Routes', 'button_url' => '/routes',
            'map_media_id' => MediaSeeder::id('design-india-map.png'), 'map_title' => 'Serving Across', 'map_highlight' => 'India',
            'scenic_media_id' => MediaSeeder::id('design-scenic-journeys.webp'),
        ]);
        $merge('fleet', ['title' => 'Comfortable Vehicles for', 'subtitle' => 'Choose from our well-maintained fleet for a safe and comfortable ride.'], [
            'eyebrow' => 'Our fleet', 'title_highlight' => 'Every Journey', 'button_label' => 'View All Vehicles', 'limit' => 5,
        ]);

        $order = ['hero', 'booking', 'stats', 'routes', 'fleet', 'why_choose', 'services', 'how_it_works', 'service_areas', 'testimonials', 'cta'];
        foreach ($order as $i => $key) {
            PageSection::where('page_id', $home->id)->where('key', $key)->update(['sort_order' => $i + 1]);
        }

        // Cut-out vehicle images shown on white cards (object-fit: contain)
        MediaAsset::where('disk', 'demo')->where('path', 'like', 'design-%.png')->update(['object_fit' => 'contain']);

        // Fleet: names, order and photos from the mockup; Tempo Traveller added as a sample category.
        $fleet = [
            'mini' => ['Hatchback / Mini', 'design-fleet-hatchback.png', 4, 2, 1],
            'economy-sedan' => ['Sedan', 'design-fleet-sedan.png', 4, 3, 2],
            'suv' => ['SUV', 'design-fleet-suv.png', 7, 4, 3],
            'innova-crysta' => ['Innova Crysta', 'design-fleet-innova.png', 7, 4, 4],
            'premium-sedan' => [null, null, null, null, 6],
        ];
        foreach ($fleet as $slug => [$name, $image, $seats, $bags, $order]) {
            VehicleCategory::where('slug', $slug)->update(array_filter([
                'name' => $name, 'media_id' => $image ? MediaSeeder::id($image) : null,
                'passenger_capacity' => $seats, 'luggage_capacity' => $bags, 'sort_order' => $order,
            ], fn ($v) => $v !== null));
        }
        $tempo = VehicleCategory::firstOrCreate(['slug' => 'tempo-traveller'], [
            'name' => 'Tempo Traveller', 'category' => 'Van', 'example_models' => 'Force Tempo Traveller or similar',
            'media_id' => MediaSeeder::id('design-fleet-tempo.png'), 'passenger_capacity' => 12, 'luggage_capacity' => 8,
            'short_description' => 'Spacious 9–12 seater for groups, pilgrimages and family tours.',
            'description' => 'Spacious 9–12 seater for groups, pilgrimages and family tours. Exact model is subject to availability.',
            'features' => ['Air conditioned', 'Up to 12 passengers', '8 bags', 'Push-back seats (subject to availability)'],
            'sort_order' => 5, 'is_active' => true,
        ]);
        FareRule::firstOrCreate(['vehicle_category_id' => $tempo->id], [
            'base_fare' => '0.00', 'per_km_rate' => '26.00', 'minimum_km' => 250, 'minimum_fare' => '0.00', 'driver_allowance' => '600.00',
            'additional_charges' => [
                ['label' => 'Toll charges', 'amount' => null, 'note' => 'as applicable on the route'],
                ['label' => 'Inter-state permit', 'amount' => null, 'note' => 'when crossing state borders'],
                ['label' => 'Parking fees', 'amount' => null, 'note' => 'as applicable'],
            ],
            'notes' => 'SAMPLE fare rule — verify before going live.', 'is_active' => true,
        ]);

        // Header menu from the mockup (inactive links are kept).
        $header = [['Home', '/'], ['About Us', '/about'], ['Services', '/services'], ['Routes', '/routes'], ['Fleet', '/fleet'], ['Corporate', '/corporate'], ['Contact', '/contact']];
        $keep = [];
        foreach ($header as $n => [$label, $url]) {
            $keep[] = NavigationItem::updateOrCreate(['menu' => 'header', 'url' => $url], ['label' => $label, 'sort_order' => $n + 1, 'is_active' => true])->id;
        }
        NavigationItem::where('menu', 'header')->whereNotIn('id', $keep)->update(['is_active' => false]);
    }

    /**
     * v6 — wide booking panel below the hero (red header with the taxi SUV, trip tabs, five fields).
     * Only fills keys that are not set yet, so CMS edits are kept.
     */
    private function applyBookingPanel(): void
    {
        $home = Page::where('slug', 'home')->firstOrFail();
        $booking = PageSection::where('page_id', $home->id)->where('key', 'booking')->first();

        $booking?->update(['content' => array_merge([
            'panel_media_id' => MediaSeeder::id('hero-suv-black.webp'),
            'submit_label' => 'SUBMIT',
            'note' => 'No payment now — our team confirms by phone.',
        ], $booking->content ?? [])]);
    }

    /**
     * v9 — forest-green and golden-yellow brand colours (light hero). Only replaces the previous seeded values.
     */
    /**
     * v10 — car-free hero: the airport scene stands on its own (no SUV cut-out).
     * Only previous seeded defaults are changed, so CMS edits are kept.
     */
    private function applyCarFreeHero(): void
    {
        $slide = HeroSlide::query()->ordered()->first();
        if ($slide && $slide->foreground_media_id && $slide->foreground_media_id === MediaSeeder::id('hero-suv-black.webp')) {
            $slide->update(['foreground_media_id' => null]);
        }
    }

    /**
     * v16 — reference palette: primary blue #0D6EFD, dark navy #062B55 (bright blue #009DFF and light blue
     * #EAF4FF are fixed CSS tokens). Each colour changes only while it still holds the previous seeded value.
     */
    private function applyReferencePalette(): void
    {
        foreach (['brand_gold' => ['#1769E0', '#0D6EFD'], 'brand_green' => ['#102A43', '#062B55'], 'brand_green_fresh' => ['#1D5A8C', '#0D6EFD']] as $key => [$old, $new]) {
            SiteSetting::where('key', $key)->where('value', $old)->update(['value' => $new]);
        }
        app(Settings::class)->flush();
    }

    /**
     * v15 — the white taxi in the hero photo stays in view above the booking panel: the scene's focal point moves
     * from the sky (36%, 50%) to the car (72%, 88%), and its stored size is corrected to the actual file.
     * Only applies while the focal point is still the previous default.
     */
    private function applyVisibleHeroTaxi(): void
    {
        $scene = MediaAsset::where('disk', 'demo')->where('path', 'hero-airport-taxi-golden-hour.jpg')->first();
        if (! $scene) {
            return;
        }

        $changes = [];
        if ($scene->focal_x === 36 && $scene->focal_y === 50) {
            $changes += ['focal_x' => 72, 'focal_y' => 88];
        }
        if ($size = @getimagesize(public_path('images/demo/'.$scene->path))) {
            $changes += ['width' => $size[0], 'height' => $size[1]];
        }
        $scene->update($changes);
    }

    /**
     * v14 — navy and blue homepage matching the reference design: "Comfortable Rides for Every Journey" hero,
     * taxi search panel, four service cards, why-choose grid, statistics strip, three steps, five-car fleet,
     * Pan-India coverage, app banner, reviews and the scenic journey banner. The section order is applied once;
     * every text, colour and image changes only while it still holds the previous seeded default.
     * Statistics stay marked unverified: they are illustrative until the business confirms them.
     */
    private function applyBlueReferenceDesign(): void
    {
        $home = Page::where('slug', 'home')->firstOrFail();
        $find = fn (string $key) => PageSection::where('page_id', $home->id)->where('key', $key)->first();
        $brand = (string) (SiteSetting::where('key', 'business_name')->value('value') ?: 'One Way Taxi');

        SiteSetting::where('key', 'brand_gold')->where('value', '#FF7900')->update(['value' => '#1769E0']);
        app(Settings::class)->flush();

        // Section order and visibility (all still editable in Admin → Homepage sections).
        $order = ['hero', 'booking', 'services', 'why_choose', 'stats', 'how_it_works', 'fleet', 'service_areas', 'app', 'testimonials', 'cta'];
        foreach ($order as $i => $key) {
            PageSection::where('page_id', $home->id)->where('key', $key)->update(['sort_order' => $i + 1, 'is_active' => true]);
        }
        PageSection::where('page_id', $home->id)->whereNotIn('key', $order)
            ->update(['is_active' => false, 'sort_order' => DB::raw('sort_order + '.count($order))]);

        // Updates a section only while its title is still the previous seeded default.
        $update = function (string $key, ?string $oldTitle, array $attributes, array $content = []) use ($find) {
            $section = $find($key);
            if ($section && ($oldTitle === null || $section->title === $oldTitle)) {
                $section->update($attributes + ['content' => array_merge($section->content ?? [], $content)]);
            }
        };

        $hero = $find('hero');
        if ($hero && array_column($hero->content['items'] ?? [], 'title') === ['Safe & Reliable Travel', '24/7 Service', 'Across India']) {
            $hero->update(['content' => array_merge($hero->content, ['items' => [
                ['icon' => 'shield-check', 'title' => 'Safe & Secure Travel', 'text' => ''],
                ['icon' => 'headset', 'title' => '24/7 Customer Support', 'text' => ''],
                ['icon' => 'map', 'title' => 'Pan India Service', 'text' => ''],
            ]])]);
        }

        $booking = $find('booking');
        if ($booking && ($booking->content['submit_label'] ?? null) === 'SUBMIT') {
            $booking->update(['title' => 'Search for a Taxi', 'content' => array_merge($booking->content, ['submit_label' => 'Search Taxi'])]);
        }

        $update('services', 'Our Services', [
            'title' => 'Our Taxi Services',
            'subtitle' => 'From quick city drops to long outstation drives, choose the ride that fits your trip.',
        ], ['eyebrow' => 'What we offer', 'limit' => 4]);
        // The homepage shows four services; the others stay on the Services page.
        Service::whereIn('slug', ['local-taxi', 'rentals'])->update(['show_on_home' => false]);

        $update('why_choose', 'Why Travel With Us', [
            'title' => "Why Choose {$brand}?",
            'subtitle' => "{$brand} makes intercity and outstation travel simple, with upfront fare estimates and a team that confirms every booking personally.",
        ], [
            'eyebrow' => 'Why choose us',
            'body' => 'Whether it is an airport pickup, a one-way drop to another city or a family trip to the hills, we plan the ride around you and keep you informed from booking to drop-off.',
            'button_label' => 'Learn More',
            'button_url' => '/about',
            'items' => [
                ['icon' => 'rupee', 'title' => 'Transparent Pricing', 'text' => 'See your fare estimate upfront, with tolls and permits explained before you travel.'],
                ['icon' => 'badge-check', 'title' => 'Verified Drivers', 'text' => 'Experienced, courteous drivers familiar with highways and ghat roads.'],
                ['icon' => 'wrench', 'title' => 'Well Maintained Vehicles', 'text' => 'Clean, air-conditioned cars that are checked before every trip.'],
                ['icon' => 'headset', 'title' => '24/7 Support', 'text' => 'Talk to a real person before, during and after your journey.'],
            ],
        ]);

        $stats = $find('stats');
        if ($stats && array_column($stats->content['items'] ?? [], 'value') === ['50,000+', '500+', '4.8/5', '5+']) {
            $stats->update(['content' => array_merge($stats->content, [
                'is_verified' => false,
                'note' => 'Illustrative figures from the design reference — replace with verified numbers and tick “verified”.',
                'items' => [
                    ['icon' => 'smile', 'value' => '10,000+', 'label' => 'Happy Customers'],
                    ['icon' => 'users', 'value' => '500+', 'label' => 'Active Drivers'],
                    ['icon' => 'map-pin', 'value' => '100+', 'label' => 'Cities Covered'],
                    ['icon' => 'route', 'value' => '1,00,000+', 'label' => 'Successful Rides'],
                ],
            ])]);
        }

        $how = $find('how_it_works');
        if ($how && $how->subtitle === 'Book your taxi in four simple steps.') {
            $how->update(['subtitle' => 'Book your taxi in three simple steps.', 'content' => array_merge($how->content ?? [], ['items' => [
                ['icon' => 'map-pin', 'title' => 'Enter Trip Details', 'text' => 'Add your pickup and drop locations, journey date and time.'],
                ['icon' => 'car', 'title' => 'Choose a Vehicle', 'text' => 'Compare vehicles and fare estimates, then pick the one that suits you.'],
                ['icon' => 'check-circle', 'title' => 'Confirm & Ride', 'text' => 'Our team confirms your booking by phone and your driver arrives on time.'],
            ]])]);
        }

        $update('fleet', 'Comfortable Vehicles for', [
            'title' => 'Our Fleet',
            'subtitle' => 'Well-maintained vehicles for solo trips, families and groups.',
        ], ['title_highlight' => '', 'eyebrow' => 'Choose your ride']);

        $update('service_areas', 'Where We Operate', [
            'title' => 'Taxi Service Across India',
            'subtitle' => 'Book one-way, round-trip and outstation taxis between the cities we serve — and ask us about any other route.',
            'media_id' => MediaSeeder::id('design-india-map.png'),
        ], ['eyebrow' => 'Pan India coverage', 'limit' => 4]);

        $update('app', 'Book Faster With Our App', [
            'title' => 'Book Anytime, Anywhere',
            'subtitle' => 'Book rides, track your trips and manage your bookings from your phone.',
        ], ['eyebrow' => 'Mobile app']);

        $update('testimonials', 'What Our Customers Say', [
            'subtitle' => 'Reviews from passengers who travelled with us.',
        ], ['eyebrow' => 'Testimonials', 'limit' => 6]);

        $update('cta', 'Ready for your next journey?', [
            'title' => 'Ready for Your Next Journey?',
            'subtitle' => 'Book a comfortable, reliable taxi in minutes. Our team confirms every ride by phone.',
            'media_id' => MediaSeeder::id('hero-munnar-highway.jpg'),
        ], ['primary_label' => 'Book Your Taxi', 'primary_url' => '/book']);

        $slide = HeroSlide::query()->ordered()->first();
        if ($slide && $slide->heading === 'Book Your' && $slide->highlight === '*One-Way* Ride') {
            $slide->update([
                'eyebrow' => null,
                'heading' => 'Comfortable Rides',
                'highlight' => 'for *Every Journey*',
                'subheading' => null,
                'subtitle' => 'Book reliable one-way, round-trip, outstation and airport taxis with upfront fare estimates — our team confirms every ride.',
            ]);
        }
    }

    /**
     * v13 — dark cinematic hero: airport-at-golden-hour scene with the ONE WAY TAXI SUV, the supporting line
     * restored, and a CMS-managed benefits strip directly below the booking form. Previous defaults only.
     */
    private function applyAirportTaxiHero(): void
    {
        $home = Page::where('slug', 'home')->firstOrFail();

        SiteSetting::where('key', 'brand_green')->where('value', '#0B2942')->update(['value' => '#102A43']);
        app(Settings::class)->flush();

        if ($slide = HeroSlide::query()->ordered()->first()) {
            $changes = [];
            $scenes = array_filter([
                MediaSeeder::id('hero-airport-terminal-sunset.jpg'),
                MediaSeeder::id('hero-airport-takeoff-golden-hour.jpg'),
                MediaSeeder::id('hero-airport-terminal-golden-hour.jpg'),
            ]);
            if (in_array($slide->media_id, $scenes, true) && ($taxi = MediaSeeder::id('hero-airport-taxi-golden-hour.jpg'))) {
                $changes['media_id'] = $taxi;
            }
            if ($slide->subtitle === 'Get instant fare estimate for your journey') {
                $changes['subtitle'] = 'Comfortable rides, transparent fares and hassle-free booking for your one-way journey.';
            }
            $slide->update($changes);
        }

        // Destinations and FAQs join the homepage flow (they stay editable/hideable in Admin → Homepage sections).
        PageSection::where('page_id', $home->id)->whereIn('key', ['destinations', 'faqs'])->update(['is_active' => true]);
        // ...and the booking call to action closes the page.
        PageSection::where('page_id', $home->id)->where('key', 'cta')
            ->update(['sort_order' => (int) PageSection::where('page_id', $home->id)->max('sort_order') + 1]);

        if (! PageSection::where('page_id', $home->id)->where('key', 'benefits')->exists()) {
            $after = (int) PageSection::where('page_id', $home->id)->where('key', 'booking')->value('sort_order');
            PageSection::where('page_id', $home->id)->where('sort_order', '>', $after)->increment('sort_order');
            PageSection::create([
                'page_id' => $home->id, 'key' => 'benefits', 'type' => 'benefits', 'title' => 'Why book with us',
                'sort_order' => $after + 1, 'is_active' => true,
                'content' => ['items' => [
                    ['icon' => 'rupee', 'title' => 'Transparent Pricing', 'text' => 'One-way fares with no return charge.'],
                    ['icon' => 'car', 'title' => 'Wide Fleet Options', 'text' => 'Hatchbacks to Innova Crysta and tempo travellers.'],
                    ['icon' => 'headset', 'title' => '24/7 Customer Support', 'text' => 'Talk to our team at any hour.'],
                    ['icon' => 'map', 'title' => 'Pan India Coverage', 'text' => 'Intercity trips across India on request.'],
                ]],
            ]);
        }
    }

    /**
     * v12 — midnight navy / orange redesign: brand colours, "Book Your *One-Way* Ride" hero copy and the
     * airport-at-sunset scene. Each value changes only while it still holds the previous seeded default.
     */
    private function applyMidnightRedesign(): void
    {
        foreach (['brand_green' => ['#075E3B', '#102A43'], 'brand_gold' => ['#FFB900', '#FF7900'], 'brand_green_fresh' => ['#0B7A4B', '#1D5A8C']] as $key => [$old, $new]) {
            SiteSetting::where('key', $key)->where('value', $old)->update(['value' => $new]);
        }
        app(Settings::class)->flush();

        $sunset = MediaAsset::where('disk', 'demo')->where('path', 'hero-airport-terminal-sunset.jpg')->first();
        if ($sunset && $sunset->focal_x === 8 && $sunset->focal_y === 45) {
            $sunset->update(['focal_x' => 62, 'focal_y' => 50]);
        }

        $slide = HeroSlide::query()->ordered()->first();
        if (! $slide) {
            return;
        }

        $defaults = [
            'heading' => ['Your Journey.', 'Book Your'],
            'highlight' => ['Our Priority.', '*One-Way* Ride'],
            'subheading' => ['One Way Taxi Across India', null],
            'subtitle' => ['Comfortable rides, transparent fares and hassle-free booking for your one-way journey.', 'Get instant fare estimate for your journey'],
        ];
        $changes = [];
        foreach ($defaults as $field => [$old, $new]) {
            if ($slide->{$field} === $old) {
                $changes[$field] = $new;
            }
        }
        if ($sunset && in_array($slide->media_id, [MediaSeeder::id('hero-airport-takeoff-golden-hour.jpg'), MediaSeeder::id('hero-airport-terminal-golden-hour.jpg')], true)) {
            $changes['media_id'] = $sunset->id;
        }
        $slide->update($changes);
    }

    /**
     * v11 — target-design hero: airliner taking off over the terminal at golden hour, and the original
     * "One Way Taxi Across India" copy restored where an earlier v10 run had replaced it.
     */
    private function applyTakeoffScene(): void
    {
        $slide = HeroSlide::query()->ordered()->first();
        if (! $slide) {
            return;
        }

        $changes = [];
        if ($slide->media_id === MediaSeeder::id('hero-airport-terminal-golden-hour.jpg')) {
            $changes['media_id'] = MediaSeeder::id('hero-airport-takeoff-golden-hour.jpg') ?? $slide->media_id;
        }
        if ($slide->subheading === null && $slide->subtitle === 'Get instant fare estimate for your journey') {
            $changes['subheading'] = 'One Way Taxi Across India';
            $changes['subtitle'] = 'Comfortable rides, transparent fares and hassle-free booking for your one-way journey.';
        }
        $slide->update($changes);
    }

    private function applyForestGoldPalette(): void
    {
        foreach (['brand_gold' => ['#FFB914', '#FFB900'], 'brand_green' => ['#004B50', '#075E3B'], 'brand_green_fresh' => ['#0E7C74', '#0B7A4B']] as $key => [$old, $new]) {
            SiteSetting::where('key', $key)->where('value', $old)->update(['value' => $new]);
        }
        app(Settings::class)->flush();
    }

    /**
     * v8 — premium navy/teal/gold homepage: the hero holds the booking form, airport-at-sunset scene with the SUV
     * cut-out, "Our Priority." highlight, three benefits, new brand colours and real fleet photos.
     * Every change applies only while the value is still the previous seeded default, so CMS edits are kept.
     */
    private function applyPremiumHero(): void
    {
        $home = Page::where('slug', 'home')->firstOrFail();

        if ($slide = HeroSlide::query()->ordered()->first()) {
            $changes = [];
            if ($slide->highlight === 'Your Way.') {
                $changes['highlight'] = 'Our Priority.';
            }
            if ($slide->media_id === MediaSeeder::id('design-hero-scene.webp')) {
                $changes['media_id'] = MediaSeeder::id('hero-airport-terminal-golden-hour.jpg') ?? $slide->media_id;
            }
            $slide->update($changes);
        }

        $hero = PageSection::where('page_id', $home->id)->where('key', 'hero')->first();
        if ($hero && array_column($hero->items(), 'title') === ['Transparent Fares', 'Professional Drivers', '24/7 Customer Support']) {
            $hero->update(['content' => array_merge($hero->content ?? [], ['items' => [
                ['icon' => 'shield-check', 'title' => 'Safe & Reliable Travel', 'text' => ''],
                ['icon' => 'clock', 'title' => '24/7 Service', 'text' => ''],
                ['icon' => 'map-pin', 'title' => 'Across India', 'text' => ''],
            ]])]);
        }

        foreach (['brand_gold' => ['#D4A017', '#FFB914'], 'brand_green' => ['#14532D', '#004B50'], 'brand_green_fresh' => ['#2F855A', '#0E7C74']] as $key => [$old, $new]) {
            SiteSetting::where('key', $key)->where('value', $old)->update(['value' => $new]);
        }

        // Real, credited vehicle photos instead of the mock-up renders.
        $photos = [
            'design-fleet-hatchback.png' => 'fleet-mini.jpg',
            'design-fleet-sedan.png' => 'fleet-economy-sedan.jpg',
            'design-fleet-suv.png' => 'fleet-suv-fortuner.jpg',
            'design-fleet-innova.png' => 'fleet-innova.jpg',
            'design-fleet-tempo.png' => 'fleet-tempo-traveller.jpg',
        ];
        foreach ($photos as $render => $photo) {
            if (($from = MediaSeeder::id($render)) && ($to = MediaSeeder::id($photo))) {
                VehicleCategory::where('media_id', $from)->update(['media_id' => $to]);
            }
        }

        app(Settings::class)->flush();
    }

    /**
     * v7 — deep-teal booking hero: airport scene photo, gold title highlight and three benefit indicators.
     * New keys are only filled when missing; the benefits replace the old seeded trust indicators only.
     */
    private function applyTealBookingHero(): void
    {
        $home = Page::where('slug', 'home')->firstOrFail();
        $booking = PageSection::where('page_id', $home->id)->where('key', 'booking')->first();
        if (! $booking) {
            return;
        }

        $content = array_merge([
            'scene_media_id' => MediaSeeder::id('booking-airport-runway-sunrise.jpg'),
            'title_highlight' => 'One-Way',
        ], $booking->content ?? []);

        $oldIndicators = ['No Hidden Charges', 'Best Price Guarantee', 'Quick Confirmation'];
        if (empty($content['items']) || array_column($content['items'], 'title') === $oldIndicators) {
            $content['items'] = [
                ['icon' => 'shield-check', 'title' => 'Safe & Reliable Travel', 'text' => ''],
                ['icon' => 'clock', 'title' => '24/7 Service', 'text' => ''],
                ['icon' => 'map', 'title' => 'Across India', 'text' => ''],
            ];
        }

        $booking->update(['content' => $content]);
    }

    /**
     * v4 — the hero photo is replaced by a booking container with two photo cards
     * (One Way / Outstation) followed by the form. Applied once.
     */
    private function applyTripCards(): void
    {
        $home = Page::where('slug', 'home')->firstOrFail();
        $booking = PageSection::where('page_id', $home->id)->where('key', 'booking')->first();

        if ($booking) {
            $booking->update(['content' => array_merge($booking->content ?? [], [
                'one_way_title' => 'One Way',
                'one_way_subtitle' => 'Affordable one-way rides across India',
                'one_way_media_id' => MediaSeeder::id('trip-one-way-white-sedan-sunrise.jpg'),
                'outstation_title' => 'Outstation',
                'outstation_subtitle' => 'Comfortable rides for longer journeys',
                'outstation_media_id' => MediaSeeder::id('hero-white-suv-mountains.jpg'),
                'cta_label' => 'GET A QUOTE',
                'note' => 'No payment now — our team confirms by phone.',
            ])]);
        }

        HeroSlide::query()->ordered()->first()?->update(['primary_url' => '#book-ride']);
    }

    /**
     * v3 — bright ivory / gold / green redesign: single hero with the SUV photo, homepage sections
     * A–H from the brief, six home services and a six-item header menu. Applied once.
     */
    private function applyBrightRedesign(): void
    {
        $home = Page::where('slug', 'home')->firstOrFail();

        // Hero: first slide becomes the main hero, the others are kept but hidden.
        $first = HeroSlide::query()->ordered()->first();
        if ($first) {
            $first->update([
                'eyebrow' => 'Premium one-way taxi service',
                'heading' => 'Your Journey.',
                'highlight' => 'Your Way.',
                'subtitle' => 'Reliable one-way taxi services across India. Comfortable rides, transparent fares, and hassle-free booking.',
                'media_id' => MediaSeeder::id('hero-white-suv-mountains.jpg') ?? $first->media_id,
                'foreground_media_id' => null,
                'primary_label' => 'Book Your Ride', 'primary_url' => '#book-ride',
                'secondary_label' => 'Explore Routes', 'secondary_url' => '/routes',
                'text_align' => 'left', 'is_active' => true,
            ]);
            HeroSlide::whereKeyNot($first->id)->update(['is_active' => false]);
        }

        $section = fn (string $key) => PageSection::where('page_id', $home->id)->where('key', $key)->first();
        $merge = function (string $key, array $attributes, array $content = []) use ($section) {
            if ($s = $section($key)) {
                $s->update($attributes + ['content' => array_merge($s->content ?? [], $content)]);
            }
        };

        $merge('hero', [], ['items' => [
            ['icon' => 'rupee', 'title' => 'Transparent Fares', 'text' => 'Estimates before you book'],
            ['icon' => 'badge-check', 'title' => 'Professional Drivers', 'text' => 'Experienced & verified'],
            ['icon' => 'headset', 'title' => 'Customer Support', 'text' => 'Real people, any time'],
        ]]);
        $merge('booking', ['title' => 'Book Your Ride', 'subtitle' => 'Get an instant fare estimate for your one-way trip'], ['cta_label' => 'GET A QUOTE']);
        $merge('routes', ['title' => 'Popular Routes & Fares', 'subtitle' => 'Most booked one-way taxi routes, with starting fares.'], ['limit' => 6, 'button_label' => 'View All Routes', 'button_url' => '/routes']);
        $merge('fleet', ['title' => 'Our Fleet', 'subtitle' => 'Clean, air-conditioned vehicles for every group size.'], ['button_label' => 'View Full Fleet']);
        $merge('why_choose', ['title' => 'Why Travel With Us', 'subtitle' => 'Everything you need for a comfortable, stress-free journey.', 'media_id' => MediaSeeder::id('hero-munnar-highway.jpg')], ['items' => [
            ['icon' => 'rupee', 'title' => 'Transparent pricing', 'text' => 'See your fare estimate upfront. Tolls and permits are explained before you travel.'],
            ['icon' => 'badge-check', 'title' => 'Professional drivers', 'text' => 'Experienced on highways and ghat roads, courteous and punctual.'],
            ['icon' => 'sparkles', 'title' => 'Clean vehicles', 'text' => 'Air-conditioned cars, cleaned and checked before every trip.'],
            ['icon' => 'map', 'title' => 'Nationwide route coverage', 'text' => 'One-way trips between cities across states — ask us about any route.'],
            ['icon' => 'headset', 'title' => 'Reliable customer support', 'text' => 'Talk to a real person before, during and after your journey.'],
        ]]);
        $merge('services', ['title' => 'Our Services', 'subtitle' => 'Whatever the journey, we have a ride for it.']);
        $merge('how_it_works', ['title' => 'How It Works', 'subtitle' => 'Book your taxi in four simple steps.'], ['items' => [
            ['icon' => 'map-pin', 'title' => 'Choose your route', 'text' => 'Enter your pickup and destination cities.'],
            ['icon' => 'car', 'title' => 'Select your vehicle', 'text' => 'Pick the car that suits your group and luggage.'],
            ['icon' => 'send', 'title' => 'Submit your enquiry', 'text' => 'See your estimate and send your request in seconds.'],
            ['icon' => 'check-circle', 'title' => 'Receive confirmation', 'text' => 'Our team calls to confirm your booking and fare.'],
        ]]);
        $merge('service_areas', ['title' => 'Where We Operate', 'subtitle' => 'Cities currently served by our one-way and outstation cabs.'], ['body' => 'Travelling somewhere else? Send an enquiry and we’ll tell you if we can arrange it.']);
        $merge('testimonials', ['title' => 'What Our Customers Say', 'subtitle' => 'Feedback from passengers on our rides.']);
        $merge('cta', ['title' => 'Ready for your next journey?', 'subtitle' => 'Get a fare estimate in seconds, or talk to our team about your trip.', 'media_id' => null], [
            'primary_label' => 'Book Your Ride', 'primary_url' => '/book', 'secondary_label' => 'Call Us', 'secondary_url' => '',
        ]);

        $order = ['hero', 'booking', 'routes', 'fleet', 'why_choose', 'services', 'how_it_works', 'service_areas', 'testimonials', 'cta',
            'stats', 'vehicle_carousel', 'safety', 'corporate', 'partner', 'app', 'faqs', 'about', 'contact', 'destinations'];
        $visible = array_slice($order, 0, 10);
        foreach ($order as $i => $key) {
            PageSection::where('page_id', $home->id)->where('key', $key)->update(['sort_order' => $i + 1, 'is_active' => in_array($key, $visible, true)]);
        }

        // Six homepage services with the names from the brief.
        $services = ['one-way-taxi' => 'One Way Taxi', 'outstation-taxi' => 'Outstation Taxi', 'airport-transfers' => 'Airport Transfers',
            'local-taxi' => 'Local Travel', 'corporate-taxi' => 'Corporate Travel', 'rentals' => 'Rental Trips'];
        Service::query()->update(['show_on_home' => false]);
        $i = 0;
        foreach ($services as $slug => $title) {
            Service::where('slug', $slug)->update(['title' => $title, 'show_on_home' => true, 'sort_order' => ++$i]);
        }
        Service::whereNotIn('slug', array_keys($services))->increment('sort_order', 10);

        // Header menu: Home, About, Services, Routes, Fleet, Contact (others kept, inactive).
        $header = [['Home', '/'], ['About', '/about'], ['Services', '/services'], ['Routes', '/routes'], ['Fleet', '/fleet'], ['Contact', '/contact']];
        $keep = [];
        foreach ($header as $n => [$label, $url]) {
            $keep[] = NavigationItem::updateOrCreate(['menu' => 'header', 'url' => $url], ['label' => $label, 'sort_order' => $n + 1, 'is_active' => true])->id;
        }
        NavigationItem::where('menu', 'header')->whereNotIn('id', $keep)->update(['is_active' => false]);
        NavigationItem::where('url', '/services/corporate-taxi')->update(['label' => 'Corporate Travel']);
        NavigationItem::where('url', '/services/local-taxi')->update(['label' => 'Local Travel']);
        NavigationItem::where('url', '/services/rentals')->update(['label' => 'Rental Trips']);
    }

    private function seedSlides(): void
    {
        if (HeroSlide::exists()) {
            return;
        }

        $slides = [
            [
                'eyebrow' => 'PREMIUM ONE-WAY TAXI SERVICE', 'heading' => 'YOUR JOURNEY. YOUR WAY.', 'highlight' => 'ONE WAY TAXI',
                'subtitle' => 'Reliable one-way rides. Transparent fares. Comfortable journeys across India.',
                'media' => 'hero-sunset-mountains.jpg', 'foreground' => null,
                'primary' => ['Book a Trip', '#book-ride'], 'secondary' => ['View Routes', '/routes'], 'overlay' => 35,
            ],
            [
                'eyebrow' => 'OUTSTATION & HILL STATIONS', 'heading' => 'Weekend in the hills?', 'highlight' => 'We’ll drive.',
                'subtitle' => 'Experienced ghat-road drivers for Ooty, Kodaikanal, Munnar and beyond.',
                'media' => 'hero-munnar-highway.jpg', 'foreground' => null,
                'primary' => ['Plan an Outstation Trip', '/book?trip_type=outstation'], 'secondary' => ['Popular Routes', '/routes'], 'overlay' => 50,
            ],
            [
                'eyebrow' => 'AIRPORT & STATION TRANSFERS', 'heading' => 'On time for every flight,', 'highlight' => 'day or night.',
                'subtitle' => 'Pickups and drops planned around your flight or train schedule.',
                'media' => 'airport-chennai.jpg', 'foreground' => null,
                'primary' => ['Book Airport Taxi', '/services/airport-transfers'], 'secondary' => null, 'overlay' => 55,
            ],
            [
                'eyebrow' => 'HOURLY RENTALS', 'heading' => 'A car and driver,', 'highlight' => 'by the hour.',
                'subtitle' => 'Packages for meetings, shopping and sightseeing within the city.',
                'media' => 'scenic-city-highway.jpg', 'foreground' => null,
                'primary' => ['Book a Rental', '/book?trip_type=rental'], 'secondary' => null, 'overlay' => 50,
            ],
        ];

        foreach ($slides as $i => $s) {
            HeroSlide::create([
                'eyebrow' => $s['eyebrow'],
                'heading' => $s['heading'],
                'highlight' => $s['highlight'],
                'subtitle' => $s['subtitle'],
                'media_id' => MediaSeeder::id($s['media']),
                'foreground_media_id' => $s['foreground'] ? MediaSeeder::id($s['foreground']) : null,
                'primary_label' => $s['primary'][0] ?? null,
                'primary_url' => $s['primary'][1] ?? null,
                'secondary_label' => $s['secondary'][0] ?? null,
                'secondary_url' => $s['secondary'][1] ?? null,
                'overlay_strength' => $s['overlay'],
                'text_align' => 'left',
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }
    }

    private function seedServices(): void
    {
        $services = [
            ['rentals', 'Hourly Rentals', 'clock', 'A car with driver for 4, 8 or 12 hours within the city.', 'scenic-city-highway.jpg',
                ['4, 8 and 12-hour packages', 'Multiple stops included', 'Sedan, SUV or Innova', 'Extra hours billed transparently'],
                '<p>Book a car with a professional driver for a few hours or the whole day. Rental packages are ideal for meetings, shopping, hospital visits and city sightseeing.</p><p>Rental rates depend on your city and vehicle. Choose “Rental” in the booking form and our team will call you with an exact quote.</p>'],
            ['bike-taxi', 'Bike Taxi', 'zap', 'Quick, affordable two-wheeler rides for solo travellers — in select cities.', 'service-bike-taxi.jpg',
                ['Beat city traffic', 'Helmet provided for the passenger', 'Ideal for short solo trips', 'Available in select cities'],
                '<p>Bike taxis are a fast, low-cost way to cover short distances in busy cities.</p><p><strong>Availability:</strong> bike taxi service is offered only in select cities and is subject to local regulations. Send an enquiry with your pickup city and our team will confirm whether a ride is available.</p>'],
            ['auto-taxi', 'Auto Taxi', 'navigation', 'Book an auto rickshaw for short local trips — in select cities.', 'service-auto-taxi.jpg',
                ['Affordable short-distance rides', 'Room for up to 3 passengers', 'Local drivers who know the city', 'Available in select cities'],
                '<p>Auto rickshaws are a convenient option for short local trips and last-mile connections to stations and bus stands.</p><p><strong>Availability:</strong> auto taxi bookings are offered in select cities only. Send an enquiry and our team will confirm availability and the fare.</p>'],
        ];

        $order = (int) Service::max('sort_order');
        foreach ($services as [$slug, $title, $icon, $short, $image, $highlights, $body]) {
            Service::firstOrCreate(['slug' => $slug], [
                'title' => $title, 'icon' => $icon, 'short_description' => $short, 'highlights' => $highlights,
                'body' => $body, 'media_id' => MediaSeeder::id($image), 'show_on_home' => false, 'is_active' => true,
                'sort_order' => ++$order, 'seo_title' => $title, 'seo_description' => $short,
            ]);
        }
    }

    private function seedMiniVehicle(): void
    {
        $mini = VehicleCategory::firstOrCreate(['slug' => 'mini'], [
            'name' => 'Mini', 'category' => 'Hatchback', 'example_models' => 'Maruti Wagon R, Celerio or similar',
            'media_id' => MediaSeeder::id('fleet-mini.jpg'), 'passenger_capacity' => 4, 'luggage_capacity' => 1,
            'short_description' => 'Compact and economical — perfect for short city-to-city drops.',
            'description' => 'Compact and economical — perfect for short city-to-city drops. Exact model is subject to availability.',
            'features' => ['Air conditioned', '4 passengers', '1 large bag', 'Lowest per-km rate'],
            'sort_order' => 0, 'is_active' => true,
        ]);

        FareRule::firstOrCreate(['vehicle_category_id' => $mini->id], [
            'base_fare' => '0.00', 'per_km_rate' => '11.00', 'minimum_km' => 130, 'minimum_fare' => '0.00',
            'driver_allowance' => '400.00',
            'additional_charges' => [
                ['label' => 'Toll charges', 'amount' => null, 'note' => 'as applicable on the route'],
                ['label' => 'Inter-state permit', 'amount' => null, 'note' => 'when crossing state borders'],
                ['label' => 'Parking fees', 'amount' => null, 'note' => 'as applicable'],
            ],
            'notes' => 'SAMPLE fare rule — verify before going live.', 'is_active' => true,
        ]);
    }

    /** @return array<string, PageSection> */
    private function seedSections(): array
    {
        $home = Page::where('slug', 'home')->firstOrFail();

        $new = [
            'vehicle_carousel' => [
                'title' => 'Choose Your Ride', 'subtitle' => 'From budget hatchbacks to family MUVs — and two- and three-wheelers in select cities.',
                'content' => ['button_label' => 'View All Cabs', 'button_url' => '/fleet', 'items' => [
                    ['title' => 'Mini', 'text' => 'Wagon R, Celerio or similar', 'badge' => '4 seats', 'link' => '/fleet/mini', 'image' => MediaSeeder::id('fleet-mini.jpg')],
                    ['title' => 'Sedan', 'text' => 'Swift Dzire, Etios or similar', 'badge' => '4 seats', 'link' => '/fleet/economy-sedan', 'image' => MediaSeeder::id('fleet-economy-sedan.jpg')],
                    ['title' => 'Premium Sedan', 'text' => 'Honda City, Verna or similar', 'badge' => '4 seats', 'link' => '/fleet/premium-sedan', 'image' => MediaSeeder::id('fleet-premium-sedan.jpg')],
                    ['title' => 'SUV', 'text' => 'XUV700, Fortuner or similar', 'badge' => '6 seats', 'link' => '/fleet/suv', 'image' => MediaSeeder::id('fleet-suv-fortuner.jpg')],
                    ['title' => 'Innova Crysta', 'text' => 'Spacious family MUV', 'badge' => '7 seats', 'link' => '/fleet/innova-crysta', 'image' => MediaSeeder::id('fleet-innova.jpg')],
                    ['title' => 'Rentals', 'text' => '4, 8 or 12-hour packages', 'badge' => 'Hourly', 'link' => '/services/rentals', 'image' => MediaSeeder::id('scenic-city-highway.jpg')],
                    ['title' => 'Auto Taxi', 'text' => 'Short local trips', 'badge' => 'Select cities', 'link' => '/services/auto-taxi', 'image' => MediaSeeder::id('service-auto-taxi.jpg')],
                    ['title' => 'Bike Taxi', 'text' => 'Quick solo rides', 'badge' => 'Select cities', 'link' => '/services/bike-taxi', 'image' => MediaSeeder::id('service-bike-taxi.jpg')],
                ]],
            ],
            'safety' => [
                'title' => 'Your Safety Comes First', 'subtitle' => 'Simple, practical measures on every ride.',
                'media' => 'partner-driver.jpg',
                'content' => ['items' => [
                    ['icon' => 'shield-check', 'title' => 'Verified drivers', 'text' => 'Licence and identity checks before a driver joins.'],
                    ['icon' => 'clipboard-check', 'title' => 'Vehicle checks', 'text' => 'Cars are inspected and cleaned before trips.'],
                    ['icon' => 'phone', 'title' => 'Trip details shared', 'text' => 'You receive driver and vehicle details before pickup.'],
                    ['icon' => 'headset', 'title' => 'Support line', 'text' => 'Call our team at any time during your journey.'],
                ]],
            ],
            'service_areas' => [
                'title' => 'Where We Operate', 'subtitle' => 'One-way and outstation cabs between these cities — and many more on request.',
                'content' => ['body' => 'Don’t see your city? Send an enquiry — we arrange one-way and outstation trips across India through our partner network.'],
            ],
            'corporate' => [
                'title' => 'Corporate Taxi Solutions', 'subtitle' => 'Dependable transport for teams, guests and events.',
                'media' => 'fleet-premium-sedan.jpg',
                'content' => [
                    'body' => 'From daily employee commutes to airport pickups for visiting clients, we provide professional drivers, clean vehicles and a single point of contact for your business.',
                    'cta_label' => 'Enquire for Your Company', 'cta_url' => '/corporate',
                    'items' => [
                        ['icon' => 'wallet', 'title' => 'Monthly billing', 'text' => 'Consolidated invoices on request'],
                        ['icon' => 'users', 'title' => 'Dedicated manager', 'text' => 'One contact for all bookings'],
                        ['icon' => 'plane', 'title' => 'Airport & guest pickups', 'text' => 'Meet-and-greet for visitors'],
                        ['icon' => 'car', 'title' => 'Premium vehicles', 'text' => 'Sedans, SUVs and MUVs'],
                    ],
                ],
            ],
            'partner' => [
                'title' => 'Become a Partner', 'subtitle' => 'Drivers, owner-drivers and fleet owners — grow with us.',
                'media' => 'partner-driver.jpg',
                'content' => [
                    'body' => 'Register your interest to drive with One Way Taxi or attach your vehicles to our network. Our partner team will call you to explain the process, documents and earnings.',
                    'cta_label' => 'Register as a Partner', 'cta_url' => '/become-a-partner',
                    'items' => [
                        ['icon' => 'route', 'title' => 'Intercity trips', 'text' => 'Long-distance one-way bookings'],
                        ['icon' => 'wallet', 'title' => 'Clear payouts', 'text' => 'Transparent trip-wise earnings'],
                        ['icon' => 'headset', 'title' => 'Partner support', 'text' => 'Help from our operations team'],
                    ],
                ],
            ],
            'app' => [
                'title' => 'Book Faster With Our App', 'subtitle' => 'Fare estimates, bookings and trip updates in your pocket.',
                'content' => [
                    'body' => 'Save your frequent routes, get instant fare estimates and manage your bookings on the go.',
                    'items' => [['text' => 'Instant fare estimates'], ['text' => 'Saved pickup locations'], ['text' => 'Booking history and support']],
                ],
            ],
            'contact' => [
                'title' => 'Talk to Us', 'subtitle' => 'Questions, quotes or special requests — we’re here to help.',
                'content' => ['body' => 'Call, email or send us a message and our team will get back to you.', 'show_form' => true],
            ],
        ];

        $sections = [];
        foreach ($new as $key => $s) {
            $sections[$key] = PageSection::firstOrCreate(['page_id' => $home->id, 'key' => $key], [
                'type' => $key,
                'title' => $s['title'],
                'subtitle' => $s['subtitle'],
                'content' => $s['content'],
                'media_id' => isset($s['media']) ? MediaSeeder::id($s['media']) : null,
                'sort_order' => 999,
                'is_active' => true,
            ]);
        }

        return $sections;
    }

    /** One-time: section order, autoplay and sections superseded by the new layout. */
    private function applyLayout(array $sections): void
    {
        $home = Page::where('slug', 'home')->firstOrFail();
        $order = ['hero', 'booking', 'stats', 'vehicle_carousel', 'routes', 'services', 'why_choose', 'safety', 'how_it_works',
            'service_areas', 'corporate', 'partner', 'app', 'testimonials', 'about', 'faqs', 'contact', 'fleet', 'destinations', 'cta'];

        foreach ($order as $i => $key) {
            PageSection::where('page_id', $home->id)->where('key', $key)->update(['sort_order' => $i + 1]);
        }

        // Replaced by the carousel, service areas and contact sections (still editable / re-enable any time).
        PageSection::where('page_id', $home->id)->whereIn('key', ['fleet', 'destinations', 'cta'])->update(['is_active' => false]);

        $hero = PageSection::where('page_id', $home->id)->where('key', 'hero')->first();
        if ($hero) {
            $content = $hero->content ?? [];
            $content['autoplay_seconds'] ??= 6;
            $hero->update(['title' => 'Hero slider', 'content' => $content]);
        }

        $routes = PageSection::where('page_id', $home->id)->where('key', 'routes')->first();
        if ($routes) {
            $routes->update(['content' => array_merge($routes->content ?? [], ['limit' => 5])]);
        }
    }

    /** One-time: header menu requested by the business; previous links are deactivated, not deleted. */
    private function applyNavigation(): void
    {
        $header = [
            ['Home', '/'], ['About Us', '/about'], ['Corporate', '/corporate'], ['Cabs', '/fleet'], ['Bike Taxi', '/services/bike-taxi'],
            ['Auto Taxi', '/services/auto-taxi'], ['Book a Trip', '/book'], ['Become a Partner', '/become-a-partner'], ['Download App', '/download-app'],
        ];

        $keep = [];
        foreach ($header as $i => [$label, $url]) {
            $item = NavigationItem::updateOrCreate(['menu' => 'header', 'url' => $url], ['label' => $label, 'sort_order' => $i + 1, 'is_active' => true]);
            $keep[] = $item->id;
        }
        NavigationItem::where('menu', 'header')->whereNotIn('id', $keep)->update(['is_active' => false]);

        $extra = [
            'footer_company' => [['Corporate', '/corporate', 6], ['Become a Partner', '/become-a-partner', 7], ['Download App', '/download-app', 8]],
            'footer_services' => [['Hourly Rentals', '/services/rentals', 6], ['Bike Taxi', '/services/bike-taxi', 7], ['Auto Taxi', '/services/auto-taxi', 8]],
        ];
        foreach ($extra as $menu => $items) {
            foreach ($items as [$label, $url, $order]) {
                NavigationItem::firstOrCreate(['menu' => $menu, 'url' => $url], ['label' => $label, 'sort_order' => $order, 'is_active' => true]);
            }
        }
    }
}
