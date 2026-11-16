<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Service;
use App\Models\TaxiRoute;
use App\Models\VehicleCategory;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('services.index', [
            'services' => Service::active()->with('media')->ordered()->get(),
            'seo' => Seo::make('Taxi Services — One Way, Outstation, Airport & Local', 'One-way drops, outstation trips, airport transfers, local taxis and corporate travel with transparent fares.'),
        ]);
    }

    public function show(Service $service): View
    {
        abort_unless($service->is_active, 404);

        $service->load('media');

        return view('services.show', [
            'service' => $service,
            'others' => Service::active()->whereKeyNot($service->id)->ordered()->get(),
            'popularRoutes' => TaxiRoute::active()->with(['origin', 'destination'])->where('is_popular', true)->ordered()->limit(6)->get(),
            'cities' => City::active()->ordered()->get(),
            'vehicles' => VehicleCategory::active()->ordered()->get(),
            'seo' => Seo::make($service->seo_title ?: $service->title, $service->seo_description ?: $service->short_description, $service->media),
        ]);
    }
}
