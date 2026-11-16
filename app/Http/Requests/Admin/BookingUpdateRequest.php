<?php

namespace App\Http\Requests\Admin;

use App\Enums\BookingStatus;
use Illuminate\Validation\Rule;

class BookingUpdateRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(BookingStatus::class)],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'estimated_fare' => ['nullable', 'numeric', 'min:0', 'max:9999999', 'decimal:0,2'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $fare = $this->input('estimated_fare');
        $this->merge(['estimated_fare' => is_string($fare) ? (str_replace([',', '₹', ' '], '', $fare) ?: null) : $fare]);
    }
}
