<?php

namespace App\Http\Requests;

use App\Models\CorporateEnquiry;
use App\Rules\IndianMobile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CorporateEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'min:2', 'max:150'],
            'contact_name' => ['required', 'string', 'min:2', 'max:120'],
            'designation' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20', new IndianMobile],
            'email' => ['required', 'string', 'email:rfc', 'max:150'],
            'city' => ['nullable', 'string', 'max:100'],
            'requirement' => ['required', Rule::in(array_keys(CorporateEnquiry::REQUIREMENTS))],
            'monthly_trips' => ['nullable', Rule::in(array_keys(CorporateEnquiry::MONTHLY_TRIPS))],
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

    protected function getRedirectUrl(): string
    {
        return route('corporate').'#enquiry';
    }
}
