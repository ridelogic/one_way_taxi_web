<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Money helpers. All arithmetic is done in integer paise to avoid floating point errors.
 */
final class Money
{
    /** Convert a decimal string/number such as "1234.50" to integer paise (123450). */
    public static function toPaise(string|int|float|null $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        $value = is_float($amount) ? number_format($amount, 2, '.', '') : trim((string) $amount);

        if (! preg_match('/^(-)?(\d+)(?:\.(\d{1,2})\d*)?$/', $value, $m)) {
            throw new InvalidArgumentException("Invalid money amount [{$value}].");
        }

        $paise = ((int) $m[2]) * 100 + (int) str_pad($m[3] ?? '0', 2, '0');

        return $m[1] === '-' ? -$paise : $paise;
    }

    /** Integer paise to a decimal string suitable for a DECIMAL(10,2) column. */
    public static function toDecimal(int $paise): string
    {
        $sign = $paise < 0 ? '-' : '';
        $paise = abs($paise);

        return $sign.intdiv($paise, 100).'.'.str_pad((string) ($paise % 100), 2, '0', STR_PAD_LEFT);
    }

    /** Round paise up to the nearest whole multiple of $rupees. */
    public static function roundUp(int $paise, int $rupees): int
    {
        $step = max(1, $rupees) * 100;

        return intdiv($paise + $step - 1, $step) * $step;
    }

    /** Format using Indian digit grouping: ₹1,23,456 (paise shown only when non-zero). */
    public static function format(int|string|float|null $amount, bool $isPaise = false): string
    {
        $paise = $isPaise ? (int) $amount : self::toPaise($amount);
        $rupees = intdiv(abs($paise), 100);
        $fraction = abs($paise) % 100;

        $digits = (string) $rupees;
        if (strlen($digits) > 3) {
            $last3 = substr($digits, -3);
            $rest = substr($digits, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $digits = $rest.','.$last3;
        }

        return ($paise < 0 ? '-' : '').'₹'.$digits.($fraction ? '.'.str_pad((string) $fraction, 2, '0', STR_PAD_LEFT) : '');
    }
}
