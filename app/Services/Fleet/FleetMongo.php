<?php

namespace App\Services\Fleet;

use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use RuntimeException;

/**
 * Connection to the fleet backend's MongoDB (same database the OnewayTaxi admin, driver API and passenger API use).
 * Documents are returned as plain PHP arrays, like the old MangoDB wrapper did.
 */
class FleetMongo
{
    private ?Database $db = null;

    /** Whether fleet integration is switched on and the PHP mongodb extension is loaded. */
    public function available(): bool
    {
        return (bool) config('fleet.enabled')
            && extension_loaded('mongodb')
            && config('fleet.mongo.database') !== '';
    }

    public function db(): Database
    {
        if ($this->db) {
            return $this->db;
        }

        if (! extension_loaded('mongodb')) {
            throw new RuntimeException('The PHP mongodb extension is not loaded. Enable extension=mongodb in php.ini.');
        }

        $c = config('fleet.mongo');
        $auth = $c['username'] !== '' ? rawurlencode($c['username']).':'.rawurlencode($c['password']).'@' : '';
        $uri = 'mongodb://'.$auth.$c['host'].'/';

        $client = new Client($uri, array_filter([
            'authSource' => $c['username'] !== '' ? $c['auth_source'] : null,
            'authMechanism' => $c['username'] !== '' ? $c['auth_mechanism'] : null,
            'connectTimeoutMS' => $c['timeout_ms'],
            'serverSelectionTimeoutMS' => $c['timeout_ms'],
            'socketTimeoutMS' => $c['timeout_ms'] * 2,
        ]), [
            'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
        ]);

        return $this->db = $client->selectDatabase($c['database']);
    }

    public function collection(string $name): Collection
    {
        return $this->db()->selectCollection($name);
    }

    /** @return array<string, mixed>|null */
    public function findOne(string $collection, array $filter, array $options = []): ?array
    {
        $doc = $this->collection($collection)->findOne($filter, $options);

        return $doc === null ? null : (array) $doc;
    }

    /** @return array<int, array<string, mixed>> */
    public function find(string $collection, array $filter = [], array $options = []): array
    {
        return array_map(fn ($d) => (array) $d, $this->collection($collection)->find($filter, $options)->toArray());
    }

    public function count(string $collection, array $filter): int
    {
        return $this->collection($collection)->countDocuments($filter);
    }

    /** @return array<int, array<string, mixed>> */
    public function aggregate(string $collection, array $pipeline): array
    {
        return array_map(fn ($d) => (array) $d, $this->collection($collection)->aggregate($pipeline)->toArray());
    }

    /** Next integer _id for collections that use auto-increment style ids (passengers, passengers_logs). */
    public function nextId(string $collection): int
    {
        $last = $this->findOne($collection, [], ['projection' => ['_id' => 1], 'sort' => ['_id' => -1]]);

        return (int) ($last['_id'] ?? 0) + 1;
    }
}
