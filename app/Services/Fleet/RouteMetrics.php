<?php

namespace App\Services\Fleet;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Road distance and time between pickup and drop — the numbers every fare depends on.
 *
 * The admin screen takes them from Google Directions in the browser. Here the server asks Google Directions
 * itself; if that is not possible (for example the key only allows browser use), it accepts the distance the
 * visitor's browser measured, but only when it is plausible against the straight-line distance. A fare is
 * never calculated from an unverified distance.
 */
class RouteMetrics
{
    public function __construct(private readonly FleetSettings $settings) {}

    /**
     * @return array{km: float, sec: int, source: string}|null  null when the route cannot be verified
     */
    public function resolve(float $pickupLat, float $pickupLng, float $dropLat, float $dropLng, ?float $clientKm, ?int $clientSec): ?array
    {
        $straight = $this->haversineKm($pickupLat, $pickupLng, $dropLat, $dropLng);

        if ($server = $this->fromGoogle($pickupLat, $pickupLng, $dropLat, $dropLng)) {
            return $server;
        }

        if ($clientKm !== null && $clientSec !== null && $clientKm > 0 && $this->plausible($straight, $clientKm, $clientSec)) {
            return ['km' => round($clientKm, 2), 'sec' => $clientSec, 'source' => 'browser'];
        }

        return null;
    }

    public function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** Road distance is at least the straight line and rarely more than ~3× it; speed between 5 and 130 km/h. */
    private function plausible(float $straightKm, float $km, int $sec): bool
    {
        if ($km < $straightKm * 0.95 - 0.5 || $km > $straightKm * 3 + 5) {
            return false;
        }
        $hours = max($sec, 1) / 3600;
        $speed = $km / $hours;

        return $km < 1 || ($speed >= 5 && $speed <= 130);
    }

    /** @return array{km: float, sec: int, source: string}|null */
    private function fromGoogle(float $pLat, float $pLng, float $dLat, float $dLng): ?array
    {
        $key = $this->settings->googleMapsKey();
        if ($key === '') {
            return null;
        }

        $cacheKey = 'fleet.route.'.md5(implode(',', array_map(fn ($v) => round($v, 5), [$pLat, $pLng, $dLat, $dLng])));

        return Cache::remember($cacheKey, 3600, function () use ($key, $pLat, $pLng, $dLat, $dLng) {
            try {
                $res = Http::timeout(6)->get('https://maps.googleapis.com/maps/api/directions/json', [
                    'origin' => $pLat.','.$pLng,
                    'destination' => $dLat.','.$dLng,
                    'mode' => 'driving',
                    'key' => $key,
                ]);
                $leg = $res->json('routes.0.legs.0');
                if ($res->json('status') !== 'OK' || ! $leg) {
                    Log::info('Fleet route: Google Directions unavailable', ['status' => $res->json('status'), 'error' => $res->json('error_message')]);

                    return null;
                }

                // Same rounding as the admin: (metres / 100) / 10, 2 decimals.
                return ['km' => round(($leg['distance']['value'] / 100) / 10, 2), 'sec' => (int) $leg['duration']['value'], 'source' => 'google'];
            } catch (\Throwable $e) {
                Log::warning('Fleet route: Google Directions request failed', ['error' => $e->getMessage()]);

                return null;
            }
        }) ?: null;
    }
}
