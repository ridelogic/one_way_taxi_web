<?php

namespace App\Http\Requests\Admin;

class FaqRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:5000'],
            'category' => ['required', 'string', 'max:60'],
            'show_on_home' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->booleans(['show_on_home', 'is_active']);
        $this->merge(['sort_order' => (int) $this->input('sort_order', 0), 'category' => $this->input('category') ?: 'General']);
    }
}
