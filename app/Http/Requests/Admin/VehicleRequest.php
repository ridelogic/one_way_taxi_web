<?php

namespace App\Http\Requests\Admin;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class VehicleRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('vehicle_categories', 'slug')->ignore($this->route('vehicle'))],
            'category' => ['nullable', 'string', 'max:50'],
            'example_models' => ['nullable', 'string', 'max:255'],
            'media_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')],
            'passenger_capacity' => ['required', 'integer', 'min:1', 'max:60'],
            'luggage_capacity' => ['required', 'integer', 'min:0', 'max:60'],
            'fleet_model_id' => ['nullable', 'integer', 'min:1'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'features' => ['nullable', 'array', 'max:20'],
            'features.*' => ['string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
            'seo_title' => ['nullable', 'string', 'max:120'],
            'seo_description' => ['nullable', 'string', 'max:300'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->booleans(['is_active']);
        $this->merge([
            'slug' => Str::slug($this->text('slug') ?: $this->text('name')),
            'sort_order' => (int) $this->input('sort_order', 0),
            'features' => array_values(array_filter(array_map('trim', preg_split('/\R/', $this->text('features'))))),
        ]);
    }
}
