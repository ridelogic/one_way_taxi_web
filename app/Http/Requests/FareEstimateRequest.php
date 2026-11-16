<?php

namespace App\Http\Requests;

use App\Enums\TripType;
use App\Models\City;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Pickup and destination are free text (the form suggests configured cities through a datalist).
 * They are matched to City records when possible so a fare can be estimated.
 */
class FareEstimateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'trip_type' => ['required', Rule::enum(TripType::class)],
            'pickup_location' => ['required', 'string', 'min:2', 'max:120'],
            'destination_location' => ['nullable', 'required_unless:trip_type,rental', 'string', 'min:2', 'max:120'],
            'vehicle_category_id' => ['nullable', 'integer', Rule::exists('vehicle_categories', 'id')->where('is_active', true)],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->tripType() === TripType::Rental) {
                    return;
                }

                $pickup = mb_strtolower(trim((string) $this->input('pickup_location')));
                $destination = mb_strtolower(trim((string) $this->input('destination_location')));

                if ($pickup !== '' && $pickup === $destination) {
                    $validator->errors()->add('destination_location', 'Pickup and destination must be different.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('trip_type')) {
            $this->merge(['trip_type' => TripType::OneWay->value]);
        }
    }

    public function tripType(): TripType
    {
        return TripType::tryFrom((string) $this->input('trip_type')) ?? TripType::OneWay;
    }

    public function pickupCity(): ?City
    {
        return City::resolve($this->input('pickup_location'));
    }

    public function destinationCity(): ?City
    {
        return $this->tripType() === TripType::Rental ? null : City::resolve($this->input('destination_location'));
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'trip_type' => 'trip type',
            'pickup_location' => 'pickup city',
            'destination_location' => 'destination',
            'vehicle_category_id' => 'vehicle type',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['destination_location.required_unless' => 'Please enter your destination.'];
    }
}
