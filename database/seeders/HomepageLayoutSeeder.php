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
    private const VERSION = 37;

    /**
     * About Us banner photo (v35–v37): a young Indian woman travelling comfortably in a car. Safe to call on its own —
     * replaces only previously seeded banner photos (chauffeur, Munnar taxi, Kodaikanal taxi), never an admin choice.
     */
    public static function applyAboutBannerPhoto(): bool
    {
        $new = MediaSeeder::id('about-banner-woman-passenger.jpg');
        $previous = array_values(array_filter([MediaSeeder::id('why-chauffeur-opening-door.jpg'), MediaSeeder::id('hero-munnar-highway.jpg'), MediaSeeder::id('about-banner-kodaikanal-innova.jpg')]));
        if (! $new || $previous === []) {
            return false;
        }

        return Page::where('slug', 'about')->whereIn('hero_media_id', $previous)->update(['hero_media_id' => $new]) > 0;
    }

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
        if ($applied < 17) {
            $this->applyLogoPalette();
        }
        if ($applied < 18) {
            $this->applyPremiumRedesign();
        }
        if ($applied < 19) {
            $this->applyFleetRemovalAndCorporatePhotos();
        }
        if ($applied < 20) {
            $this->applyDistrictPages();
        }
        if ($applied < 21) {
            $this->applyPremiumAutomotiveHero();
        }
        if ($applied < 23) {
            // v22 introduced the standard names; v23 extends the same (idempotent) step to the service SEO fields.
            $this->applyStandardTripTypeNames();
        }
        if ($applied < 24) {
            $this->applyCleanerHomepage();
        }
        if ($applied < 25) {
            // v25 — the site owner's own hero banner replaces the stock hero photo (only if the previous one is still set).
            $slide = HeroSlide::query()->ordered()->first();
            $banner = MediaSeeder::id('onewaytrip-hero-banner.jpg');
            $previous = array_filter([MediaSeeder::id('hero-black-sedan-city-skyline.jpg'), MediaSeeder::id('hero-airport-taxi-golden-hour.jpg')]);
            if ($slide && $banner && in_array((int) $slide->media_id, array_map('intval', $previous), true)) {
                $slide->update(['media_id' => $banner]);
            }
        }
        if ($applied < 26) {
            // v26 — About section photo: a smiling woman passenger (only if the previous seeded photo is still set).
            $home = Page::where('slug', 'home')->first();
            $about = $home ? PageSection::where('page_id', $home->id)->where('key', 'about')->first() : null;
            $new = MediaSeeder::id('about-woman-passenger.jpg');
            $previous = array_filter([MediaSeeder::id('about-indian-taxi-driver.jpg'), MediaSeeder::id('about-driver-with-passenger.jpg')]);
            if ($about && $new && in_array((int) $about->media_id, array_map('intval', $previous), true)) {
                $about->update(['media_id' => $new]);
            }
        }
        if ($applied < 27) {
            // v27 — light footer: Quick Links and Taxi Services trimmed to the agreed lists. Extra items are only
            // hidden (still editable in Admin → Menus); nothing is deleted and no URL changes.
            NavigationItem::where('menu', 'footer_company')->whereIn('url', ['/download-app', '/tamil-nadu'])->update(['is_active' => false]);
            foreach (['/services/one-way-taxi', '/services/outstation-taxi', '/services/local-taxi', '/services/rentals', '/services/airport-transfers'] as $i => $url) {
                NavigationItem::where('menu', 'footer_services')->where('url', $url)->update(['sort_order' => $i + 1, 'is_active' => true]);
            }
            NavigationItem::where('menu', 'footer_services')->whereIn('url', ['/services/corporate-taxi', '/services/bike-taxi', '/services/auto-taxi'])->update(['is_active' => false]);
        }
        if ($applied < 28) {
            $this->applyVideoPromotionsHomepage();
        }
        if ($applied < 29) {
            $this->applyHomepageMediaSections();
        }
        if ($applied < 30) {
            // v30 — new About and Why Choose photos (young Indian women passengers); only where the previous seeded photo is still set.
            $home = Page::where('slug', 'home')->first();
            foreach (['about' => ['about-college-woman-passenger.jpg', ['about-woman-passenger.jpg', 'about-indian-taxi-driver.jpg', 'about-driver-with-passenger.jpg']],
                'why_choose' => ['why-woman-passenger-smiling.jpg', ['why-chauffeur-opening-door.jpg']]] as $key => [$newFile, $oldFiles]) {
                $section = $home ? PageSection::where('page_id', $home->id)->where('key', $key)->first() : null;
                $new = MediaSeeder::id($newFile);
                $old = array_map('intval', array_filter(array_map(fn ($f) => MediaSeeder::id($f), $oldFiles)));
                if ($section && $new && in_array((int) $section->media_id, $old, true)) {
                    $section->update(['media_id' => $new]);
                }
            }
        }
        if ($applied < 31) {
            $this->applyReferenceHomepageMockup();
        }
        if ($applied < 32) {
            // v32 — About photo: the smiling young woman passenger (mockup); only while the previous seeded photo is set.
            $home = Page::where('slug', 'home')->first();
            $about = $home ? PageSection::where('page_id', $home->id)->where('key', 'about')->first() : null;
            $new = MediaSeeder::id('why-woman-passenger-smiling.jpg');
            $old = MediaSeeder::id('about-college-woman-passenger.jpg');
            if ($about && $new && $old && (int) $about->media_id === (int) $old) {
                $about->update(['media_id' => $new]);
            }
        }
        if ($applied < 33) {
            $this->applyMockupAboutWhyText();
        }
        if ($applied < 34) {
            // v34 — final homepage mockup: the mockup's woman-in-car photo for About, no statistics strip below the
            // services (record kept, only hidden), "Our services" label. Each change applies only to seeded values.
            $home = Page::where('slug', 'home')->first();
            if ($home) {
                $about = PageSection::where('page_id', $home->id)->where('key', 'about')->first();
                $new = MediaSeeder::id('about-woman-in-car-mockup.jpg');
                $previous = array_map('intval', array_filter([MediaSeeder::id('about-college-woman-passenger.jpg'), MediaSeeder::id('why-woman-passenger-smiling.jpg')]));
                if ($about && $new && in_array((int) $about->media_id, $previous, true)) {
                    $about->update(['media_id' => $new]);
                }
                PageSection::where('page_id', $home->id)->where('key', 'stats')->update(['is_active' => false]);
                $services = PageSection::where('page_id', $home->id)->where('key', 'services')->first();
                if ($services && ($services->content['eyebrow'] ?? null) === 'What we offer') {
                    $services->update(['content' => array_merge((array) $services->content, ['eyebrow' => 'Our services'])]);
                }
            }
        }
        if ($applied < 37) {
            // v35–v37 — About Us page banner: a young Indian woman travelling comfortably in a car replaces the
            // earlier seeded photos — only while the page still uses one of them.
            self::applyAboutBannerPhoto();
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
     * v24 — cleaner homepage: the "Most Booked One-Way Taxi Routes" and "Taxi Service Across India" sections are
     * switched off (records kept — they can be switched back on in Admin → Homepage sections; route and district
     * pages are unaffected), and the About section shows a smiling taxi driver at the wheel.
     */
    private function applyCleanerHomepage(): void
    {
        $home = Page::where('slug', 'home')->first();
        if (! $home) {
            return;
        }
        PageSection::where('page_id', $home->id)->whereIn('key', ['routes', 'service_areas'])->update(['is_active' => false]);

        $about = PageSection::where('page_id', $home->id)->where('key', 'about')->first();
        $old = MediaSeeder::id('about-driver-with-passenger.jpg');
        $new = MediaSeeder::id('about-indian-taxi-driver.jpg');
        if ($about && $new && (int) $about->media_id === (int) $old) {
            $about->update(['media_id' => $new]);
        }
    }

    /**
     * v22 — one set of trip type names everywhere: Local, Rental, One Way, Round Trip.
     * Service titles and footer labels follow them; the Local Taxi service now describes local city rides (it
     * previously duplicated Rental's hourly packages); the rental package "Full day (local)" becomes "Full day".
     * Each change applies only while the previous seeded text is still in place.
     */
    private function applyStandardTripTypeNames(): void
    {
        $services = [
            'outstation-taxi' => ['Outstation Taxi', 'Round Trip Taxi', null],
            'local-taxi' => ['Local Travel', 'Local Taxi', [
                'old_short' => 'Hourly packages for errands, meetings and sightseeing in the city.',
                'short' => 'Point-to-point rides within the city — station, bus stand, hospital, office or airport.',
                'old_body_start' => 'Need a car for a few hours?',
                'body' => '<p>Need to get across town? Book a Local taxi for point-to-point rides within the city — to the railway station, bus stand, hospital, office or airport — with a driver who knows the local roads.</p>'
                    .'<p>Local trips are priced by distance and time within the city. Share your pickup and drop and our team confirms the fare by phone before your ride.</p>'
                    .'<p>Need a car for several hours instead? Choose a <a href="/services/rentals">Rental</a> package.</p>',
            ]],
            'rentals' => ['Rental Trips', 'Rental Taxi', null],
        ];
        foreach ($services as $slug => [$oldTitle, $newTitle, $copy]) {
            $service = Service::where('slug', $slug)->first();
            if (! $service) {
                continue;
            }
            if ($service->title === $oldTitle) {
                $service->update(['title' => $newTitle]);
            }
            if ($copy && $service->short_description === $copy['old_short']) {
                $service->update(['short_description' => $copy['short']]);
            }
            if ($copy && str_starts_with(trim(strip_tags((string) $service->body)), $copy['old_body_start'])) {
                $service->update(['body' => $copy['body']]);
            }
        }

        // Service page SEO titles and descriptions follow the same names.
        foreach ([
            'outstation-taxi' => ['seo_title', 'Outstation Taxi Service', 'Round Trip Taxi Service'],
            'rentals' => ['seo_title', 'Hourly Rentals', 'Rental Taxi Service'],
            'local-taxi' => ['seo_description', 'Hourly packages for errands, meetings and sightseeing in the city.', 'Local taxi for point-to-point rides within the city — station, bus stand, hospital, office or airport.'],
        ] as $slug => [$column, $old, $new]) {
            Service::where('slug', $slug)->where($column, $old)->update([$column => $new]);
        }

        foreach (['Outstation Taxi' => 'Round Trip Taxi', 'Local Travel' => 'Local Taxi', 'Rental Trips' => 'Rental Taxi'] as $old => $new) {
            NavigationItem::where('label', $old)->update(['label' => $new]);
        }

        $packages = SiteSetting::where('key', 'rental_packages')->first();
        if ($packages && str_contains((string) $packages->value, 'Full day (local)')) {
            $packages->update(['value' => str_replace('Full day (local)', 'Full day', (string) $packages->value)]);
        }
        app(Settings::class)->flush();
    }

    /**
     * v21 — premium automotive homepage: brand colours red #E60000 / dark navy #080D18, the header menu
     * Home · Services · Routes & Fares · About Us · Contact, the "Find Your Perfect Taxi" hero with a black
     * sedan and city skyline, and the Tamil Nadu district grid directly below the hero.
     * Every change applies only while the previous seeded value is still in place.
     */
    private function applyPremiumAutomotiveHero(): void
    {
        foreach (['brand_gold' => ['#E30613', '#E60000'], 'brand_green' => ['#1F2326', '#080D18']] as $key => [$old, $new]) {
            SiteSetting::where('key', $key)->where('value', $old)->update(['value' => $new]);
        }

        // Header menu (Corporate stays in the footer).
        $header = ['/' => ['Home', 1], '/services' => ['Services', 2], '/routes' => ['Routes & Fares', 3], '/about' => ['About Us', 4], '/contact' => ['Contact', 5]];
        foreach ($header as $url => [$label, $order]) {
            NavigationItem::where('menu', 'header')->where('url', $url)->update(['label' => $label, 'sort_order' => $order, 'is_active' => true]);
        }
        NavigationItem::where('menu', 'header')->where('url', '/corporate')->update(['is_active' => false]);

        // Hero slide text and photo.
        $slide = HeroSlide::query()->ordered()->first();
        if ($slide && $slide->heading === 'Comfortable Rides') {
            $slide->update([
                'eyebrow' => 'Trusted taxi service across Tamil Nadu',
                'heading' => 'Find Your Perfect Taxi',
                'highlight' => 'for *Every Journey*',
                'subtitle' => 'Reliable, comfortable and affordable taxi services across Tamil Nadu and beyond.',
            ]);
        }
        if ($slide && (int) $slide->media_id === (int) MediaSeeder::id('hero-airport-taxi-golden-hour.jpg') && ($sedan = MediaSeeder::id('hero-black-sedan-city-skyline.jpg'))) {
            $slide->update(['media_id' => $sedan]);
        }

        $home = Page::where('slug', 'home')->first();
        if (! $home) {
            return;
        }
        $section = fn (string $key) => PageSection::where('page_id', $home->id)->where('key', $key)->first();

        // Hero highlights.
        $hero = $section('hero');
        $previous = ['Safe & Secure Travel', '24/7 Customer Support', 'Pan India Service'];
        if ($hero && array_column($hero->items(), 'title') === $previous) {
            $hero->update(['content' => array_merge($hero->content ?? [], ['items' => [
                ['icon' => 'shield-check', 'title' => 'Safe & Secure', 'text' => ''],
                ['icon' => 'badge-check', 'title' => 'Verified Drivers', 'text' => ''],
                ['icon' => 'clock', 'title' => 'On-Time Service', 'text' => ''],
            ]])]);
        }

        // District grid directly below the hero (and its booking panel).
        $districts = $section('districts');
        if ($districts) {
            PageSection::where('page_id', $home->id)->whereKeyNot($districts->id)->where('sort_order', '>=', 3)->increment('sort_order');
            $districts->update(['sort_order' => 3]);
            if ($districts->title === 'Taxi Services Across Tamil Nadu') {
                $districts->update([
                    'title' => 'Book Your Taxi Across Tamil Nadu',
                    'subtitle' => 'Explore reliable taxi services across all 38 districts of Tamil Nadu. Choose your district and book your journey with ease.',
                    'content' => array_merge($districts->content ?? [], ['eyebrow' => 'All 38 districts']),
                ]);
            }
        }
    }

    /**
     * v20 — Tamil Nadu district pages: a homepage "Taxi Services Across Tamil Nadu" section after the service
     * areas, and a footer link to /tamil-nadu. The pages themselves are created by DistrictPageSeeder.
     */
    private function applyDistrictPages(): void
    {
        $home = Page::where('slug', 'home')->first();
        if ($home) {
            $after = (int) (PageSection::where('page_id', $home->id)->where('key', 'service_areas')->value('sort_order') ?? 10);
            if (! PageSection::where('page_id', $home->id)->where('key', 'districts')->exists()) {
                // Make room directly after the service areas section.
                PageSection::where('page_id', $home->id)->where('sort_order', '>', $after)->increment('sort_order');
                PageSection::create([
                    'page_id' => $home->id, 'key' => 'districts', 'type' => 'districts',
                    'title' => 'Taxi Services Across Tamil Nadu',
                    'subtitle' => 'One-way, round-trip and local taxis in all 38 districts — choose yours to see routes, fares and pickup points.',
                    'content' => ['eyebrow' => 'District-wise service'],
                    'sort_order' => $after + 1, 'is_active' => true,
                ]);
            }
        }

        $order = (int) NavigationItem::where('menu', 'footer_company')->max('sort_order') + 1;
        NavigationItem::firstOrCreate(['menu' => 'footer_company', 'url' => '/tamil-nadu'], ['label' => 'Tamil Nadu Districts', 'sort_order' => $order, 'is_active' => true]);
    }

    /**
     * v33 — homepage mockup (OneWayTrip_Taxi_Homepage_Mockup.jpg): the short About paragraph, short benefit points,
     * the mockup's one-line Why Choose descriptions (car icon for vehicles) and the seated young woman passenger photo.
     * Every value changes only while it still equals the previous seeded text/photo, so admin edits are kept.
     */
    private function applyMockupAboutWhyText(): void
    {
        $home = Page::where('slug', 'home')->first();
        if (! $home) {
            return;
        }

        $about = PageSection::where('page_id', $home->id)->where('key', 'about')->first();
        if ($about) {
            $content = (array) $about->content;
            if (($content['body'] ?? null) === 'We make intercity travel simple and affordable. With one-way pricing you are never charged for the driver’s return journey, and every fare estimate is shown before you book. Our drivers know the highways and hill roads of South India, and our support team is a phone call away.') {
                $content['body'] = 'We make intercity travel simple and affordable with clear fares, verified drivers and reliable service across Tamil Nadu.';
            }
            if (array_column((array) ($content['items'] ?? []), 'text') === ['One-way fares with no return charges', 'Doorstep pickup from home, hotel or airport', 'Sedans, SUVs and Innova Crysta', 'Booking confirmed personally by our team']) {
                $content['items'] = [['text' => 'No return-fare charges'], ['text' => 'Doorstep pickup & drop'], ['text' => 'Sedans, SUVs & Innova'], ['text' => 'Every booking confirmed']];
            }
            $media = $about->media_id;
            if ((int) $media === (int) MediaSeeder::id('why-woman-passenger-smiling.jpg') && MediaSeeder::id('about-college-woman-passenger.jpg')) {
                $media = MediaSeeder::id('about-college-woman-passenger.jpg');
            }
            $about->update(['content' => $content, 'media_id' => $media]);
        }

        $why = PageSection::where('page_id', $home->id)->where('key', 'why_choose')->first();
        if ($why) {
            $content = (array) $why->content;
            $old = [
                'See your fare estimate upfront, with tolls and permits explained before you travel.',
                'Experienced, courteous drivers familiar with highways and ghat roads.',
                'Clean, air-conditioned cars that are checked before every trip.',
                'Talk to a real person before, during and after your journey.',
            ];
            if (array_column((array) ($content['items'] ?? []), 'text') === $old) {
                $content['items'] = [
                    ['icon' => 'rupee', 'title' => 'Transparent Pricing', 'text' => 'See your fare upfront, no hidden charges.'],
                    ['icon' => 'badge-check', 'title' => 'Verified Drivers', 'text' => 'Experienced and courteous drivers.'],
                    ['icon' => 'car', 'title' => 'Well Maintained Vehicles', 'text' => 'Clean, comfortable and AC cars.'],
                    ['icon' => 'headset', 'title' => '24/7 Support', 'text' => 'Get assistance anytime, anywhere.'],
                ];
                $why->update(['content' => $content]);
            }
        }
    }

    /**
     * v31 — homepage mockup: About + Why choose us directly below the hero, then the services; homepage service cards
     * One Way, Round Trip, Airport Transfers, Rental with vehicle photos. Each change applies only while the previous
     * seeded value is still in place (admin choices are kept). The hero is not touched.
     */
    private function applyReferenceHomepageMockup(): void
    {
        $home = Page::where('slug', 'home')->first();
        if ($home) {
            $keys = PageSection::where('page_id', $home->id)->orderBy('sort_order')->orderBy('id')->pluck('key')->all();
            $rest = array_values(array_diff($keys, ['hero', 'booking', 'about', 'services']));
            foreach (array_merge(['hero', 'booking', 'about', 'services'], $rest) as $i => $key) {
                PageSection::where('page_id', $home->id)->where('key', $key)->update(['sort_order' => $i + 1]);
            }
        }

        $onHome = Service::where('show_on_home', true)->orderBy('sort_order')->pluck('slug')->all();
        if ($onHome === ['one-way-taxi', 'outstation-taxi', 'local-taxi', 'rentals']) {
            Service::where('slug', 'local-taxi')->update(['show_on_home' => false, 'sort_order' => 5]);
            Service::where('slug', 'airport-transfers')->update(['show_on_home' => true, 'sort_order' => 3]);
            Service::where('slug', 'rentals')->update(['sort_order' => 4]);
        }

        foreach ([
            'one-way-taxi' => ['service-couple-loading-luggage.jpg', 'trip-one-way-white-sedan-sunrise.jpg'],
            'airport-transfers' => ['service-airport-traveller.jpg', 'hero-airport-taxi-golden-hour.jpg'],
            'rentals' => ['rental-chauffeur-luxury-suv.jpg', 'fleet-innova.jpg'],
        ] as $slug => [$old, $new]) {
            $oldId = MediaSeeder::id($old);
            $newId = MediaSeeder::id($new);
            if ($oldId && $newId) {
                Service::where('slug', $slug)->where('media_id', $oldId)->update(['media_id' => $newId]);
            }
        }
    }

    /**
     * v29 — Homepage Content: "Taxi Promotional Videos" and "Photo Gallery" sections, placed right after the featured
     * videos section. Each only renders once the admin adds matching homepage content (nothing sample is created).
     */
    private function applyHomepageMediaSections(): void
    {
        $home = Page::where('slug', 'home')->first();
        if (! $home) {
            return;
        }

        $after = (int) (PageSection::where('page_id', $home->id)->where('key', 'videos')->value('sort_order') ?? 0);
        $new = [
            ['key' => 'promo_videos', 'type' => 'promo_videos', 'title' => 'Taxi Promotional Videos', 'subtitle' => 'Our latest taxi service promotions and offers.', 'content' => ['eyebrow' => 'Promotions']],
            ['key' => 'gallery', 'type' => 'gallery', 'title' => 'Photo Gallery', 'subtitle' => 'Our cars, drivers and journeys across Tamil Nadu.', 'content' => ['eyebrow' => 'Gallery']],
        ];
        foreach ($new as $i => $section) {
            if (PageSection::where('page_id', $home->id)->where('key', $section['key'])->exists()) {
                continue;
            }
            $position = $after + $i + 1;
            PageSection::where('page_id', $home->id)->where('sort_order', '>=', $position)->increment('sort_order');
            PageSection::create($section + ['page_id' => $home->id, 'sort_order' => $position, 'is_active' => true]);
        }
    }

    /**
     * v28 — homepage flow for the video promotions release: hero + booking, the four trip-type service cards, why
     * choose us, promotional videos, all 38 districts, testimonials, mobile app and the final booking CTA. Sections
     * outside that list stay active, placed after the districts. The videos section only renders once an admin adds
     * a homepage video — no sample videos are created.
     */
    private function applyVideoPromotionsHomepage(): void
    {
        $home = Page::where('slug', 'home')->first();
        if (! $home) {
            return;
        }

        PageSection::firstOrCreate(['page_id' => $home->id, 'key' => 'videos'], [
            'type' => 'videos',
            'title' => 'Explore Our Taxi Experiences',
            'subtitle' => 'Real journeys, real customers and reliable taxi services.',
            'content' => ['eyebrow' => 'Video stories'],
            'sort_order' => 0, 'is_active' => true,
        ]);

        $order = ['hero', 'booking', 'services', 'why_choose', 'stats', 'videos', 'districts', 'about', 'how_it_works', 'testimonials', 'app', 'contact', 'cta'];
        foreach ($order as $i => $key) {
            PageSection::where('page_id', $home->id)->where('key', $key)->update(['sort_order' => $i + 1]);
        }
        PageSection::where('page_id', $home->id)->whereNotIn('key', $order)->where('sort_order', '<=', count($order))
            ->update(['sort_order' => DB::raw('sort_order + '.(count($order) + 10))]);

        // Homepage service cards: One Way, Round Trip, Local, Rental — only while the previous seeded selection is unchanged.
        $onHome = Service::where('show_on_home', true)->orderBy('sort_order')->pluck('slug')->all();
        if ($onHome === ['one-way-taxi', 'outstation-taxi', 'airport-transfers', 'corporate-taxi']) {
            Service::whereIn('slug', ['airport-transfers', 'corporate-taxi'])->update(['show_on_home' => false]);
            foreach (['one-way-taxi', 'outstation-taxi', 'local-taxi', 'rentals'] as $i => $slug) {
                Service::where('slug', $slug)->update(['show_on_home' => true, 'sort_order' => $i + 1]);
            }
            Service::where('slug', 'airport-transfers')->update(['sort_order' => 5]);
            Service::where('slug', 'corporate-taxi')->update(['sort_order' => 6]);
        }
    }

    /**
     * v19 — the website has no Fleet section: the homepage Fleet and vehicle-carousel sections are switched off and
     * the /fleet menu links hidden (records are kept, only deactivated; vehicle types still power booking and fares).
     * Corporate, partner, rental and routes photography is replaced, again only where the previous seeded photo is
     * still in place.
     */
    private function applyFleetRemovalAndCorporatePhotos(): void
    {
        NavigationItem::where('url', 'like', '/fleet%')->update(['is_active' => false]);

        $id = fn (string $file) => MediaSeeder::id($file);
        $swap = function ($model, string $column, ?string $oldFile, string $newFile) use ($id) {
            $new = $id($newFile);
            $current = $model?->{$column};
            if ($model && $new && ($oldFile === null ? $current === null : (int) $current === (int) $id($oldFile))) {
                $model->update([$column => $new]);
            }
        };

        $home = Page::where('slug', 'home')->first();
        if ($home) {
            $section = fn (string $key) => PageSection::where('page_id', $home->id)->where('key', $key)->first();
            foreach (['fleet', 'vehicle_carousel'] as $key) {
                $section($key)?->update(['is_active' => false]);
            }
            $swap($section('corporate'), 'media_id', 'fleet-premium-sedan.jpg', 'corporate-executive-arrival.jpg');
            $swap($section('partner'), 'media_id', 'partner-driver.jpg', 'partner-driver-at-wheel.jpg');
            $swap($section('routes'), 'media_id', null, 'routes-sedan-highway-sunset.jpg');
        }

        $swap(Service::where('slug', 'rentals')->first(), 'media_id', 'scenic-city-highway.jpg', 'rental-chauffeur-luxury-suv.jpg');
    }

    /**
     * v18 — premium redesign: taxi-service photography replaces the mountain and landscape scenery, and the
     * homepage gains its About, Popular routes and Contact sections in a clear story order.
     * Every image swap applies only while the record still shows the previous seeded photo, so images
     * chosen in the admin are kept.
     */
    private function applyPremiumRedesign(): void
    {
        $id = fn (string $file) => MediaSeeder::id($file);
        $swap = function ($model, string $column, string $oldFile, string $newFile) use ($id) {
            $old = $id($oldFile);
            $new = $id($newFile);
            if ($model && $new && (int) $model->{$column} === (int) $old) {
                $model->update([$column => $new]);
            }
        };

        // Service photos: the people and journeys each service is about.
        foreach ([
            'one-way-taxi' => ['hero-munnar-highway.jpg', 'service-couple-loading-luggage.jpg'],
            'outstation-taxi' => ['scenic-yercaud-ghat-road.jpg', 'service-white-suv-indian-highway.jpg'],
            'airport-transfers' => ['airport-chennai.jpg', 'service-airport-traveller.jpg'],
            'local-taxi' => ['scenic-city-highway.jpg', 'cta-mumbai-taxi-driver.jpg'],
            'corporate-taxi' => ['fleet-premium-sedan.jpg', 'service-corporate-rear-seat.jpg'],
        ] as $slug => [$old, $new]) {
            $swap(Service::where('slug', $slug)->first(), 'media_id', $old, $new);
        }

        $home = Page::where('slug', 'home')->first();
        if ($home) {
            $section = fn (string $key) => PageSection::where('page_id', $home->id)->where('key', $key)->first();
            $swap($section('about'), 'media_id', 'scenic-western-ghats.jpg', 'about-driver-with-passenger.jpg');
            $swap($section('why_choose'), 'media_id', 'hero-munnar-highway.jpg', 'why-chauffeur-opening-door.jpg');
            $swap($section('cta'), 'media_id', 'hero-munnar-highway.jpg', 'scenic-city-highway.jpg');

            // Story order: introduce the company, show what it offers, prove it, then help the visitor book.
            $order = ['about' => 3, 'services' => 4, 'why_choose' => 5, 'stats' => 6, 'how_it_works' => 7, 'fleet' => 8,
                'routes' => 9, 'service_areas' => 10, 'app' => 11, 'testimonials' => 12, 'contact' => 13, 'cta' => 14];
            foreach ($order as $key => $position) {
                $section($key)?->update(['sort_order' => $position]);
            }
            foreach (['about', 'routes', 'contact'] as $key) {
                $section($key)?->update(['is_active' => true]);
            }
        }

        $swap(Page::where('slug', 'about')->first(), 'hero_media_id', 'scenic-western-ghats.jpg', 'why-chauffeur-opening-door.jpg');
    }

    /**
     * v17 — palette from the official logo: primary red #E30613, charcoal #1F2326, deep red #B8030F for links
     * (the logo yellow #FFC20E and amber #FBA919 are fixed CSS tokens). Each colour changes only while it still
     * holds the previous seeded value, so colours customised in the admin are kept.
     */
    private function applyLogoPalette(): void
    {
        foreach (['brand_gold' => ['#0D6EFD', '#E30613'], 'brand_green' => ['#062B55', '#1F2326'], 'brand_green_fresh' => ['#0D6EFD', '#B8030F']] as $key => [$old, $new]) {
            SiteSetting::where('key', $key)->where('value', $old)->update(['value' => $new]);
        }
        app(Settings::class)->flush();
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
