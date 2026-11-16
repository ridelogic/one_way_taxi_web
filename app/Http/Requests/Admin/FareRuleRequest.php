<?php

namespace App\Http\Requests\Admin;

class FareRuleRequest extends AdminRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $money = ['required', 'numeric', 'min:0', 'max:9999999', 'decimal:0,2'];

        return [
            'base_fare' => $money,
            'per_km_rate' => ['required', 'numeric', 'min:0.01', 'max:10000', 'decimal:0,2'],
            'minimum_km' => ['required', 'integer', 'min:0', 'max:5000'],
            'minimum_fare' => $money,
            'driver_allowance' => $money,
            'additional_charges' => ['nullable', 'array', 'max:15'],
            'additional_charges.*.label' => ['required_with:additional_charges.*.amount', 'nullable', 'string', 'max:80'],
            'additional_charges.*.amount' => ['nullable', 'numeric', 'min:0', 'max:999999', 'decimal:0,2'],
            'additional_charges.*.note' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'additional_charges.*.label' => 'charge label',
            'additional_charges.*.amount' => 'charge amount',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->booleans(['is_active']);
        $clean = fn ($v) => is_string($v) ? str_replace([',', '₹', ' '], '', $v) : $v;

        $text = fn ($v) => is_string($v) ? trim($v) : '';

        $charges = collect((array) $this->input('additional_charges', []))
            ->map(fn ($c) => [
                'label' => $text($c['label'] ?? ''),
                'amount' => ($c['amount'] ?? '') === '' ? null : $clean($c['amount']),
                'note' => $text($c['note'] ?? '') ?: null,
            ])
            ->filter(fn ($c) => $c['label'] !== '' || $c['amount'] !== null)
            ->values()->all();

        $this->merge([
            'base_fare' => $clean($this->input('base_fare', '0')) ?: '0',
            'per_km_rate' => $clean($this->input('per_km_rate')),
            'minimum_fare' => $clean($this->input('minimum_fare', '0')) ?: '0',
            'driver_allowance' => $clean($this->input('driver_allowance', '0')) ?: '0',
            'additional_charges' => $charges,
        ]);
    }
}
