<?php

namespace App\Enums;

enum TripType: string
{
    case OneWay = 'one_way';
    case Outstation = 'outstation';
    case Rental = 'rental';

    public function label(): string
    {
        return match ($this) {
            self::OneWay => 'One Way Trip',
            self::Outstation => 'Outstation',
            self::Rental => 'Rental',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::OneWay => 'route',
            self::Outstation => 'map',
            self::Rental => 'clock',
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
