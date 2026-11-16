<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FareRule extends Model
{
    protected $fillable = [
        'vehicle_category_id', 'base_fare', 'per_km_rate', 'minimum_km', 'minimum_fare',
        'driver_allowance', 'additional_charges', 'notes', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            // Money is kept as decimal strings and converted to integer paise in FareCalculator.
            'base_fare' => 'decimal:2',
            'per_km_rate' => 'decimal:2',
            'minimum_fare' => 'decimal:2',
            'driver_allowance' => 'decimal:2',
            'minimum_km' => 'integer',
            'additional_charges' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function vehicleCategory(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class);
    }

    /** @return array<int, array{label: string, amount: ?string, note: ?string}> */
    public function charges(): array
    {
        return array_values(array_filter($this->additional_charges ?? [], fn ($c) => ! empty($c['label'])));
    }
}
