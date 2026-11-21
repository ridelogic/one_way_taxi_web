<?php

namespace App\Http\Requests;

use App\Enums\TripType;
use App\Models\VehicleCategory;
use App\Rules\IndianMobile;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Used by both the homepage booking widget (route, date, mobile, email, consent) and the
 * full /book form (which adds name, time, vehicle, addresses and notes).
 */
class StoreBookingRequest extends FareEstimateRequest
{
    /**
     * The homepage booking panel asks for route, date, mobile and email only;
     * pickup time and vehicle are confirmed by phone.
     */
    public function fromHomepagePanel(): bool
    {
        return $this->input('source') === 'homepage';
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $panel = $this->fromHomepagePanel();

        return array_merge(parent::rules(), [
            'rental_package' => ['nullable', 'required_if:trip_type,rental', 'string', Rule::in(settings()->lines('rental_packages'))],
            'pickup_address' => ['nullable', 'string', 'max:255'],
            'drop_address' => ['nullable', 'string', 'max:255'],
            'travel_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.now()->addYear()->toDateString()],
            'travel_time' => [$panel ? 'nullable' : 'required', 'date_format:H:i'],
            'vehicle_category_id' => [$panel ? 'nullable' : 'required', 'integer', Rule::exists('vehicle_categories', 'id')->where('is_active', true)],
            'return_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:travel_date'],
            'passengers' => ['nullable', 'integer', 'min:1', 'max:20'],
            'customer_name' => ['nullable', 'string', 'min:2', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:20', new IndianMobile],
            'customer_email' => [$panel ? 'required' : 'nullable', 'string', 'email:rfc', 'max:150'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
            'website' => ['prohibited'], // honeypot — must stay empty
            'consent' => ['accepted'],
        ]);
    }

    /** Cross-field checks: pickup lead time and vehicle capacity. */
    public function after(): array
    {
        return array_merge(parent::after(), [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['travel_date', 'travel_time']) || ! $this->filled('travel_time')) {
                    return; // without a pickup time, the date rules (today or later) apply
                }

                $pickupAt = Carbon::createFromFormat('Y-m-d H:i', $this->input('travel_date').' '.$this->input('travel_time'));
                $leadHours = (int) settings('min_hours_before_pickup', 2);

                if ($pickupAt->lt(now()->addHours($leadHours))) {
                    $validator->errors()->add('travel_time', $leadHours > 0
                        ? "Please choose a pickup time at least {$leadHours} hour(s) from now."
                        : 'The pickup time cannot be in the past.');
                }
            },
            function (Validator $validator) {
                $passengers = $this->integer('passengers');
                $vehicle = $passengers ? VehicleCategory::find($this->integer('vehicle_category_id')) : null;

                if ($vehicle && $passengers > $vehicle->passenger_capacity) {
                    $validator->errors()->add('passengers', "The {$vehicle->name} seats up to {$vehicle->passenger_capacity} passengers.");
                }
            },
        ]);
    }

    /** Validation errors from the homepage widget scroll the visitor back to the widget. */
    protected function getRedirectUrl(): string
    {
        $previous = url()->previous();

        return str_contains($previous, '/book') ? $previous : strtok($previous, '#').'#book-ride';
    }

    public function normalizedPhone(): string
    {
        return IndianMobile::normalize($this->input('customer_phone')) ?? (string) $this->input('customer_phone');
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        // Rental has no destination; only Round Trip ('outstation') has a return date; only Rental has a package.
        $type = $this->input('trip_type');
        if ($type === TripType::Rental->value) {
            $this->merge(['destination_location' => null]);
        }
        if ($type !== TripType::Outstation->value) {
            $this->merge(['return_date' => null]);
        }
        if ($type !== TripType::Rental->value) {
            $this->merge(['rental_package' => null]);
        }
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'rental_package' => 'rental package',
            'travel_date' => 'travel date',
            'travel_time' => 'pickup time',
            'return_date' => 'return date',
            'customer_name' => 'name',
            'customer_phone' => 'mobile number',
            'customer_email' => 'email address',
            'customer_notes' => 'notes',
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'travel_date.after_or_equal' => 'The travel date cannot be in the past.',
            'return_date.after_or_equal' => 'The return date must be on or after the travel date.',
            'rental_package.required_if' => 'Please choose a rental package.',
            'customer_email.email' => 'Please enter a valid email address.',
            'consent.accepted' => 'Please accept the privacy policy so we can contact you about this booking.',
        ]);
    }
}
