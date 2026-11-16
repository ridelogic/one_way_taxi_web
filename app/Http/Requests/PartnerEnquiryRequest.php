<?php

namespace App\Http\Requests;

use App\Models\PartnerEnquiry;
use App\Rules\IndianMobile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartnerEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return settings()->bool('partner_programme_open');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'max:20', new IndianMobile],
            'email' => ['nullable', 'string', 'email:rfc', 'max:150'],
            'city' => ['required', 'string', 'min:2', 'max:100'],
            'partner_type' => ['required', Rule::in(array_keys(PartnerEnquiry::PARTNER_TYPES))],
            'vehicle_type' => ['nullable', Rule::in(array_keys(PartnerEnquiry::VEHICLE_TYPES))],
            'vehicle_count' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'has_commercial_licence' => ['boolean'],
            'message' => ['nullable', 'string', 'max:2000'],
            'consent' => ['accepted'],
            'website' => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['consent.accepted' => 'Please accept the privacy policy so we can contact you.'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['has_commercial_licence' => $this->boolean('has_commercial_licence')]);
    }

    protected function getRedirectUrl(): string
    {
        return route('partner').'#register';
    }
}
