<?php

namespace App\Http\Requests\Admin;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CityRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('cities', 'slug')->ignore($this->route('city'))],
            'state' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'media_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')],
            'is_popular_destination' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->booleans(['is_popular_destination', 'is_active']);
        $this->merge([
            'slug' => Str::slug($this->text('slug') ?: $this->text('name')),
            'sort_order' => (int) $this->input('sort_order', 0),
        ]);
    }
}
