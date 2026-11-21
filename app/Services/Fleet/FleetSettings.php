<?php

namespace App\Services\Fleet;

use Illuminate\Support\Facades\Cache;

/**
 * Site-wide settings from the fleet `siteinfo` collection — the values the old admin turns into constants
 * in application/classes/common_config.php (TAX, FARE_CALCULATION_TYPE, ZONEFARE_AVAILABLE_STATUS, …).
 */
class FleetSettings
{
    private ?array $info = null;

    public function __construct(private readonly FleetMongo $mongo) {}

    /** @return array<string, mixed> */
    public function all(): array
    {
        if ($this->info !== null) {
            return $this->info;
        }

        return $this->info = Cache::remember('fleet.siteinfo', config('fleet.cache_seconds', 300), function () {
            $doc = $this->mongo->findOne('siteinfo', [], ['sort' => ['_id' => 1]]) ?? [];

            // Keep only scalar settings (the cache must stay small and serializable).
            return array_filter($doc, fn ($v) => is_scalar($v) || $v === null);
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    /** TAX — percentage added on top of the fare. */
    public function tax(): float
    {
        return (float) $this->get('tax', 0);
    }

    /** FARE_CALCULATION_TYPE — 1 distance, 2 time, 3 distance + time. */
    public function fareCalculationType(): int
    {
        return (int) $this->get('fare_calculation_type', 1);
    }

    /** ZONEFARE_AVAILABLE_STATUS — Operating Zone / Zone to Zone fares win over the common model fares. */
    public function zoneFareEnabled(): bool
    {
        return (int) $this->get('zonefare_available_status', 0) === 1;
    }

    /** LOCAL_ZONE_MATCH_STATUS — a Local trip's drop must be in the same zone as the pickup. */
    public function localZoneMatch(): bool
    {
        return (int) $this->get('local_zone_match_status', 0) === 1;
    }

    /** MIN_OUTSTATION_KMS — Outstation needs at least this distance; Local must stay at or below it. */
    public function minOutstationKm(): float
    {
        return (float) $this->get('min_outstation_kms', 0);
    }

    /** DEFAULT_ONEWAY_DURATION (hours) — one-way trips longer than this must be booked as round trips. */
    public function defaultOnewayDurationMinutes(): float
    {
        return (float) $this->get('default_oneway_trip_duration', 24) * 60;
    }

    /** OUTSTATION_ROUND_DOUBLE_FARE — round trip base fare and plan distance are multiplied by the day count. */
    public function outstationRoundDoubleFare(): bool
    {
        return (int) $this->get('outstation_round_double_base', 1) === 1;
    }

    /** UNIT_NAME — 'km' or 'miles'. */
    public function unitName(): string
    {
        return (int) $this->get('default_unit', 0) === 1 ? 'miles' : 'km';
    }

    public function adminCommission(): float
    {
        return (float) $this->get('admin_commission', 0);
    }

    public function googleMapsKey(): string
    {
        return (string) (config('fleet.google_maps_key') ?: $this->get('web_google_map_key', ''));
    }
}
