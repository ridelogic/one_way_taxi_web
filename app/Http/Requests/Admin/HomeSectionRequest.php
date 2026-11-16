<?php

namespace App\Http\Requests\Admin;

use App\Models\PageSection;
use Illuminate\Validation\Rule;

/**
 * Validation rules are generated from the section schema in config/sections.php.
 */
class HomeSectionRequest extends AdminRequest
{
    private const URL_PATTERN = '/^(\/|https?:\/\/|tel:|mailto:|#)/i';

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var PageSection $section */
        $section = $this->route('section');
        $schema = $section->schema();

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'media_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')],
            'content' => ['nullable', 'array'],
        ];

        foreach ($schema['fields'] ?? [] as $key => $field) {
            $rules['content.'.$key] = $this->fieldRules($field);
        }

        if (isset($schema['items'])) {
            $rules['content.items'] = ['nullable', 'array', 'max:'.($schema['items']['max'] ?? 12)];
            foreach ($schema['items']['fields'] as $key => $field) {
                $rules['content.items.*.'.$key] = $this->fieldRules($field);
            }
        }

        return $rules;
    }

    /** @return array<int, mixed> */
    private function fieldRules(array $field): array
    {
        return match ($field['type']) {
            'textarea' => ['nullable', 'string', 'max:2000'],
            'url' => ['nullable', 'string', 'max:255', 'regex:'.self::URL_PATTERN],
            'number' => ['nullable', 'integer', 'min:'.($field['min'] ?? 0), 'max:'.($field['max'] ?? 100000)],
            'boolean' => ['nullable', 'boolean'],
            'icon' => ['nullable', 'string', Rule::in(array_keys(config('icons')))],
            'media' => ['nullable', 'integer', Rule::exists('media_assets', 'id')],
            default => ['nullable', 'string', 'max:255'],
        };
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['regex' => 'Links must start with /, http(s)://, tel:, mailto: or #.'];
    }

    protected function prepareForValidation(): void
    {
        $this->booleans(['is_active']);
    }
}
