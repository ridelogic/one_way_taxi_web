<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\DistrictPage;
use App\Models\TaxiRoute;
use App\Models\User;
use App\Models\VehicleCategory;
use App\Services\FareCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistrictPagesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private const DISTRICTS = [
        'Ariyalur', 'Chengalpattu', 'Chennai', 'Coimbatore', 'Cuddalore', 'Dharmapuri', 'Dindigul', 'Erode', 'Kallakurichi',
        'Kancheepuram', 'Kanniyakumari', 'Karur', 'Krishnagiri', 'Madurai', 'Mayiladuthurai', 'Nagapattinam', 'Namakkal',
        'Nilgiris', 'Perambalur', 'Pudukkottai', 'Ramanathapuram', 'Ranipet', 'Salem', 'Sivaganga', 'Tenkasi', 'Thanjavur',
        'Theni', 'Thoothukudi', 'Tiruchirappalli', 'Tirunelveli', 'Tirupathur', 'Tiruppur', 'Tiruvallur', 'Tiruvannamalai',
        'Tiruvarur', 'Vellore', 'Viluppuram', 'Virudhunagar',
    ];

    public function test_all_38_districts_are_listed_with_working_links(): void
    {
        // 38 Tamil Nadu districts + Puducherry (a separate Union Territory page) = 39 published pages.
        $this->assertSame(39, DistrictPage::published()->count());
        $this->assertSame(38, DistrictPage::published()->tamilNadu()->count());

        $index = $this->get('/tamil-nadu')->assertOk()->assertSee('Taxi Services Across Tamil Nadu');
        $home = $this->get('/')->assertOk()->assertSee('Book Your Taxi Across Tamil Nadu')->assertSee('Explore Taxi Services');

        foreach (self::DISTRICTS as $name) {
            $slug = DistrictPage::where('name', $name)->value('slug');
            $this->assertNotNull($slug, "Missing district {$name}");
            $index->assertSee('href="'.route('districts.show', $slug).'"', false);
            $home->assertSee('href="'.route('districts.show', $slug).'"', false);
        }
        $this->get('/')->assertSee('href="'.url('/tamil-nadu').'"', false); // footer link
    }

    public function test_every_district_page_has_unique_seo_metadata(): void
    {
        $titles = $descriptions = [];

        foreach (DistrictPage::published()->get() as $district) {
            $url = $district->url(); // /tamil-nadu/{slug}, or /puducherry for the Union Territory
            $html = $this->get($url)->assertOk()
                ->assertSee('<title>Taxi Service in '.e($district->name).' | One Way Taxi</title>', false)
                ->assertSee('<link rel="canonical" href="'.$url.'">', false)
                ->assertSee('<meta property="og:title" content="Taxi Service in '.e($district->name).' | One Way Taxi">', false)
                ->assertSee('<meta name="robots" content="index, follow">', false)
                ->getContent();

            $this->assertSame(1, substr_count($html, '<h1'), "{$district->name} must have exactly one H1");
            preg_match('/<meta name="description" content="([^"]+)"/', $html, $m);
            $titles[] = $district->seoTitle();
            $descriptions[] = $m[1] ?? '';
        }

        $this->assertCount(39, array_unique($titles));
        $this->assertCount(39, array_unique($descriptions));
    }

    public function test_district_content_is_not_duplicated_across_pages(): void
    {
        $paragraphs = DistrictPage::all()->flatMap(function (DistrictPage $d) {
            preg_match_all('/<p>(.*?)<\/p>/s', (string) $d->content, $m);

            return $m[1];
        });

        $this->assertSame($paragraphs->count(), $paragraphs->unique()->count(), 'District paragraphs must be unique');
    }

    public function test_booking_widget_uses_the_existing_booking_flow_with_the_district_prefilled(): void
    {
        $this->get('/tamil-nadu/coimbatore')->assertOk()
            ->assertSee('method="GET" action="'.route('booking.create').'"', false)
            ->assertSee('name="trip_type" value="one_way" class="sr-only" checked', false)
            ->assertSee('name="trip_type" value="rental"', false) // "Local" = existing hourly rental trip type
            ->assertSee('data-trip-show="local one_way round_trip"', false)->assertSee('name="trip_type" value="local"', false)->assertDontSee('name="trip_type" value="airport"', false)
            ->assertSee('value="Coimbatore"', false);

        // Nilgiris prefills the linked booking city (Ooty); districts without one use their headquarters.
        $this->get('/tamil-nadu/nilgiris')->assertSee('id="bp-pickup"', false)->assertSee('value="Ooty"', false);
        $this->get('/tamil-nadu/kanniyakumari')->assertSee('value="Nagercoil"', false);

        // The search opens the existing booking page for local and one-way trips.
        $this->get('/book?trip_type=rental&pickup_location=Coimbatore')->assertOk();
        $this->get('/book?trip_type=one_way&pickup_location=Coimbatore&destination_location=Ooty')->assertOk()->assertSee('Coimbatore');
    }

    public function test_fares_come_from_existing_route_data(): void
    {
        $chennai = City::where('name', 'Chennai')->firstOrFail();
        $coimbatore = City::where('name', 'Coimbatore')->firstOrFail();
        $route = TaxiRoute::where('origin_city_id', $chennai->id)->where('destination_city_id', $coimbatore->id)->firstOrFail();
        $sedan = VehicleCategory::where('slug', 'economy-sedan')->firstOrFail();
        $expected = app(FareCalculator::class)->estimate($chennai, $coimbatore, $sedan);
        $this->assertTrue($expected['available']);

        $this->get('/tamil-nadu/chennai')->assertOk()
            ->assertSee('Chennai Taxi Fares')
            ->assertSee(route('routes.show', $route->slug), false)
            ->assertSee($expected['total_formatted'])
            ->assertSee(e(route('booking.create', ['pickup_location' => 'Chennai', 'destination_location' => 'Coimbatore', 'vehicle_category_id' => $sedan->id])), false)
            ->assertDontSee('We don’t publish fixed fares');
    }

    public function test_districts_without_route_data_ask_for_a_fare_instead_of_showing_prices(): void
    {
        $html = $this->get('/tamil-nadu/ariyalur')->assertOk()
            ->assertSee('Get a fare for your Ariyalur trip')
            ->assertSee('Fares on request')
            ->assertDontSee('class="fare-grid"', false)
            ->getContent();

        // No fare grid or route prices on the page.
        $this->assertStringNotContainsString('Starting from', $html);
    }

    public function test_page_shows_services_pickups_content_and_related_districts(): void
    {
        $this->get('/tamil-nadu/coimbatore')->assertOk()
            ->assertSee('Reliable Taxi Services in Coimbatore')
            ->assertSee('Why Choose One Way Taxi in Coimbatore?')
            ->assertSee('Taxi Services in Coimbatore')->assertSeeInOrder(['One Way Taxi <span class="sr-only">in Coimbatore', 'Round Trip Taxi <span class="sr-only">in Coimbatore', 'Local Taxi <span class="sr-only">in Coimbatore', 'Rental Taxi <span class="sr-only">in Coimbatore', 'Airport transfers'], false)
            ->assertSee('Nearest airport: Coimbatore International Airport (CJB)')
            ->assertSeeInOrder(['Popular Pickup and Drop Locations in Coimbatore', 'Gandhipuram', 'Peelamedu', 'RS Puram', 'Drop locations', 'Isha Yoga Centre'])
            ->assertSeeInOrder(['Popular Taxi Routes from Coimbatore', 'Coimbatore <span class="text-gold" aria-hidden="true">→</span><span class="sr-only">to</span> Erode'], false)
            ->assertSeeInOrder(['Taxi Questions About Coimbatore', 'Can I book a one way taxi from Coimbatore?'])
            ->assertSee('"@type":"FAQPage"', false)->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('foot of the Nilgiris')
            ->assertSee('href="'.route('districts.show', 'tiruppur').'"', false)
            ->assertSee('href="'.route('districts.show', 'nilgiris').'"', false)
            ->assertSee('alt="Taxi service in Coimbatore, Tamil Nadu"', false);
    }

    public function test_sitemap_lists_every_published_district(): void
    {
        DistrictPage::where('slug', 'theni')->update(['is_published' => false]);

        $xml = $this->get('/sitemap.xml')->assertOk()->assertSee(route('districts.index'))->getContent();
        $this->assertSame(37, substr_count($xml, url('/tamil-nadu').'/'));
        $this->assertStringNotContainsString(route('districts.show', 'theni'), $xml);
    }

    public function test_unpublished_pages_are_hidden_except_as_an_admin_preview(): void
    {
        DistrictPage::where('slug', 'theni')->update(['is_published' => false]);

        $this->get('/tamil-nadu/theni')->assertNotFound();
        $this->get('/tamil-nadu')->assertDontSee(route('districts.show', 'theni'));
        $this->get('/tamil-nadu/not-a-district')->assertNotFound();

        $this->actingAs(User::where('is_admin', true)->firstOrFail())
            ->get('/tamil-nadu/theni')->assertOk()->assertSee('Preview')->assertSee('noindex, nofollow');
    }

    public function test_admin_can_manage_district_pages(): void
    {
        $admin = User::where('is_admin', true)->firstOrFail();
        $erode = DistrictPage::where('slug', 'erode')->firstOrFail();
        $salemCity = City::where('name', 'Salem')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.districts.index'))->assertOk()->assertSee('Erode');
        $this->get(route('admin.districts.edit', $erode))->assertOk()->assertSee('District Details')->assertSee('Main description');

        // District Details tab.
        $this->put(route('admin.districts.update', $erode), [
            'name' => 'Erode', 'slug' => 'erode', 'state' => 'Tamil Nadu',
            'heading' => 'Erode Taxi — Turmeric City Rides', 'intro' => 'Test intro',
            'content' => '<h2>Guide</h2><p>Turmeric market rides.</p><script>alert(1)</script>',
            'is_published' => '1',
        ])->assertRedirect(route('admin.districts.edit', $erode))->assertSessionHasNoErrors();

        // Routes tab: pickup points, nearby districts and the cities whose priced routes fill the fare table.
        $this->get(route('admin.districts.tab', [$erode, 'routes']))->assertOk()->assertSee('Popular pickup locations');
        $this->put(route('admin.districts.tab.update', [$erode, 'routes']), [
            'pickup_locations' => "Erode Junction\nPerundurai",
            'related_districts' => "Tiruppur\nsalem",
            'city_ids' => [$salemCity->id],
        ])->assertRedirect(route('admin.districts.tab', [$erode, 'routes']))->assertSessionHasNoErrors();

        // SEO tab.
        $this->put(route('admin.districts.tab.update', [$erode, 'seo']), ['seo_title' => 'Erode Cabs', 'seo_description' => 'Erode cab booking.'])
            ->assertSessionHasNoErrors();

        $erode->refresh();
        $this->assertSame(['Erode Junction', 'Perundurai'], $erode->pickup_locations);
        $this->assertSame(['tiruppur', 'salem'], $erode->related_districts);
        $this->assertStringNotContainsString('<script', $erode->content);
        $this->assertSame([$salemCity->id], $erode->cities()->pluck('cities.id')->all());

        $this->get('/tamil-nadu/erode')->assertOk()
            ->assertSee('Erode Taxi — Turmeric City Rides')
            ->assertSee('<title>Erode Cabs | One Way Taxi</title>', false)
            ->assertSee('Erode Taxi Fares')->assertDontSee('Get a fare for your Erode trip'); // linked city's routes now shown

        $this->patch(route('admin.districts.toggle', $erode))->assertRedirect();
        $this->assertFalse($erode->fresh()->is_published);

        // Related districts must exist.
        $this->put(route('admin.districts.tab.update', [$erode, 'routes']), ['related_districts' => 'Atlantis'])
            ->assertSessionHasErrors('related_districts.0');
    }

    public function test_homepage_district_cards_show_a_photo_description_and_link_for_all_38(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // The district grid follows the hero's booking panel and the About / services sections.
        $this->assertLessThan(strpos($html, 'Book Your Taxi Across Tamil Nadu'), strpos($html, 'id="book-ride"'));
        $this->assertLessThan(strpos($html, 'Book Your Taxi Across Tamil Nadu'), strpos($html, 'id="services-title"'));

        // 38 district cards + the Puducherry (Union Territory) card.
        $this->assertSame(39, substr_count($html, 'class="district-card"'));
        $this->assertSame(39, substr_count($html, 'Explore Taxi Services'));
        $this->assertStringContainsString('images/demo/district-thanjavur.jpg', $html);
        $this->assertStringContainsString('alt="Brihadeeswarar temple, Thanjavur"', $html);
        $this->assertStringContainsString('Heart of the Kaveri delta and home of the UNESCO-listed Big Temple.', $html);
        $this->assertSame(39, DistrictPage::whereNotNull('hero_media_id')->count());
    }

    public function test_district_page_offers_trip_types_and_destinations_through_the_booking_page(): void
    {
        $this->get('/tamil-nadu/thanjavur')->assertOk()
            ->assertSee('images/demo/district-thanjavur.jpg')
            ->assertSee(e(route('booking.create', ['trip_type' => 'local', 'pickup_location' => 'Thanjavur'])), false)->assertSee(e(route('booking.create', ['trip_type' => 'rental', 'pickup_location' => 'Thanjavur'])), false)
            ->assertSee(e(route('booking.create', ['trip_type' => 'round_trip', 'pickup_location' => 'Thanjavur'])), false)
            ->assertSee('Popular Trips from Thanjavur')
            ->assertSee(e(route('booking.create', ['trip_type' => 'one_way', 'pickup_location' => 'Thanjavur', 'destination_location' => 'Kumbakonam'])), false)
            ->assertSee('Get a fare for your Thanjavur trip'); // destinations never claim fares
    }

    public function test_footer_links_every_district_on_every_page(): void
    {
        foreach (['/contact', '/routes', '/tamil-nadu/madurai'] as $url) {
            $html = $this->get($url)->assertOk()->assertSee('Taxi Services Across Tamil Nadu')->getContent();
            // The 38-district band sits directly ABOVE the footer, as its own section.
            $start = strpos($html, '<nav class="tn-districts"');
            $footerAt = strpos($html, '<footer class="site-footer">');
            $this->assertNotFalse($start);
            $this->assertLessThan($footerAt, $start);
            $band = substr($html, $start, $footerAt - $start);
            $this->assertSame(38, preg_match_all('#href="'.preg_quote(url('/tamil-nadu'), '#').'/[a-z-]+"#', $band));
            $this->assertSame(0, preg_match_all('#href="'.preg_quote(url('/tamil-nadu'), '#').'/[a-z-]+"#', substr($html, $footerAt)));
        }
    }
}
