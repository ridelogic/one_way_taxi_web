<?php

namespace App\Enums;

/**
 * The four booking categories, named the same everywhere (forms, booking pages, confirmation, admin, filters).
 *
 *   Local      'local'       point-to-point rides within a city (quoted by the team)
 *   Rental     'rental'      hour / package based hire with driver (quoted by the team)
 *   One Way    'one_way'     one-way outstation journey (per-vehicle fare from the route data)
 *   Round Trip 'outstation'  return outstation journey (quoted by the team; stored value kept for existing bookings)
 *
 * Stored values never change. Booking links may also say trip_type=round_trip (→ Round Trip).
 * Airport transfers are a service, not a trip type: they are booked as One Way trips.
 */
enum TripType: string
{
    // Display order everywhere: One Way, Round Trip, Local, Rental.
    case OneWay = 'one_way';
    case Outstation = 'outstation';
    case Local = 'local';
    case Rental = 'rental';

    public function label(): string
    {
        return match ($this) {
            self::Local => 'Local',
            self::Rental => 'Rental',
            self::OneWay => 'One Way',
            self::Outstation => 'Round Trip',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Local => 'map-pin',
            self::Rental => 'clock',
            self::OneWay => 'route',
            self::Outstation => 'swap',
        };
    }

    /** One-line explanation shown under the trip type choice. */
    public function description(): string
    {
        return match ($this) {
            self::Local => 'Point-to-point rides within the city.',
            self::Rental => 'A car with driver by the hour or package.',
            self::OneWay => 'One-way outstation drop — pay only for one side.',
            self::Outstation => 'Outstation journey with return to your pickup city.',
        };
    }

    /** Whether the trip has a drop location (every type except Rental). */
    public function hasDestination(): bool
    {
        return $this !== self::Rental;
    }

    /** Resolve a value from a link or form, including the "round_trip" alias used by booking links. */
    public static function fromInput(mixed $value): self
    {
        $value = is_string($value) ? $value : '';

        return $value === 'round_trip' ? self::Outstation : (self::tryFrom($value) ?? self::OneWay);
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
