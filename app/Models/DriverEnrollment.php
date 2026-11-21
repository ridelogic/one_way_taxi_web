<?php

namespace App\Models;

use App\Support\Reference;
use Illuminate\Database\Eloquent\Model;

class DriverEnrollment extends Model
{
    public const PENDING = 'pending';

    public const SENT = 'sent';

    public const FAILED = 'failed';

    protected $guarded = ['id'];

    /** Personal data never leaks through accidental serialisation. */
    protected $hidden = ['password', 'phone', 'email', 'national_id', 'address', 'ip_address', 'user_agent', 'files'];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'licence_expiry' => 'date',
            'badge_expiry' => 'date',
            'files' => 'array',
            'password' => 'encrypted',
            'sent_at' => 'datetime',
        ];
    }

    public static function generateReference(): string
    {
        return Reference::generate('DRV', static::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}
