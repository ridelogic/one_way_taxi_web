<?php

namespace Tests\Feature;

use App\Enums\TripType;
use App\Models\Booking;
use App\Models\VehicleCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function vehicleId(string $slug = 'economy-sedan'): int
    {
        return VehicleCategory::where('slug', $slug)->value('id');
    }

    /** The homepage widget fields: route, date, time, vehicle, mobile (+ consent). */
    private function widgetPayload(array $overrides = []): array
    {
        return array_merge([
            'source' => 'widget',
            'trip_type' => 'one_way',
            'pickup_location' => 'Chennai',
            'destination_location' => 'Coimbatore',
            'travel_date' => now()->addDays(3)->toDateString(),
            'travel_time' => '07:30',
            'vehicle_category_id' => $this->vehicleId(),
            'customer_phone' => '98400 12345',
            'consent' => '1',
        ], $overrides);
    }

    /** The homepage booking panel fields: route, date, mobile, email (+ consent). */
    private function panelPayload(array $overrides = []): array
    {
        return array_merge([
            'source' => 'homepage',
            'trip_type' => 'one_way',
            'pickup_location' => 'Chennai',
            'destination_location' => 'Coimbatore',
            'travel_date' => now()->addDays(3)->toDateString(),
            'customer_phone' => '98400 12345',
            'customer_email' => 'Asha@Example.com',
            'consent' => '1',
        ], $overrides);
    }

    public function test_homepage_booking_panel_has_tabs_and_requested_fields(): void
    {
        $html = $this->get('/')->assertOk()
            // The hero (navy gradient, airport taxi scene, three benefits) ends with the taxi search panel
            ->assertSeeInOrder(['class="home-hero"', 'Comfortable Rides', 'Every Journey',
                'Safe &amp; Secure Travel', '24/7 Customer Support', 'Pan India Service', 'id="book-ride" class="booking-panel', 'One Way', 'Round Trip', 'Outstation'], false)
            ->assertSee('images/demo/hero-airport-taxi-golden-hour.jpg')->assertDontSee('hero-airport-takeoff-golden-hour.jpg')
            ->assertSee('<h2 id="book-ride-title" class="sr-only">Search for a Taxi</h2>', false)
            // A GET search that opens the full booking page
            ->assertSee('method="GET" action="'.route('booking.create').'"', false)
            ->assertSee('name="trip_type" value="one_way" class="sr-only" checked', false)
            ->assertSee('name="trip_type" value="round_trip"', false)
            ->assertSee('name="trip_type" value="outstation"', false)
            ->assertDontSee('name="trip_type" value="rental"', false)
            ->assertSee('data-lead-hours="2"', false)
            ->assertSee('data-trip-show="round_trip"', false)
            ->assertSeeInOrder(['Pickup Location', 'Drop Location', 'Journey Date', 'Return Date', 'Pickup Time', 'Search Taxi'])
            ->assertSee('list="bp-cities"', false)
            // Contact details, vehicle and consent are collected on the booking page
            ->assertDontSee('name="customer_email"', false)
            ->assertDontSee('name="vehicle_category_id"', false)
            ->assertDontSee('quick-actions', false)
            ->getContent();

        $this->assertSame(1, substr_count($html, 'id="book-ride"'), 'Only one booking section on the homepage');
    }

    public function test_homepage_search_prefills_the_booking_page_and_maps_round_trip_to_outstation(): void
    {
        $date = now()->addDays(3)->toDateString();
        $return = now()->addDays(5)->toDateString();

        $this->get(route('booking.create', ['trip_type' => 'round_trip', 'pickup_location' => 'Chennai', 'destination_location' => 'Coimbatore',
            'travel_date' => $date, 'return_date' => $return, 'travel_time' => '09:30']))
            ->assertOk()
            ->assertSee('name="trip_type" value="outstation" checked', false)
            ->assertSee('Round trips are booked as an Outstation trip with your return date.')
            ->assertSee('value="'.$date.'"', false)
            ->assertSee('value="'.$return.'"', false)
            ->assertSee('value="09:30"', false)
            ->assertSee('value="Coimbatore"', false);

        // A return date before the journey date is not prefilled; one-way trips never get one.
        $this->get(route('booking.create', ['trip_type' => 'round_trip', 'travel_date' => $return, 'return_date' => $date]))
            ->assertOk()->assertDontSee('value="'.$date.'"', false);
        $this->get(route('booking.create', ['trip_type' => 'one_way', 'return_date' => $return]))
            ->assertOk()->assertDontSee('value="'.$return.'"', false)->assertDontSee('Round trips are booked');
    }

    public function test_homepage_panel_enquiry_is_stored_without_time_or_vehicle(): void
    {
        $response = $this->from('/')->post('/book', $this->panelPayload(['trip_type' => 'outstation']));

        $booking = Booking::firstOrFail();
        $response->assertRedirect(route('booking.confirmation', $booking->reference));
        $this->assertSame(TripType::Outstation, $booking->trip_type);
        $this->assertSame('asha@example.com', $booking->customer_email);
        $this->assertSame('+919840012345', $booking->customer_phone);
        $this->assertNull($booking->travel_time);
        $this->assertNull($booking->vehicle_category_id);
        $this->assertSame('homepage booking panel', $booking->source);

        $this->get(route('booking.confirmation', $booking->reference))->assertOk()->assertSee('Outstation');
    }

    public function test_homepage_panel_validates_email_mobile_date_and_keeps_the_trip_type(): void
    {
        $this->from('/')->post('/book', $this->panelPayload(['customer_email' => '']))->assertSessionHasErrors('customer_email');
        $this->from('/')->post('/book', $this->panelPayload(['customer_email' => 'not-an-email']))->assertSessionHasErrors('customer_email');
        $this->from('/')->post('/book', $this->panelPayload(['customer_phone' => '123']))->assertSessionHasErrors('customer_phone');
        $this->from('/')->post('/book', $this->panelPayload(['travel_date' => now()->subDay()->toDateString()]))->assertSessionHasErrors('travel_date');
        $this->from('/')->post('/book', $this->panelPayload(['trip_type' => 'rental', 'destination_location' => '']))->assertSessionHasErrors('rental_package');

        // Errors return to the booking panel
        $this->from('/')->post('/book', $this->panelPayload(['trip_type' => 'outstation', 'customer_email' => '']))
            ->assertRedirect(route('home').'#book-ride');
        $this->assertSame(0, Booking::count());
    }

    public function test_other_booking_forms_still_require_time_and_vehicle(): void
    {
        $this->post('/book', $this->widgetPayload(['travel_time' => '', 'vehicle_category_id' => '']))
            ->assertSessionHasErrors(['travel_time', 'vehicle_category_id'])->assertSessionDoesntHaveErrors('customer_email');
    }

    public function test_widget_enquiry_is_stored_with_reference_and_private_confirmation(): void
    {
        $response = $this->from('/')->post('/book', $this->widgetPayload());

        $booking = Booking::firstOrFail();
        $response->assertRedirect(route('booking.confirmation', $booking->reference));

        $this->assertMatchesRegularExpression('/^OWT-\d{6}-[A-Z0-9]{5}$/', $booking->reference);
        $this->assertSame(TripType::OneWay, $booking->trip_type);
        $this->assertSame('+919840012345', $booking->customer_phone); // normalised Indian mobile
        $this->assertSame('6970.00', $booking->estimated_fare);
        $this->assertSame('pending', $booking->status->value);
        $this->assertSame('homepage widget', $booking->source);
        $this->assertNotNull($booking->taxi_route_id);

        $this->get(route('booking.confirmation', $booking->reference))
            ->assertOk()->assertSee($booking->reference)->assertSee('Enquiry received')->assertSee('One Way Trip');

        $this->flushSession();
        $this->get(route('booking.confirmation', $booking->reference))->assertNotFound();
    }

    public function test_full_booking_page_with_optional_details(): void
    {
        $this->post('/book', $this->widgetPayload([
            'source' => 'booking-page', 'customer_name' => 'Ravi Kumar', 'customer_email' => 'Ravi@Example.com',
            'passengers' => 3, 'pickup_address' => 'T Nagar', 'customer_notes' => 'Two suitcases',
        ]))->assertRedirect();

        $booking = Booking::firstOrFail();
        $this->assertSame('Ravi Kumar', $booking->customer_name);
        $this->assertSame('ravi@example.com', $booking->customer_email);
        $this->assertSame('website', $booking->source);
    }

    public function test_rental_needs_a_package_but_no_destination(): void
    {
        $this->post('/book', $this->widgetPayload(['trip_type' => 'rental', 'destination_location' => '']))
            ->assertSessionHasErrors('rental_package');

        $this->post('/book', $this->widgetPayload(['trip_type' => 'rental', 'destination_location' => '', 'rental_package' => '8 hrs / 80 km']))
            ->assertRedirect();

        $booking = Booking::firstOrFail();
        $this->assertSame(TripType::Rental, $booking->trip_type);
        $this->assertSame('8 hrs / 80 km', $booking->rental_package);
        $this->assertNull($booking->drop_location);
        $this->assertNull($booking->estimated_fare);
    }

    public function test_outstation_requires_destination_and_accepts_return_date(): void
    {
        $this->post('/book', $this->widgetPayload(['trip_type' => 'outstation', 'destination_location' => '']))
            ->assertSessionHasErrors('destination_location');

        $this->post('/book', $this->widgetPayload(['trip_type' => 'outstation', 'return_date' => now()->addDays(5)->toDateString()]))
            ->assertRedirect();

        $this->assertSame(TripType::Outstation, Booking::firstOrFail()->trip_type);
    }

    public function test_hero_quote_prefills_booking_page_with_estimate(): void
    {
        $this->get('/book?pickup_location=Chennai&destination_location=Coimbatore&vehicle_category_id='.$this->vehicleId())
            ->assertOk()
            ->assertSee('value="Chennai"', false)
            ->assertSee('₹6,970')
            ->assertSee('Toll charges');
    }

    public function test_fare_estimate_endpoint(): void
    {
        $this->postJson('/fare-estimate', [
            'trip_type' => 'one_way', 'pickup_location' => 'chennai', 'destination_location' => 'Coimbatore, Tamil Nadu', 'vehicle_category_id' => $this->vehicleId(),
        ])->assertOk()->assertJson(['available' => true, 'total' => '6970.00', 'total_formatted' => '₹6,970', 'is_sample' => true]);

        // Without a vehicle the widget shows the route's starting fare.
        $this->postJson('/fare-estimate', ['pickup_location' => 'Chennai', 'destination_location' => 'Coimbatore'])
            ->assertOk()->assertJson(['available' => true, 'starting_from' => true, 'total' => '6970.00']);

        $this->postJson('/fare-estimate', ['trip_type' => 'rental', 'pickup_location' => 'Chennai'])
            ->assertOk()->assertJson(['available' => false]);
    }

    public function test_unknown_cities_are_accepted_without_an_estimate(): void
    {
        $this->post('/book', $this->widgetPayload(['pickup_location' => 'Hosur', 'destination_location' => 'Vellore']))->assertRedirect();

        $booking = Booking::firstOrFail();
        $this->assertNull($booking->estimated_fare);
        $this->assertNull($booking->pickup_city_id);
        $this->assertSame('Hosur', $booking->pickup_location);
    }

    public function test_past_dates_and_short_notice_are_rejected(): void
    {
        $this->post('/book', $this->widgetPayload(['travel_date' => now()->subDay()->toDateString()]))->assertSessionHasErrors('travel_date');
        $this->post('/book', $this->widgetPayload(['travel_date' => now()->toDateString(), 'travel_time' => now()->format('H:i')]))->assertSessionHasErrors('travel_time');

        $this->assertDatabaseCount('bookings', 0);
    }

    /** @return array<string, array{0: string, 1: bool}> */
    public static function mobileNumbers(): array
    {
        return [
            '10 digits' => ['9840012345', true],
            'with +91 and spaces' => ['+91 98400 12345', true],
            'with 0 prefix' => ['09840012345', true],
            'with hyphens' => ['98400-12345', true],
            'starts with 5' => ['5840012345', false],
            'too short' => ['984001234', false],
            'landline style' => ['044 2345 6789', false],
            'letters' => ['call me', false],
            'foreign' => ['+44 7700 900123', false],
        ];
    }

    /** @dataProvider mobileNumbers */
    public function test_indian_mobile_validation(string $number, bool $valid): void
    {
        $response = $this->post('/book', $this->widgetPayload(['customer_phone' => $number]));

        $valid ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors('customer_phone');
    }

    public function test_required_fields_consent_and_spam_protection(): void
    {
        $this->post('/book', ['trip_type' => 'one_way'])->assertSessionHasErrors([
            'pickup_location', 'destination_location', 'travel_date', 'travel_time', 'vehicle_category_id', 'customer_phone', 'consent',
        ]);

        $this->post('/book', $this->widgetPayload(['destination_location' => 'chennai']))->assertSessionHasErrors('destination_location');
        $this->post('/book', $this->widgetPayload(['customer_email' => 'not-an-email']))->assertSessionHasErrors('customer_email');
        $this->post('/book', $this->widgetPayload(['passengers' => 9]))->assertSessionHasErrors('passengers');
        $this->post('/book', $this->widgetPayload(['consent' => null]))->assertSessionHasErrors('consent');
        $this->post('/book', $this->widgetPayload(['website' => 'http://spam.example']))->assertSessionHasErrors('website');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_widget_errors_redirect_back_to_the_widget(): void
    {
        $this->from(route('home'))->post('/book', $this->widgetPayload(['customer_phone' => '123']))
            ->assertRedirect(route('home').'#book-ride');
    }

    public function test_server_ignores_client_supplied_fare(): void
    {
        $this->post('/book', $this->widgetPayload(['estimated_fare' => '1.00']));

        $this->assertSame('6970.00', Booking::firstOrFail()->estimated_fare);
    }
}
