<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'subject', 'message', 'read_at', 'ip_address'];

    protected $hidden = ['email', 'phone', 'ip_address'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
