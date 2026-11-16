<?php

namespace App\Models;

use App\Enums\EnquiryStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CorporateEnquiry extends Model
{
    public const REQUIREMENTS = [
        'employee_commute' => 'Employee transport',
        'airport' => 'Airport transfers',
        'outstation' => 'Outstation / intercity travel',
        'events' => 'Events & guest transport',
        'monthly_rental' => 'Monthly car rental',
        'other' => 'Other',
    ];

    public const MONTHLY_TRIPS = ['1-10' => '1–10', '11-50' => '11–50', '51-200' => '51–200', '200+' => '200+'];

    protected $fillable = [
        'reference', 'company_name', 'contact_name', 'designation', 'phone', 'email', 'city',
        'requirement', 'monthly_trips', 'message', 'status', 'admin_notes', 'ip_address',
    ];

    protected $hidden = ['phone', 'email', 'ip_address', 'admin_notes'];

    protected function casts(): array
    {
        return ['status' => EnquiryStatus::class];
    }

    public function requirementLabel(): string
    {
        return self::REQUIREMENTS[$this->requirement] ?? $this->requirement;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $term === '' ? $query : $query->where(fn (Builder $q) => $q
            ->where('reference', 'like', "%{$term}%")
            ->orWhere('company_name', 'like', "%{$term}%")
            ->orWhere('contact_name', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%"));
    }
}
