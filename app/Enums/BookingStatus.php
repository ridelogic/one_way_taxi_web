<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Contacted = 'contacted';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** CSS modifier used by admin status badges. */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Contacted => 'blue',
            self::Confirmed => 'green',
            self::Completed => 'slate',
            self::Cancelled => 'red',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
