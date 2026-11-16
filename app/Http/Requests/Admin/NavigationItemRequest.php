<?php

namespace App\Http\Requests\Admin;

use App\Models\NavigationItem;
use Illuminate\Validation\Rule;

class NavigationItemRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'menu' => ['required', Rule::in(array_keys(NavigationItem::MENUS))],
            'label' => ['required', 'string', 'max:80'],
            'url' => ['required', 'string', 'max:255', 'regex:/^(\/|https?:\/\/|tel:|mailto:|#)/i'],
            'open_in_new_tab' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['url.regex' => 'Use a site path such as /routes, or a full https:// link.'];
    }

    protected function prepareForValidation(): void
    {
        $this->booleans(['open_in_new_tab', 'is_active']);
        $this->merge(['sort_order' => (int) $this->input('sort_order', 0)]);
    }
}
