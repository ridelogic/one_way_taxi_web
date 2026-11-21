<?php

namespace App\Services\Fleet;

use App\Enums\TripType;
use App\Models\VehicleCategory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Prices a website booking form with the fleet backend's rules.
 *
 * Website trip type → fleet trip type:
 *   Local → 1, Rental → 2 (package), One Way → 3 / one way, Round Trip → 3 / round trip (days from the return date).
 *
 * Returns null when the fleet fare does not apply (integration off, vehicle not linked to a fleet model, or no map
 * coordinates) so the caller can fall back to the website's own fare data.
 */
class WebsiteFleetQuote
{
    public function __construct(
        private readonly FleetFareService $fares,
        private readonly RouteMetrics $routes,
    ) {}

    public function applies(?VehicleCategory $vehicle, array $input): bool
    {
        return $this->fares->available()
            && $vehicle?->fleet_model_id
            && is_numeric($input['pickup_lat'] ?? null) && is_numeric($input['pickup_lng'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $input  pickup_lat, pickup_lng, drop_lat, drop_lng, route_km, route_sec, travel_date, travel_time, return_date, fleet_package_id
     */
    public function quote(TripType $type, ?VehicleCategory $vehicle, array $input): ?array
    {
        if (! $this->applies($vehicle, $input)) {
            return null;
        }

        try {
            $fleetType = match ($type) {
                TripType::Local => FleetFareService::LOCAL,
                TripType::Rental => FleetFareService::RENTAL,
                default => FleetFareService::OUTSTATION,
            };

            if (! in_array((int) $vehicle->fleet_model_id, array_column($this->fares->models($fleetType), 'model_id'), true)) {
                return $this->notOffered($type, $vehicle);
            }

            $pLat = (float) $input['pickup_lat'];
            $pLng = (float) $input['pickup_lng'];
            $hasDrop = is_numeric($input['drop_lat'] ?? null) && is_numeric($input['drop_lng'] ?? null);
            $dLat = $hasDrop ? (float) $input['drop_lat'] : null;
            $dLng = $hasDrop ? (float) $input['drop_lng'] : null;

            if ($type !== TripType::Rental && ! $hasDrop) {
                return null; // drop typed without picking a map suggestion
            }

            $km = 0.0;
            $sec = 0;
            if ($hasDrop) {
                $route = $this->routes->resolve($pLat, $pLng, $dLat, $dLng,
                    is_numeric($input['route_km'] ?? null) ? (float) $input['route_km'] : null,
                    is_numeric($input['route_sec'] ?? null) ? (int) $input['route_sec'] : null);
                if (! $route) {
                    return $this->message($type, $vehicle, 'We could not measure the road distance for this route. Submit your request and our team will call you with the fare.');
                }
                [$km, $sec] = [$route['km'], $route['sec']];
            }

            $tz = config('fleet.timezone');
            $date = $input['travel_date'] ?? null;
            $pickupAt = $date ? CarbonImmutable::parse($date.' '.($input['travel_time'] ?? '09:00'), $tz) : CarbonImmutable::now($tz);
            $days = 1;
            if ($type === TripType::Outstation && $date && ! empty($input['return_date'])) {
                $days = max(1, CarbonImmutable::parse($date, $tz)->diffInDays(CarbonImmutable::parse($input['return_date'], $tz)) + 1);
            }

            $estimate = $this->fares->estimate(new FleetTrip(
                tripType: $fleetType,
                modelId: (int) $vehicle->fleet_model_id,
                pickupLat: $pLat,
                pickupLng: $pLng,
                dropLat: $dLat,
                dropLng: $dLng,
                distanceKm: $km,
                durationSec: $sec,
                pickupAt: $pickupAt,
                osTripType: $type === TripType::Outstation ? 2 : 1,
                days: (int) $days,
                packageId: isset($input['fleet_package_id']) && $input['fleet_package_id'] !== '' ? (int) $input['fleet_package_id'] : null,
            ));

            $estimate['vehicle'] = $this->fares->modelName((int) $vehicle->fleet_model_id);
            $estimate['route_name'] = $this->routeName($type, $input);
            if ($type === TripType::Outstation && $estimate['available']) {
                $estimate['message'] = $days.' day round trip — fare covers the return journey.';
            }

            return $estimate;
        } catch (\Throwable $e) {
            Log::error('Fleet fare estimate failed', ['error' => $e->getMessage(), 'trip_type' => $type->value]);

            return $this->message($type, $vehicle, 'Live fares are temporarily unavailable. Submit your request and our team will call you with the fare.');
        }
    }

    /** Rental packages for the vehicle and pickup point, for the booking form. */
    public function rentalPackages(VehicleCategory $vehicle, ?float $lat, ?float $lng): array
    {
        if (! $this->fares->available() || ! $vehicle->fleet_model_id) {
            return ['available' => false, 'packages' => []];
        }

        try {
            if (! in_array((int) $vehicle->fleet_model_id, array_column($this->fares->models(FleetFareService::RENTAL), 'model_id'), true)) {
                return ['available' => true, 'packages' => [], 'message' => "Rental is not available for {$vehicle->name}."];
            }
            $list = $this->fares->packages(FleetFareService::RENTAL, (int) $vehicle->fleet_model_id, $lat, $lng);

            return [
                'available' => true,
                'source' => $list['source'],
                'packages' => array_map(fn ($p) => ['id' => $p['id'], 'label' => $p['label'], 'base_fare' => $p['base_fare']], $list['packages']),
                'message' => $list['packages'] ? null : ($list['source'] === 'zone' ? 'Rental service is not available in this zone right now.' : 'No rental packages for this vehicle yet.'),
            ];
        } catch (\Throwable $e) {
            Log::error('Fleet rental packages failed', ['error' => $e->getMessage()]);

            return ['available' => false, 'packages' => []];
        }
    }

    /**
     * The vehicle list for the booking form. With the fleet connection on, it is the admin's Manage Models list
     * (active motor_model rows, in admin order, with the admin's name and seat count) — each shown through the
     * website vehicle linked to it. Fleet models without a linked website vehicle are logged and left out.
     * Without the fleet connection, the website's own list is returned unchanged.
     */
    public function bookableVehicles(Collection $vehicles): Collection
    {
        if (! $this->fares->available()) {
            return $vehicles;
        }

        try {
            $byModel = $vehicles->filter(fn ($v) => $v->fleet_model_id)->keyBy('fleet_model_id');
            $list = collect();
            foreach ($this->fares->models(0) as $m) {
                $vehicle = $byModel->get($m['model_id']);
                if (! $vehicle) {
                    Log::warning('Fleet model has no linked website vehicle (Admin → Vehicles → Fleet model)', $m);

                    continue;
                }
                // Display only — never saved.
                $vehicle->name = $m['model_name'];
                if ($m['model_size'] > 0) {
                    $vehicle->passenger_capacity = $m['model_size'];
                }
                $list->push($vehicle);
            }

            return $list->isEmpty() ? $vehicles : $list;
        } catch (\Throwable $e) {
            Log::error('Fleet vehicle list failed', ['error' => $e->getMessage()]);

            return $vehicles;
        }
    }

    /**
     * Trip types each linked vehicle is offered for, e.g. [3 => ['local', 'one_way', 'outstation']].
     *
     * @return array<int, array<int, string>>
     */
    public function vehicleTripTypes(iterable $vehicles): array
    {
        if (! $this->fares->available()) {
            return [];
        }

        try {
            $enabled = [];
            foreach ([FleetFareService::LOCAL => ['local'], FleetFareService::RENTAL => ['rental'], FleetFareService::OUTSTATION => ['one_way', 'outstation']] as $fleetType => $types) {
                foreach ($this->fares->models($fleetType) as $m) {
                    $enabled[$m['model_id']] = array_merge($enabled[$m['model_id']] ?? [], $types);
                }
            }

            $map = [];
            foreach ($vehicles as $v) {
                if ($v->fleet_model_id) {
                    $map[$v->id] = $enabled[$v->fleet_model_id] ?? [];
                }
            }

            return $map;
        } catch (\Throwable $e) {
            Log::error('Fleet vehicle list failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function googleMapsKey(): string
    {
        try {
            return $this->fares->available() ? app(FleetSettings::class)->googleMapsKey() : (string) config('fleet.google_maps_key');
        } catch (\Throwable) {
            return (string) config('fleet.google_maps_key');
        }
    }

    private function notOffered(TripType $type, VehicleCategory $vehicle): array
    {
        return $this->message($type, $vehicle, "{$type->label()} is not available for {$vehicle->name}. Please choose another vehicle.") + ['no_service_available' => true];
    }

    private function message(TripType $type, VehicleCategory $vehicle, string $message): array
    {
        return [
            'available' => false, 'starting_from' => false, 'message' => $message, 'route_id' => null, 'route_name' => null,
            'distance_km' => null, 'duration' => null, 'vehicle' => $vehicle->name, 'basis' => null,
            'lines' => [], 'excluded' => [], 'total' => null, 'total_formatted' => null, 'is_sample' => false,
        ];
    }

    private function routeName(TripType $type, array $input): string
    {
        $short = fn ($v) => trim(explode(',', (string) $v)[0]);

        return $type === TripType::Rental
            ? $short($input['pickup_location'] ?? '')
            : $short($input['pickup_location'] ?? '').' to '.$short($input['destination_location'] ?? '');
    }
}
