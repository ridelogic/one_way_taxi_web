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
            ->assertSee(':root{--color-gold:#0D6EFD;--color-forest:#062B55;--color-fresh:#0D6EFD}', false)
            ->assertSeeInOrder(['Comfortable Rides', '<span class="home-hero__accent">Every Journey</span>'], false)
            ->assertSee('images/demo/hero-airport-taxi-golden-hour.jpg')
            ->assertSee('Call 24/7')
            // Sections in the reference order
            ->assertSeeInOrder([
                'Safe &amp; Secure Travel', '24/7 Customer Support', 'Pan India Service', 'id="book-ride"',
                'Our Taxi Services', 'One Way Taxi', 'Outstation Taxi', 'Airport Transfers', 'Corporate Travel',
                'Why Choose One Way Taxi?', 'Learn More', 'Transparent Pricing', 'Verified Drivers', 'Well Maintained Vehicles', '24/7 Support',
                'Happy Customers', 'Active Drivers', 'Cities Covered', 'Successful Rides',
                'How It Works', 'Enter Trip Details', 'Choose a Vehicle', 'Confirm &amp; Ride',
                'Our Fleet', 'Hatchback / Mini', 'Sedan', 'SUV', 'Innova Crysta', 'Tempo Traveller',
                'india-map-hd.png', 'Taxi Service Across India',
                'Book Anytime, Anywhere',
                'What Our Customers Say',
                'Ready for Your Next Journey?', 'Book Your Taxi',
                'Quick Links', 'Our Services', 'Contact Us', 'Privacy Policy', 'Terms &amp; Conditions',
            ], false)
            // Four service cards: local and rental trips stay on the Services page
            ->assertDontSee('Hourly packages for errands')
            // Statistics are illustrative until verified
            ->assertSeeInOrder(['10,000+', '500+', '100+', '1,00,000+', 'Illustrative figures'])
            // App banner: no store links or QR code until real URLs are configured
            ->assertSee('Coming soon to')->assertSee('QR code appears here once the app is published')
            ->assertDontSee('Scan to download')
            // Seeded testimonials are samples: shown only in demo mode, and labelled
            ->assertSee('Sample Customer A')->assertSee('Placeholder content')
            // Sections from the previous design are hidden
            ->assertDontSee('Most Booked One-Way Taxi Routes')
            ->assertDontSee('Frequently Asked Questions')
            ->assertDontSee('Wide Fleet Options');
    }

    public function test_sample_testimonials_are_hidden_outside_demo_mode(): void
    {
        \App\Models\SiteSetting::where('key', 'demo_mode')->update(['value' => '0']);
        app(\App\Support\Settings::class)->flush();

        $this->get('/')->assertOk()->assertDontSee('Sample Customer A')->assertDontSee('What Our Customers Say')
            // Unverified statistics stay labelled even when demo mode is off
            ->assertSee('Illustrative figures');
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
            'fleet' => ['/fleet'],
            'vehicle detail' => ['/fleet/suv'],
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
            'bike taxi' => ['/services/bike-taxi'],
        ];
    }

    /** @dataProvider publicPages */
    public function test_public_pages_load(string $url): void
    {
        $this->get($url)->assertOk()->assertSee('<link rel="canonical"', false);
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
}
