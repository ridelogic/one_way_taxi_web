<?php

namespace App\Http\Requests\Admin;

use App\Enums\EnquiryStatus;
use Illuminate\Validation\Rule;

class EnquiryUpdateRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(EnquiryStatus::class)],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
