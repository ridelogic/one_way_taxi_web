<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class HeroSlideRequest extends AdminRequest
{
    private const URL = 'regex:/^(\/|https?:\/\/|tel:|mailto:|#)/i';

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'eyebrow' => ['nullable', 'string', 'max:120'],
            'heading' => ['required', 'string', 'max:160'],
            'highlight' => ['nullable', 'string', 'max:120'],
            'subheading' => ['nullable', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:400'],
            'media_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')],
            'foreground_media_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')],
            'primary_label' => ['nullable', 'required_with:primary_url', 'string', 'max:60'],
            'primary_url' => ['nullable', 'required_with:primary_label', 'string', 'max:255', self::URL],
            'secondary_label' => ['nullable', 'required_with:secondary_url', 'string', 'max:60'],
            'secondary_url' => ['nullable', 'required_with:secondary_label', 'string', 'max:255', self::URL],
            'overlay_strength' => ['required', 'integer', 'between:0,90'],
            'text_align' => ['required', Rule::in(['left', 'center'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'regex' => 'Links must start with /, http(s)://, tel:, mailto: or #.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->booleans(['is_active']);
        $this->merge(['sort_order' => (int) $this->input('sort_order', 0)]);
    }
}
