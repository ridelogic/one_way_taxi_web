<?php

namespace App\Http\Requests\Admin;

use App\Models\Page;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Page|null $page */
        $page = $this->route('page');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('pages', 'slug')->ignore($page?->id),
                Rule::notIn($page?->slug === 'home' ? [] : array_merge(Page::RESERVED_SLUGS, ['home'])),
            ],
            'template' => ['required', Rule::in(array_merge(array_keys(Page::TEMPLATES), $page?->slug === 'home' ? ['home'] : []))],
            'heading' => ['nullable', 'string', 'max:255'],
            'subheading' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:200000'],
            'hero_media_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')],
            'status' => ['required', Rule::in(array_keys(Page::STATUSES))],
            'show_in_nav' => ['boolean'],
            'nav_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'seo_title' => ['nullable', 'string', 'max:120'],
            'seo_description' => ['nullable', 'string', 'max:300'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'slug.regex' => 'The URL slug may only contain lowercase letters, numbers and hyphens.',
            'slug.not_in' => 'That URL is reserved by the website. Please choose another.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->booleans(['show_in_nav']);
        $this->merge(['slug' => Str::slug($this->text('slug') ?: $this->text('title'))]);

        // The home page keeps its slug.
        if ($this->route('page')?->slug === 'home') {
            $this->merge(['slug' => 'home']);
        }
    }
}
