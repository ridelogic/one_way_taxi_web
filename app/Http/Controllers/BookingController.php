<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Enums\TripType;
use App\Http\Requests\FareEstimateRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\City;
use App\Models\VehicleCategory;
use App\Services\FareCalculator;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    private const SESSION_KEY = 'booking_references';

    public function __construct(private readonly FareCalculator $calculator) {}

    /** Full booking page. Accepts quick-quote fields as query parameters and shows an estimate. */
    public function create(Request $request): View
    {
        $vehicles = VehicleCategory::active()->with('media')->ordered()->get();
        // Booking links use trip_type=round_trip for Round Trip (stored as 'outstation'); unknown values fall back to One Way.
        $tripType = TripType::fromInput($request->query('trip_type'));
        $isRoundTrip = $tripType === TripType::Outstation;
        $travelDate = $this->validDate($request->query('travel_date'));
        $returnDate = $this->validDate($request->query('return_date'));

        $pickupText = Str::limit(trim((string) $request->query('pickup_location')), 120, '');
        $destinationText = Str::limit(trim((string) $request->query('destination_location')), 120, '');
        $vehicle = $vehicles->firstWhere('id', $request->integer('vehicle_category_id'));

        $estimate = null;
        if ($pickupText !== '' && ($tripType === TripType::Rental || ($destinationText !== '' && mb_strtolower($pickupText) !== mb_strtolower($destinationText)))) {
            $estimate = $this->quote($tripType, City::resolve($pickupText), City::resolve($destinationText), $vehicle, $pickupText, $destinationText);
        }

        return view('booking.create', [
            'cities' => City::active()->ordered()->get(),
            'vehicles' => $vehicles,
            'estimate' => $estimate,
            'tripType' => $tripType,
            'isRoundTrip' => $isRoundTrip,
            'prefill' => [
                'trip_type' => $tripType->value,
                'pickup_location' => $pickupText,
                'destination_location' => $destinationText,
                'vehicle_category_id' => $vehicle?->id,
                'travel_date' => $travelDate,
                'return_date' => $tripType === TripType::Outstation && $returnDate && $returnDate >= ($travelDate ?? $returnDate) ? $returnDate : null,
                'travel_time' => preg_match('/^\d{2}:\d{2}$/', (string) $request->query('travel_time')) ? $request->query('travel_time') : null,
                'rental_package' => in_array($request->query('rental_package'), settings()->lines('rental_packages'), true) ? $request->query('rental_package') : null,
            ],
            'seo' => Seo::make('Book a Taxi — Local, Rental, One Way or Round Trip', 'Book a Local, Rental, One Way or Round Trip taxi with a fare estimate where available. Our team confirms every booking by phone.'),
        ]);
    }

    /** JSON fare estimate for the booking forms. */
    public function estimate(FareEstimateRequest $request): JsonResponse
    {
        $vehicle = $request->filled('vehicle_category_id') ? VehicleCategory::find($request->integer('vehicle_category_id')) : null;

        return response()->json($this->quote(
            $request->tripType(),
            $request->pickupCity(),
            $request->destinationCity(),
            $vehicle,
            $request->input('pickup_location'),
            $request->input('destination_location'),
        ));
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $tripType = $request->tripType();
        $pickup = $request->pickupCity();
        $destination = $request->destinationCity();
        $vehicle = ! empty($data['vehicle_category_id']) ? VehicleCategory::find($data['vehicle_category_id']) : null;

        // Always recalculate on the server — never trust a fare sent by the browser.
        $estimate = $this->quote($tripType, $pickup, $destination, $vehicle, $data['pickup_location'], $data['destination_location'] ?? null);
        $isFullEstimate = $estimate['available'] && empty($estimate['starting_from']);

        $booking = DB::transaction(fn () => Booking::create([
            'reference' => Booking::generateReference(),
            'trip_type' => $tripType,
            'rental_package' => $data['rental_package'] ?? null,
            'customer_name' => $data['customer_name'] ?? null,
            'customer_phone' => $request->normalizedPhone(),
            'customer_email' => isset($data['customer_email']) ? mb_strtolower(trim($data['customer_email'])) : null,
            'pickup_city_id' => $pickup?->id,
            'destination_city_id' => $destination?->id,
            'pickup_location' => trim($data['pickup_location']),
            'drop_location' => isset($data['destination_location']) ? trim($data['destination_location']) : null,
            'pickup_address' => $data['pickup_address'] ?? null,
            'drop_address' => $data['drop_address'] ?? null,
            'taxi_route_id' => $estimate['route_id'],
            'vehicle_category_id' => $vehicle?->id,
            'travel_date' => $data['travel_date'],
            'travel_time' => $data['travel_time'] ?? null,
            'return_date' => $data['return_date'] ?? null,
            'passengers' => $data['passengers'] ?? null,
            'customer_notes' => $data['customer_notes'] ?? null,
            'estimated_fare' => $isFullEstimate ? $estimate['total'] : null,
            'fare_breakdown' => $estimate['available'] ? [
                'lines' => $estimate['lines'],
                'excluded' => $estimate['excluded'],
                'basis' => $estimate['basis'] ?? 'starting_fare',
                'starting_fare' => ! $isFullEstimate ? $estimate['total'] : null,
                'distance_km' => $estimate['distance_km'],
                'is_sample' => $estimate['is_sample'],
            ] : null,
            'status' => BookingStatus::Pending,
            'source' => match ($request->input('source')) {
                'homepage' => 'homepage booking panel',
                'widget' => 'homepage widget',
                default => 'website',
            },
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
        ]));

        // Only the browser that created the booking may view its confirmation page.
        $references = array_slice(array_merge($request->session()->get(self::SESSION_KEY, []), [$booking->reference]), -10);
        $request->session()->put(self::SESSION_KEY, $references);

        return redirect()->route('booking.confirmation', $booking->reference);
    }

    public function confirmation(Request $request, string $reference): View
    {
        abort_unless(in_array($reference, $request->session()->get(self::SESSION_KEY, []), true), 404);

        $booking = Booking::with(['pickupCity', 'destinationCity', 'vehicleCategory', 'route'])
            ->where('reference', $reference)
            ->firstOrFail();

        return view('booking.confirmation', [
            'booking' => $booking,
            'seo' => Seo::make('Booking Request Received')->noindex(),
        ]);
    }

    /** Full per-vehicle estimate for one-way trips with a vehicle; otherwise the route's starting fare. */
    private function quote(TripType $tripType, ?City $pickup, ?City $destination, ?VehicleCategory $vehicle, ?string $pickupText, ?string $destinationText): array
    {
        if ($tripType === TripType::OneWay && $vehicle) {
            return $this->calculator->estimate($pickup, $destination, $vehicle, $pickupText, $destinationText) + ['starting_from' => false];
        }

        return $this->calculator->quickQuote($pickup, $destination, $tripType, $pickupText, $destinationText);
    }

    private function validDate(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return $value >= now()->toDateString() ? $value : null;
    }
}
