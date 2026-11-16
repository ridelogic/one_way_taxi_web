<?php

namespace App\Http\Requests\Admin;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:140', 'alpha_dash', Rule::unique('services', 'slug')->ignore($this->route('service'))],
            'icon' => ['required', Rule::in(array_keys(config('icons')))],
            'short_description' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:100000'],
            'highlights' => ['nullable', 'array', 'max:12'],
            'highlights.*' => ['string', 'max:120'],
            'media_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')],
            'show_on_home' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'seo_title' => ['nullable', 'string', 'max:120'],
            'seo_description' => ['nullable', 'string', 'max:300'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->booleans(['show_on_home', 'is_active']);
        $this->merge([
            'slug' => Str::slug($this->text('slug') ?: $this->text('title')),
            'sort_order' => (int) $this->input('sort_order', 0),
            'highlights' => array_values(array_filter(array_map('trim', preg_split('/\R/', $this->text('highlights'))))),
        ]);
    }
}
