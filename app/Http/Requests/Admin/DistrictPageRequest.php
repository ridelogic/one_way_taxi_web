<?php

namespace App\Http\Requests\Admin;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** District Management → District Details tab (and adding a district). Other tabs validate in DistrictCmsController. */
class DistrictPageRequest extends AdminRequest
{
    /** Service description keys editors may override (the booking trip types plus airport transfers). */
    public const SERVICE_KEYS = ['one_way' => 'One Way Taxi', 'round_trip' => 'Round Trip Taxi', 'local' => 'Local Taxi', 'rental' => 'Rental Taxi', 'airport' => 'Airport Transfers'];

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('district_pages', 'slug')->ignore($this->route('district'))],
            'state' => ['required', 'string', 'max:100'],
            'headquarters' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:60'],
            'heading' => ['nullable', 'string', 'max:160'],
            'intro' => ['nullable', 'string', 'max:500'],
            'summary' => ['nullable', 'string', 'max:200'],
            'content' => ['nullable', 'string', 'max:30000'],
            'nearest_airport' => ['nullable', 'string', 'max:160'],
            'hero_media_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')],
            'card_media_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')],
            'is_published' => ['boolean'],
            'content_reviewed' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->booleans(['is_published', 'content_reviewed']);
        $this->merge([
            'slug' => Str::slug($this->text('slug') ?: $this->text('name')),
            'state' => $this->text('state') ?: 'Tamil Nadu',
            'sort_order' => (int) $this->input('sort_order', 0),
        ]);
    }

    protected function fieldNames(): array
    {
        return [
            'heading' => 'page title',
            'summary' => 'short description',
            'content' => 'main description',
            'hero_media_id' => 'banner image',
            'card_media_id' => 'featured image',
        ];
    }
}
