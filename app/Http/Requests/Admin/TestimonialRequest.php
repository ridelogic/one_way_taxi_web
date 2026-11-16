<?php

namespace App\Http\Requests\Admin;

class TestimonialRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'trip' => ['nullable', 'string', 'max:150'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'quote' => ['required', 'string', 'max:1500'],
            'is_sample' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->booleans(['is_sample', 'is_active']);
        $this->merge(['sort_order' => (int) $this->input('sort_order', 0), 'rating' => $this->input('rating') ?: null]);
    }
}
