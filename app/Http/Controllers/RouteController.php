<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\TaxiRoute;
use App\Models\VehicleCategory;
use App\Services\FareCalculator;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function index(Request $request): View
    {
        $from = $request->integer('from') ?: null;
        $to = $request->integer('to') ?: null;

        $routes = TaxiRoute::active()
            ->with(['origin', 'destination', 'media', 'destination.media'])
            ->when($from, fn ($q) => $q->where('origin_city_id', $from))
            ->when($to, fn ($q) => $q->where('destination_city_id', $to))
            ->orderByDesc('is_popular')->ordered()
            ->paginate(12)
            ->withQueryString();

        return view('routes.index', [
            'routes' => $routes,
            'cities' => City::active()->ordered()->get(),
            'from' => $from,
            'to' => $to,
            'seo' => Seo::make('One Way Taxi Routes & Fares', 'Browse popular one-way taxi routes with distances, travel times and starting fares. Book intercity cabs across South India.'),
        ]);
    }

    public function show(TaxiRoute $taxiRoute, FareCalculator $calculator): View
    {
        abort_unless($taxiRoute->is_active, 404);

        $taxiRoute->load(['origin', 'destination', 'media', 'destination.media']);
        $vehicles = VehicleCategory::active()->with('media')->ordered()->get();

        $estimates = $vehicles->mapWithKeys(fn (VehicleCategory $vehicle) => [
            $vehicle->id => $calculator->estimate($taxiRoute->origin, $taxiRoute->destination, $vehicle),
        ]);

        $related = TaxiRoute::active()
            ->with(['origin', 'destination', 'media', 'destination.media'])
            ->whereKeyNot($taxiRoute->id)
            ->where(fn ($q) => $q->whereIn('origin_city_id', [$taxiRoute->origin_city_id, $taxiRoute->destination_city_id])
                ->orWhereIn('destination_city_id', [$taxiRoute->origin_city_id, $taxiRoute->destination_city_id]))
            ->ordered()->limit(4)->get();

        $seo = Seo::make(
            $taxiRoute->seo_title ?: $taxiRoute->name().' Taxi',
            $taxiRoute->seo_description ?: $taxiRoute->short_description,
            $taxiRoute->displayMedia(),
        );

        return view('routes.show', [
            'route' => $taxiRoute,
            'vehicles' => $vehicles,
            'estimates' => $estimates,
            'related' => $related,
            'cities' => City::active()->ordered()->get(),
            'seo' => $seo,
        ]);
    }
}
