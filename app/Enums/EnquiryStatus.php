<?php

namespace App\Enums;

/** Status of corporate and partner enquiries. */
enum EnquiryStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case InProgress = 'in_progress';
    case Approved = 'approved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In progress',
            default => ucfirst($this->value),
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'amber',
            self::Contacted => 'blue',
            self::InProgress => 'blue',
            self::Approved => 'green',
            self::Closed => 'slate',
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
