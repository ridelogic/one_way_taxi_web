<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Moves a record one position up or down within an ordered list and renumbers sort_order 1..n. */
final class Ordering
{
    public static function move(Model $item, Builder $ordered, int $direction): void
    {
        DB::transaction(function () use ($item, $ordered, $direction) {
            $ids = $ordered->pluck($item->getKeyName())->all();
            $index = array_search($item->getKey(), $ids, true);
            $target = $index + $direction;

            if ($index === false || $target < 0 || $target >= count($ids)) {
                return;
            }

            [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

            foreach ($ids as $position => $id) {
                $item->newQuery()->whereKey($id)->update(['sort_order' => $position + 1]);
            }
        });
    }
}
