<?php

namespace Tests\Feature;

use App\Enums\TripType;
use App\Models\Booking;
use App\Models\City;
use App\Models\User;
use App\Models\VehicleCategory;
use App\Services\FareCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The four booking categories — Local, Rental, One Way, Round Trip — are named and handled consistently everywhere. */
class TripTypesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'source' => 'booking-page',
            'trip_type' => 'one_way',
            'pickup_location' => 'Chennai',
            'destination_location' => 'Coimbatore',
            'travel_date' => now()->addDays(4)->toDateString(),
            'travel_time' => '08:00',
            'vehicle_category_id' => VehicleCategory::where('slug', 'economy-sedan')->value('id'),
            'customer_phone' => '98400 12345',
            'customer_name' => 'Asha',
            'consent' => '1',
        ], $overrides);
    }

    /** Submit a booking, then check the confirmation page and the admin screens show the same trip type name. */
    private function book(array $overrides, string $label): Booking
    {
        $this->post(route('booking.store'), $this->payload($overrides))->assertSessionHasNoErrors()->assertRedirect();
        $booking = Booking::latest('id')->firstOrFail();

        $this->get(route('booking.confirmation', $booking->reference))->assertOk()->assertSee($label);

        $admin = User::where('is_admin', true)->firstOrFail();
        $this->actingAs($admin)->get(route('admin.bookings.show', $booking))->assertOk()->assertSee($label);
        $this->get(route('admin.bookings.index', ['trip' => $booking->trip_type->value]))->assertOk()
            ->assertSee($booking->reference)->assertSee('<span class="abadge abadge--slate">'.$label.'</span>', false);
        auth()->logout();

        return $booking;
    }

    public function test_exactly_four_trip_types_with_standard_names(): void
    {
        $this->assertSame(['one_way' => 'One Way', 'outstation' => 'Round Trip', 'local' => 'Local', 'rental' => 'Rental'], TripType::options());
        $this->assertSame(TripType::Outstation, TripType::fromInput('round_trip'));
        $this->assertSame(TripType::OneWay, TripType::fromInput('airport')); // airport transfers book as One Way
    }

    public function test_local_booking(): void
    {
        $booking = $this->book(['trip_type' => 'local', 'pickup_location' => 'Gandhipuram, Coimbatore', 'destination_location' => 'Coimbatore Junction', 'return_date' => now()->addDays(6)->toDateString()], 'Local');

        $this->assertSame(TripType::Local, $booking->trip_type);
        $this->assertSame('Coimbatore Junction', $booking->drop_location);
        $this->assertNull($booking->return_date);      // only Round Trip has a return date
        $this->assertNull($booking->rental_package);
        $this->assertNull($booking->estimated_fare);   // local trips are quoted by the team
    }

    public function test_rental_booking(): void
    {
        $booking = $this->book(['trip_type' => 'rental', 'pickup_location' => 'Madurai', 'destination_location' => 'Ignored', 'rental_package' => '8 hrs / 80 km'], 'Rental');

        $this->assertSame(TripType::Rental, $booking->trip_type);
        $this->assertSame('8 hrs / 80 km', $booking->rental_package);
        $this->assertNull($booking->drop_location);
        $this->assertNull($booking->estimated_fare);

        $this->post(route('booking.store'), $this->payload(['trip_type' => 'rental', 'rental_package' => '']))->assertSessionHasErrors('rental_package');
        $this->assertContains('Full day', settings()->lines('rental_packages'));
        $this->assertNotContains('Full day (local)', settings()->lines('rental_packages'));
    }

    public function test_one_way_booking_keeps_its_fare_calculation(): void
    {
        $chennai = City::where('name', 'Chennai')->first();
        $coimbatore = City::where('name', 'Coimbatore')->first();
        $sedan = VehicleCategory::where('slug', 'economy-sedan')->first();
        $expected = app(FareCalculator::class)->estimate($chennai, $coimbatore, $sedan);

        $booking = $this->book(['trip_type' => 'one_way', 'return_date' => now()->addDays(6)->toDateString()], 'One Way');

        $this->assertSame(TripType::OneWay, $booking->trip_type);
        $this->assertSame((string) $expected['total'], (string) $booking->estimated_fare);
        $this->assertNull($booking->return_date);
    }

    public function test_round_trip_booking_is_stored_as_outstation_and_shown_as_round_trip(): void
    {
        $return = now()->addDays(7)->toDateString();
        $booking = $this->book(['trip_type' => 'outstation', 'return_date' => $return], 'Round Trip');

        $this->assertSame('outstation', $booking->getRawOriginal('trip_type')); // stored value unchanged
        $this->assertSame($return, $booking->return_date->toDateString());
        $this->assertNull($booking->estimated_fare); // round trips are quoted by the team

        // Booking links use the round_trip alias.
        $this->get(route('booking.create', ['trip_type' => 'round_trip', 'pickup_location' => 'Chennai', 'destination_location' => 'Coimbatore']))
            ->assertOk()->assertSee('name="trip_type" value="outstation" checked', false)->assertSee('Round Trip selected');
    }

    public function test_every_form_and_listing_uses_the_same_four_names(): void
    {
        $banned = ['Rental Outstation', 'Outstation Rental', 'Rental One Way', 'One Way Trip', 'Outstation Taxi', 'Local Travel', 'Rental Trips', 'Full day (local)', '>Outstation<'];
        $pages = ['/', '/book', '/services', '/services/local-taxi', '/services/outstation-taxi', '/services/rentals', '/routes/chennai-to-coimbatore-taxi', '/tamil-nadu/coimbatore'];

        foreach ($pages as $url) {
            $response = $this->get($url)->assertOk();
            foreach ($banned as $label) {
                $response->assertDontSee($label, false);
            }
        }

        // Homepage / district panel: the four tabs in order, One Way selected; Airport is not a tab.
        $this->get('/')->assertSeeInOrder(['value="one_way"', 'value="round_trip"', 'value="local"', 'value="rental"'], false)
            ->assertSee('name="trip_type" value="one_way" class="sr-only" checked', false)
            ->assertDontSee('name="trip_type" value="airport"', false)
            ->assertSee('Chennai, Chennai International Airport (MAA)', false); // airports remain One Way drops

        // Booking page and sidebar widget tabs.
        $this->get('/book')->assertSeeInOrder(['value="one_way"', 'value="outstation"', 'value="local"', 'value="rental"'], false)
            ->assertSeeInOrder(['One Way', 'Round Trip', 'Local', 'Rental']);
        $this->get('/services/local-taxi')->assertSee('name="trip_type" value="local" checked', false)->assertSee('Local Taxi');

        // Header Services dropdown and footer use the standard names.
        $this->get('/')->assertSee('Round Trip Taxi')->assertSee('Rental Taxi')->assertSee('Local Taxi');

        // Admin booking filter lists exactly the four.
        $this->actingAs(User::where('is_admin', true)->firstOrFail())
            ->get(route('admin.bookings.index'))->assertOk()
            ->assertSeeInOrder(['<option value="one_way"', 'One Way', '<option value="outstation"', 'Round Trip', '<option value="local"', 'Local', '<option value="rental"', 'Rental'], false);
    }

    public function test_quick_quote_messages_name_the_trip_types(): void
    {
        $calc = app(FareCalculator::class);
        $chennai = City::where('name', 'Chennai')->first();

        $this->assertStringContainsString('Local trips', $calc->quickQuote($chennai, null, TripType::Local, 'Chennai', 'T. Nagar')['message']);
        $this->assertStringContainsString('Rental packages', $calc->quickQuote($chennai, null, TripType::Rental)['message']);
        $this->assertStringContainsString('Round Trip', (string) $calc->quickQuote($chennai, City::where('name', 'Coimbatore')->first(), TripType::Outstation)['message']);
    }
}
