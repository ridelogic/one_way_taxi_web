<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\TaxiRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_homepage_renders_reference_layout_from_the_database(): void
    {
        // Content from the seeded homepage (HomepageLayoutSeeder v14 navy/blue reference design).
        $this->get('/')
            ->assertOk()
            ->assertSee(':root{--color-gold:#E60000;--color-forest:#080D18;--color-fresh:#B8030F}', false)
            ->assertSeeInOrder(['Find Your Perfect Taxi', 'for <span class="home-hero__accent">Every Journey</span>'], false)
            ->assertSee('Reliable, comfortable and affordable taxi services across Tamil Nadu and beyond.')
            ->assertSee('images/demo/onewaytrip-hero-banner.jpg')->assertDontSee('hero-black-sedan-city-skyline.jpg')
            ->assertDontSee('site-header--dark', false)->assertDontSee('Sign Up')
            ->assertSee('Call 24/7')
            // Header menu
            ->assertSeeInOrder(['>Home</a>', '>Services</a>', '>Routes &amp; Fares</a>', '>About Us</a>', '>Contact</a>'], false)
            // Sections in order (homepage mockup): hero + booking, About beside Why choose us, services, stats, districts
            ->assertSeeInOrder([
                'Safe &amp; Secure', 'Verified Drivers', 'On-Time Service', 'id="book-ride"',
                'images/demo/about-woman-in-car-mockup.jpg', 'About One Way Taxi', 'Learn More About Us',
                'Why Choose One Way Taxi?', 'Transparent Pricing', 'Verified Drivers', 'Well Maintained Vehicles', '24/7 Support',
                'Our Taxi Services', 'One Way Taxi', 'Round Trip Taxi', 'Airport Transfers', 'Rental Taxi',
                'Book Your Taxi Across Tamil Nadu', 'Explore Taxi Services',
                'How It Works', 'Enter Trip Details', 'Choose a Vehicle', 'Confirm &amp; Ride',
                'What Our Customers Say',
                'Book Anytime, Anywhere',
                'Talk to Us', 'Send a message',
                'Taxi Services Across Tamil Nadu', 'Quick Links', 'Taxi Services', 'Contact Us', 'Privacy Policy', 'Terms &amp; Conditions',
            ], false)
            // The "Ready for Your Next Journey?" CTA has been removed from the website
            ->assertDontSee('Ready for Your Next Journey?')->assertDontSee('Book in minutes')->assertDontSee('id="cta-title"', false)
            // Contact section: hero-red "Talk to Us" panel beside the original white message form
            ->assertSee('<div class="contact-panel">', false)
            ->assertSee('<form method="POST" action="'.route('contact.store').'" class="relative rounded-2xl border border-line bg-white p-7 sm:p-10" novalidate>', false)
            // Four service cards, one per trip type (airport and corporate stay on the Services page)
            ->assertDontSee('Corporate Travel</h3>', false)
            // No statistics strip on the homepage (mockup); the section record is kept, only hidden
            ->assertDontSee('Happy Customers')->assertDontSee('Illustrative figures')
            // Book Anytime, Anywhere banner replaces the mobile-app promotion; its store buttons are not links until URLs are set
            ->assertSee('images/book-anytime-anywhere-banner.jpg', false)->assertDontSee('app-band__hit', false)
            ->assertDontSee('QR code appears here once the app is published')->assertDontSee('Coming soon to')
            // Seeded testimonials are samples: shown only in demo mode, and labelled
            ->assertSee('Sample Customer A')->assertSee('Placeholder content')
            // Sections from the previous design stay hidden (Popular routes returned in the v18 premium redesign)
            ->assertDontSee('Frequently Asked Questions')
            // Removed homepage sections (records kept; route and district pages unaffected)
            ->assertDontSee('Most Booked One-Way Taxi Routes')->assertDontSee('Taxi Service Across India')->assertDontSee('india-map-hd.png')
            // The website has no Fleet section or Fleet links
            ->assertDontSee('id="fleet"', false)->assertDontSee('href="'.url('/fleet'), false)->assertDontSee('Our Fleet')
            ->assertDontSee('Wide Fleet Options');
    }

    public function test_book_anytime_banner_links_its_store_buttons_once_urls_are_set(): void
    {
        settings()->setMany(['app_play_store_url' => 'https://play.google.com/store/apps/details?id=com.example.onewaytaxi']);

        $this->get('/')->assertOk()
            ->assertSee('class="app-band__hit app-band__hit--play"', false)
            ->assertSee('https://play.google.com/store/apps/details?id=com.example.onewaytaxi', false)
            ->assertDontSee('app-band__hit--ios', false); // no App Store URL yet
    }

    public function test_sample_testimonials_are_hidden_outside_demo_mode(): void
    {
        \App\Models\SiteSetting::where('key', 'demo_mode')->update(['value' => '0']);
        app(\App\Support\Settings::class)->flush();

        $this->get('/')->assertOk()->assertDontSee('Sample Customer A')->assertDontSee('What Our Customers Say');
    }

    /** @return array<string, array{0: string}> */
    public static function publicPages(): array
    {
        return [
            'services' => ['/services'],
            'one way taxi' => ['/services/one-way-taxi'],
            'outstation' => ['/services/outstation-taxi'],
            'airport transfers' => ['/services/airport-transfers'],
            'local taxi' => ['/services/local-taxi'],
            'corporate taxi' => ['/services/corporate-taxi'],
            'routes' => ['/routes'],
            'route detail' => ['/routes/chennai-to-coimbatore-taxi'],
            'faqs' => ['/faqs'],
            'contact' => ['/contact'],
            'booking' => ['/book'],
            'about' => ['/about'],
            'privacy' => ['/privacy-policy'],
            'terms' => ['/terms-and-conditions'],
            'image credits' => ['/image-credits'],
            'corporate' => ['/corporate'],
            'partner' => ['/become-a-partner'],
            'download app' => ['/download-app'],
            'rentals' => ['/services/rentals'],
            'local taxi' => ['/services/local-taxi'], // Bike Taxi is withdrawn (its page redirects to /services)
        ];
    }

    /** @dataProvider publicPages */
    public function test_public_pages_load(string $url): void
    {
        $this->get($url)->assertOk()->assertSee('<link rel="canonical"', false);
    }

    public function test_retired_fleet_pages_redirect_to_booking(): void
    {
        foreach (['/fleet', '/fleet/suv', '/fleet/no-such-vehicle', '/cabs'] as $url) {
            $this->get($url)->assertStatus(301)->assertRedirect(route('booking.create'));
        }
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('/fleet');
    }

    public function test_every_header_and_footer_link_resolves(): void
    {
        $html = $this->get('/')->getContent();
        preg_match_all('#href="(http://localhost[^"\#]*)"#', $html, $matches);

        foreach (array_unique($matches[1]) as $url) {
            $path = parse_url($url, PHP_URL_PATH) ?: '/';
            if (str_starts_with($path, '/images') || str_starts_with($path, '/css') || str_starts_with($path, '/uploads')) {
                continue;
            }
            $this->get($url)->assertOk();
        }
    }

    public function test_unknown_pages_and_drafts_return_custom_404(): void
    {
        $this->get('/this-page-does-not-exist')->assertNotFound()->assertSee('This road doesn’t go anywhere', false);

        Page::where('slug', 'about')->update(['status' => 'draft']);
        $this->get('/about')->assertNotFound();
    }

    public function test_inactive_route_is_hidden(): void
    {
        TaxiRoute::where('slug', 'chennai-to-coimbatore-taxi')->update(['is_active' => false]);

        $this->get('/routes/chennai-to-coimbatore-taxi')->assertNotFound();
        $this->get('/')->assertDontSee('/routes/chennai-to-coimbatore-taxi', false);
    }

    public function test_sitemap_and_robots(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('/routes/chennai-to-coimbatore-taxi', false)
            ->assertSee('/services/airport-transfers', false);

        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:')->assertSee('Disallow: /admin');
    }

    public function test_structured_data_only_when_enabled(): void
    {
        $this->get('/')->assertDontSee('application/ld+json', false);

        settings()->setMany(['structured_data_enabled' => true]);
        $this->get('/')->assertSee('"@type":"TaxiService"', false);
    }

    public function test_contact_form_stores_message(): void
    {
        $this->post('/contact', [
            'name' => 'Asha',
            'phone' => '+91 98400 12345',
            'message' => 'Do you have a large vehicle for 7 people?',
        ])->assertRedirect('/contact');

        $this->assertDatabaseHas('contact_messages', ['name' => 'Asha']);
    }

    public function test_services_dropdown_hides_corporate_bike_and_auto_but_keeps_their_pages(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        preg_match('#<ul id="nav-services".*?</ul>#s', $html, $m);
        $dropdown = $m[0] ?? '';

        foreach (['one-way-taxi', 'outstation-taxi', 'airport-transfers', 'rentals', 'local-taxi'] as $slug) {
            $this->assertStringContainsString('href="'.route('services.show', $slug).'"', $dropdown, $slug);
        }
        foreach (['corporate-taxi' => 'Corporate Travel', 'bike-taxi' => 'Bike Taxi', 'auto-taxi' => 'Auto Taxi'] as $slug => $title) {
            $this->assertStringNotContainsString(route('services.show', $slug), $dropdown, $slug);
            $this->assertStringNotContainsString($title, $dropdown, $title);
        }
        $this->get(route('services.show', 'corporate-taxi'))->assertOk(); // Corporate page is kept, only off the menu
        $this->assertStringContainsString('data-submenu-toggle', $html); // dropdown stays (5 services remain)
        foreach (['/routes', '/about', '/contact', '/services'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_bike_and_auto_taxi_are_withdrawn_from_the_public_website_but_kept_in_the_database(): void
    {
        $services = $this->get('/services')->assertOk()->getContent();
        $home = $this->get('/')->assertOk()->getContent();
        $sitemap = $this->get('/sitemap.xml')->assertOk()->getContent();

        foreach (['bike-taxi' => 'Bike Taxi', 'auto-taxi' => 'Auto Taxi'] as $slug => $title) {
            foreach ([$services, $home, $sitemap] as $html) {
                $this->assertStringNotContainsString(route('services.show', $slug), $html);
            }
            $this->assertStringNotContainsString('>'.$title.'</h2>', $services);
            $this->get(route('services.show', $slug))->assertRedirect(route('services.index'))->assertStatus(301);
            $this->assertDatabaseHas('services', ['slug' => $slug]); // record kept
        }

        // The remaining six services fill the grid: 3 columns × 2 rows on desktop.
        $this->assertSame(6, substr_count($services, 'class="svc-card"'));
        foreach (['One Way Taxi', 'Round Trip Taxi', 'Airport Transfers', 'Rental Taxi', 'Local Taxi', 'Corporate Travel'] as $title) {
            $this->assertStringContainsString('>'.$title.'</h2>', $services);
        }
        // "Other services" on a service page never offers them either.
        $this->get(route('services.show', 'one-way-taxi'))->assertOk()
            ->assertDontSee(route('services.show', 'bike-taxi'), false)->assertDontSee(route('services.show', 'auto-taxi'), false);
    }

    public function test_journey_cta_is_removed_from_every_public_page(): void
    {
        $urls = ['/', '/tamil-nadu', '/routes', '/services', '/puducherry', route('pages.show', 'about')];
        foreach (\App\Models\DistrictPage::published()->get() as $district) {
            $urls[] = $district->url(); // all 39 location pages
        }

        foreach (array_unique($urls) as $url) {
            $this->get($url)->assertOk()
                ->assertDontSee('Ready for your next journey?')->assertDontSee('Ready for Your Next Journey?')
                ->assertDontSee('id="cta-title"', false)->assertDontSee('journey-cta', false)
                ->assertSee('<footer class="site-footer">', false); // the rest of the page (footer) still renders
        }
    }
}
