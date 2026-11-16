<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Human-friendly unique references such as OWT-261011-7K3QX, COR-261011-AB2CD, PTR-261011-XY9ZQ.
 * No look-alike characters (0/O, 1/I/L) so they are easy to read out on the phone.
 */
final class Reference
{
    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    /** @param class-string<Model> $model */
    public static function generate(string $prefix, string $model, string $column = 'reference'): string
    {
        do {
            $suffix = '';
            for ($i = 0; $i < 5; $i++) {
                $suffix .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            $reference = $prefix.'-'.now()->format('ymd').'-'.$suffix;
        } while ($model::where($column, $reference)->exists());

        return $reference;
    }
}
