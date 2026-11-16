<?php

namespace App\Http\Requests\Admin;

use App\Models\City;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TaxiRouteRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $route = $this->route('taxiRoute');
        $money = ['nullable', 'numeric', 'min:0', 'max:9999999', 'decimal:0,2'];

        return [
            'origin_city_id' => ['required', 'integer', Rule::exists('cities', 'id')],
            'destination_city_id' => [
                'required', 'integer', 'different:origin_city_id', Rule::exists('cities', 'id'),
                Rule::unique('taxi_routes', 'destination_city_id')
                    ->where('origin_city_id', $this->integer('origin_city_id'))
                    ->ignore($route?->id),
            ],
            'slug' => ['required', 'string', 'max:200', 'alpha_dash', Rule::unique('taxi_routes', 'slug')->ignore($route?->id)],
            'distance_km' => ['required', 'numeric', 'min:1', 'max:5000', 'decimal:0,1'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:6000'],
            'base_fare' => ['required', 'numeric', 'min:0', 'max:9999999', 'decimal:0,2'],
            'media_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'seo_title' => ['nullable', 'string', 'max:120'],
            'seo_description' => ['nullable', 'string', 'max:300'],
            'is_active' => ['boolean'],
            'is_popular' => ['boolean'],
            'is_sample_fare' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'fares' => ['nullable', 'array'],
            'fares.*' => $money,
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'destination_city_id.unique' => 'A route between these two cities already exists.',
            'destination_city_id.different' => 'Origin and destination must be different cities.',
            // The slug is generated from the two cities, so it is only missing when they are.
            'slug.required' => 'Choose the origin and destination cities, and the URL slug will be generated automatically.',
        ];
    }

    protected function fieldNames(): array
    {
        return ['base_fare' => 'starting fare', 'fares.*' => 'fixed fare', 'sort_order' => 'order', 'slug' => 'URL slug'];
    }

    protected function prepareForValidation(): void
    {
        $this->booleans(['is_active', 'is_popular', 'is_sample_fare']);

        $slug = $this->text('slug');
        if (! $slug) {
            $from = City::find($this->integer('origin_city_id'))?->name;
            $to = City::find($this->integer('destination_city_id'))?->name;
            $slug = $from && $to ? "{$from} to {$to} taxi" : '';
        }

        // Accept "₹4,999" style input for money and distance fields.
        $clean = fn ($v) => is_string($v) ? str_replace([',', '₹', ' '], '', $v) : $v;

        $this->merge([
            'slug' => Str::slug($slug),
            'sort_order' => (int) $this->input('sort_order', 0),
            'base_fare' => $clean($this->input('base_fare')),
            'distance_km' => $clean($this->input('distance_km')),
            'fares' => array_map($clean, (array) $this->input('fares', [])),
        ]);
    }
}
