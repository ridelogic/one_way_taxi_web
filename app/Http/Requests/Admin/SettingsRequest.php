<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/** Rules come from config/settings.php so new settings only need a schema entry. */
class SettingsRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [];

        foreach ($this->fields() as $key => $field) {
            $rules[$key] = match ($field['type']) {
                'boolean' => ['boolean'],
                'media' => ['nullable', 'integer', Rule::exists('media_assets', 'id')],
                default => $field['rules'] ?? ['nullable', 'string', 'max:500'],
            };
        }

        return $rules;
    }

    /** @return array<string, array> */
    public function fields(): array
    {
        $fields = [];
        foreach (config('settings') as $group) {
            $fields += $group['fields'];
        }

        return $fields;
    }

    protected function prepareForValidation(): void
    {
        $booleans = array_keys(array_filter($this->fields(), fn ($f) => $f['type'] === 'boolean'));
        $this->booleans($booleans);

        // Saving the form unchanged must not change stored values: browsers submit colour pickers in lowercase
        // and textarea line breaks as CRLF, so normalise both back to the stored form (#RRGGBB, LF).
        $normalised = [];
        foreach ($this->fields() as $key => $field) {
            $value = $this->input($key);
            if (! is_string($value)) {
                continue;
            }
            $normalised[$key] = $field['type'] === 'color' ? strtoupper($value) : str_replace("\r\n", "\n", $value);
        }
        $this->merge($normalised);
    }
}
