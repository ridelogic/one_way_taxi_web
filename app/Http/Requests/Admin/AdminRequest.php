<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Base request for the admin CMS: only authenticated administrators may submit. */
abstract class AdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * Readable field names for validation messages, derived from the rule keys
     * ("origin_city_id" → "origin city", "media_id" → "image", "distance_km" → "distance (km)").
     * Requests add or override names in fieldNames().
     */
    public function attributes(): array
    {
        $names = [];
        foreach (array_keys($this->rules()) as $key) {
            if (str_contains($key, '*')) {
                continue;
            }
            $label = preg_replace(['/_id$/', '/(^|_)media$/', '/_km$/', '/_minutes$/'], ['', '$1image', ' (km)', ' (minutes)'], $key);
            $names[$key] = str_replace('_', ' ', $label);
        }

        return array_merge($names, $this->fieldNames());
    }

    /** @return array<string, string> */
    protected function fieldNames(): array
    {
        return [];
    }

    /** An input as text for normalising before validation; arrays (e.g. title[]=x) count as empty so the rules reject them. */
    protected function text(string $key): string
    {
        $value = $this->input($key);

        return is_string($value) ? $value : '';
    }

    /** Checkbox inputs are absent when unticked; normalise them to booleans. */
    protected function booleans(array $keys): void
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->boolean($key);
        }
        $this->merge($values);
    }
}
