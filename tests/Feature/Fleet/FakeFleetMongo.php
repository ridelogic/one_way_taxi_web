<?php

namespace Tests\Feature\Fleet;

use App\Services\Fleet\FleetMongo;

/**
 * In-memory stand-in for the fleet MongoDB, so the fare rules can be tested without a server or the mongodb
 * extension. Supports the filters the fleet services use: equality, $ne, and $geoIntersects against a zone's
 * 'bbox' ([minLng, minLat, maxLng, maxLat]).
 */
class FakeFleetMongo extends FleetMongo
{
    /** @param  array<string, array<int, array>>  $data  collection => documents */
    public function __construct(public array $data = []) {}

    public function available(): bool
    {
        return true;
    }

    public function find(string $collection, array $filter = [], array $options = []): array
    {
        $rows = array_values(array_filter($this->data[$collection] ?? [], fn ($doc) => $this->matches($doc, $filter)));

        foreach (array_reverse($options['sort'] ?? [], true) as $field => $dir) {
            usort($rows, fn ($a, $b) => (($a[$field] ?? 0) <=> ($b[$field] ?? 0)) * ($dir < 0 ? -1 : 1));
        }

        return $rows;
    }

    public function findOne(string $collection, array $filter, array $options = []): ?array
    {
        return $this->find($collection, $filter, $options)[0] ?? null;
    }

    public function count(string $collection, array $filter): int
    {
        return count($this->find($collection, $filter));
    }

    public function aggregate(string $collection, array $pipeline): array
    {
        return [];
    }

    private function matches(array $doc, array $filter): bool
    {
        foreach ($filter as $key => $want) {
            $have = $doc[$key] ?? null;
            if (is_array($want) && isset($want['$geoIntersects'])) {
                [$lng, $lat] = $want['$geoIntersects']['$geometry']['coordinates'];
                $box = $doc['bbox'] ?? null;
                if (! $box || $lng < $box[0] || $lat < $box[1] || $lng > $box[2] || $lat > $box[3]) {
                    return false;
                }
            } elseif (is_array($want) && array_key_exists('$ne', $want)) {
                if ($have === $want['$ne']) {
                    return false;
                }
            } elseif ($have !== $want) {
                return false;
            }
        }

        return true;
    }
}
