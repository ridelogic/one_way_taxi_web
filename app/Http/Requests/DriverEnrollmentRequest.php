<?php

namespace App\Http\Requests;

use App\Rules\IndianMobile;
use App\Services\Fleet\DriverEnrollmentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Website driver + taxi enrollment. Rules mirror the fleet admin's Model_Add::validate_adddriver(). */
class DriverEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $service = app(DriverEnrollmentService::class);
        $o = $service->options();
        $values = fn (string $key) => array_map('strval', array_column($o[$key], 'value'));
        $doc = ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'];
        $image = ['required', 'file', 'mimes:jpg,jpeg,png', 'max:4096'];

        return [
            // Driver
            'first_name' => ['required', 'alpha', 'max:30'],
            'last_name' => ['required', 'alpha', 'max:30'],
            'gender' => ['required', Rule::in(['Male', 'Female', 'Others'])],
            'dob' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now()->subYears(18)->toDateString(), 'after:'.now()->subYears(80)->toDateString()],
            'phone' => ['required', 'string', 'max:20', new IndianMobile],
            'email' => ['nullable', 'email:rfc', 'max:75'],
            'password' => $service->passwordRequired() ? ['required', 'string', 'min:6', 'max:20', 'confirmed'] : ['nullable'],
            'national_id' => ['nullable', 'alpha_num', 'size:11'],
            'address' => ['nullable', 'string', 'max:255'],
            'licence_no' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9\- ]+$/'],
            'licence_expiry' => ['required', 'date_format:Y-m-d', 'after:today'],
            'badge_expiry' => ['required', 'date_format:Y-m-d', 'after:today'],
            'photo' => $image,
            'licence_front' => $doc,
            'licence_back' => $doc,
            'badge_document' => $doc,
            // Taxi
            'fleet_model_id' => ['required', 'integer', Rule::in(array_column($o['models'], 'value'))],
            'state_value' => ['required', Rule::in($values('states'))],
            'prefix_value' => ['required', Rule::in($values('prefixes'))],
            'plate_number' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9]+$/'],
            'manufacturer_value' => ['required', Rule::in($values('manufacturers'))],
            'manufacturing_year' => ['required', 'integer', 'min:2005', 'max:'.(now()->year + 1)],
            'colour' => ['nullable', Rule::in($values('colours'))],
            'owner_name' => ['nullable', 'string', 'min:4', 'max:30'],
            'taxi_image' => $image,
            'rc_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:4096'],
            'website' => ['prohibited'], // honeypot
            'consent' => ['accepted'],
        ];
    }

    public function normalizedPhone(): string
    {
        $n = IndianMobile::normalize($this->input('phone')) ?? (string) $this->input('phone');

        return substr(preg_replace('/\D/', '', $n), -10);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'plate_number' => strtoupper(preg_replace('/\s+/', '', (string) $this->input('plate_number'))),
            'licence_no' => strtoupper(trim((string) $this->input('licence_no'))),
            'national_id' => strtoupper(trim((string) $this->input('national_id'))) ?: null,
        ]);
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'dob' => 'date of birth', 'licence_no' => 'driving licence number', 'licence_expiry' => 'licence expiry date',
            'badge_expiry' => 'badge / FC expiry date', 'licence_front' => 'licence (front)', 'licence_back' => 'licence (back)',
            'badge_document' => 'badge / FC document', 'fleet_model_id' => 'vehicle type', 'state_value' => 'registration state',
            'prefix_value' => 'plate prefix', 'plate_number' => 'vehicle number', 'manufacturer_value' => 'manufacturer',
            'manufacturing_year' => 'manufacturing year', 'owner_name' => 'owner name', 'taxi_image' => 'vehicle photo',
            'rc_document' => 'RC book', 'national_id' => 'national ID',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'first_name.alpha' => 'Use letters only for the first name (no spaces).',
            'last_name.alpha' => 'Use letters only for the last name (no spaces).',
            'dob.before_or_equal' => 'Drivers must be at least 18 years old.',
            'consent.accepted' => 'Please accept the terms so we can verify your details.',
        ];
    }
}
