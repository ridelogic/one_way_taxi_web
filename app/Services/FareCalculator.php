<?php

namespace App\Services;

use App\Enums\TripType;
use App\Models\City;
use App\Models\FareRule;
use App\Models\TaxiRoute;
use App\Models\VehicleCategory;
use App\Support\Money;
use App\Support\Settings;

/**
 * Produces indicative fare estimates from configured route and fare data.
 *
 * Rules:
 *  - A route-specific fare (route_fares) for the chosen vehicle replaces the distance-based trip fare
 *    and driver allowance.
 *  - Otherwise: base fare + (max(distance, minimum km) × per-km rate), raised to the minimum fare,
 *    plus driver allowance.
 *  - Additional charges with an amount are added; charges without an amount are listed as "extra, as applicable".
 *  - The total is rounded up to the configured step. All maths is in integer paise.
 */
class FareCalculator
{
    public function __construct(private readonly Settings $settings) {}

    public function findRoute(?int $pickupCityId, ?int $destinationCityId): ?TaxiRoute
    {
        if (! $pickupCityId || ! $destinationCityId || $pickupCityId === $destinationCityId) {
            return null;
        }

        return TaxiRoute::active()
            ->with(['origin', 'destination'])
            ->where('origin_city_id', $pickupCityId)
            ->where('destination_city_id', $destinationCityId)
            ->first();
    }

    /**
     * @return array{available: bool, message: ?string, route_id: ?int, route_name: ?string, distance_km: ?string,
     *               duration: ?string, vehicle: ?string, basis: ?string, lines: array, excluded: array,
     *               total: ?string, total_formatted: ?string, is_sample: bool}
     */
    public function estimate(?City $pickup, ?City $destination, VehicleCategory $vehicle, ?string $pickupText = null, ?string $destinationText = null): array
    {
        $from = $pickup?->name ?? trim((string) $pickupText);
        $to = $destination?->name ?? trim((string) $destinationText);

        $result = [
            'available' => false,
            'message' => null,
            'route_id' => null,
            'route_name' => $from !== '' && $to !== '' ? $from.' to '.$to : null,
            'distance_km' => null,
            'duration' => null,
            'vehicle' => $vehicle->name,
            'basis' => null,
            'lines' => [],
            'excluded' => [],
            'total' => null,
            'total_formatted' => null,
            'is_sample' => false,
        ];

        $route = $this->findRoute($pickup?->id, $destination?->id);

        if (! $route) {
            $result['message'] = 'We don’t have a published fare for this route yet. Submit your request and our team will call you with a quote.';

            return $result;
        }

        $result['route_id'] = $route->id;
        $result['route_name'] = $route->name();
        $result['distance_km'] = (string) $route->distance_km;
        $result['duration'] = $route->durationLabel();
        $result['is_sample'] = $route->is_sample_fare;

        $rule = $vehicle->fareRule()->where('is_active', true)->first();
        $routeFare = $route->fares()->where('vehicle_category_id', $vehicle->id)->value('fare');

        if (! $rule && $routeFare === null) {
            $result['message'] = 'Fares for this vehicle type are not configured yet. Submit your request and we will call you with a quote.';

            return $result;
        }

        $lines = [];

        if ($routeFare !== null) {
            $result['basis'] = 'route_fare';
            $lines[] = ['label' => 'Route fare — '.$vehicle->name, 'amount' => Money::toPaise($routeFare)];
        } else {
            $result['basis'] = 'per_km';
            $lines = $this->distanceLines($rule, $route);
        }

        foreach ($rule?->charges() ?? [] as $charge) {
            if (isset($charge['amount']) && $charge['amount'] !== '' && $charge['amount'] !== null) {
                $lines[] = ['label' => $charge['label'], 'amount' => Money::toPaise($charge['amount'])];
            } else {
                $result['excluded'][] = trim($charge['label'].(! empty($charge['note']) ? ' — '.$charge['note'] : ''));
            }
        }

        $subtotal = array_sum(array_column($lines, 'amount'));
        $total = Money::roundUp($subtotal, (int) $this->settings->get('fare_round_to', 10));

        if ($total !== $subtotal) {
            $lines[] = ['label' => 'Rounding', 'amount' => $total - $subtotal];
        }

        $result['available'] = true;
        $result['lines'] = array_map(fn ($line) => [
            'label' => $line['label'],
            'amount' => Money::toDecimal($line['amount']),
            'formatted' => Money::format($line['amount'], true),
        ], $lines);
        $result['total'] = Money::toDecimal($total);
        $result['total_formatted'] = Money::format($total, true);

        return $result;
    }

    /**
     * Quick quote for the homepage widget, which has no vehicle selector: shows the route's
     * published starting fare. Outstation round trips and rentals are always quoted by the team.
     */
    public function quickQuote(?City $pickup, ?City $destination, TripType $tripType, ?string $pickupText = null, ?string $destinationText = null): array
    {
        $result = [
            'available' => false,
            'starting_from' => true,
            'message' => null,
            'route_id' => null,
            'route_name' => trim(($pickup?->name ?? (string) $pickupText).($tripType === TripType::Rental ? '' : ' to '.($destination?->name ?? (string) $destinationText))),
            'distance_km' => null,
            'duration' => null,
            'total' => null,
            'total_formatted' => null,
            'is_sample' => false,
            'excluded' => [],
            'lines' => [],
        ];

        if ($tripType === TripType::Rental) {
            $result['message'] = 'Rental packages are priced by city and vehicle. Submit your request and our team will call you with a quote.';

            return $result;
        }

        $route = $this->findRoute($pickup?->id, $destination?->id);
        if (! $route) {
            $result['message'] = 'We don’t have a published fare for this route yet. Submit your request and our team will call you with a quote.';

            return $result;
        }

        $result = array_merge($result, [
            'available' => true,
            'route_id' => $route->id,
            'route_name' => $route->name(),
            'distance_km' => (string) $route->distance_km,
            'duration' => $route->durationLabel(),
            'total' => (string) $route->base_fare,
            'total_formatted' => Money::format($route->base_fare),
            'is_sample' => $route->is_sample_fare,
            'excluded' => ['Tolls, permits and parking as applicable'],
        ]);

        if ($tripType === TripType::Outstation) {
            $result['message'] = 'Shown: one-way starting fare for this route. Outstation round trips are quoted by our team based on days and distance.';
        }

        return $result;
    }

    /** @return array<int, array{label: string, amount: int}> */
    private function distanceLines(FareRule $rule, TaxiRoute $route): array
    {
        $lines = [];
        $distanceTenths = (int) round(((float) $route->distance_km) * 10);
        $chargeableTenths = max($distanceTenths, $rule->minimum_km * 10);
        $ratePaise = Money::toPaise($rule->per_km_rate);

        $base = Money::toPaise($rule->base_fare);
        if ($base > 0) {
            $lines[] = ['label' => 'Base fare', 'amount' => $base];
        }

        $kmLabel = rtrim(rtrim(number_format($chargeableTenths / 10, 1, '.', ''), '0'), '.');
        $distanceCharge = intdiv($ratePaise * $chargeableTenths + 5, 10);
        $lines[] = [
            'label' => $kmLabel.' km × '.Money::format($ratePaise, true).'/km'.($chargeableTenths > $distanceTenths ? ' (minimum distance)' : ''),
            'amount' => $distanceCharge,
        ];

        $tripFare = $base + $distanceCharge;
        $minimum = Money::toPaise($rule->minimum_fare);
        if ($minimum > $tripFare) {
            $lines[] = ['label' => 'Minimum fare adjustment', 'amount' => $minimum - $tripFare];
        }

        $allowance = Money::toPaise($rule->driver_allowance);
        if ($allowance > 0) {
            $lines[] = ['label' => 'Driver allowance', 'amount' => $allowance];
        }

        return $lines;
    }
}
