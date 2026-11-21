<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Service;
use App\Models\TaxiRoute;
use App\Models\VehicleCategory;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('services.index', [
            'services' => Service::publicListing()->with('media')->ordered()->get(),
            'seo' => Seo::make('Taxi Services — Local, Rental, One Way & Round Trip', 'Local, Rental, One Way and Round Trip taxis, plus airport transfers and corporate travel, with transparent fares.'),
        ]);
    }

    public function show(Service $service): View|RedirectResponse
    {
        abort_unless($service->is_active, 404);

        // Withdrawn services (Bike Taxi, Auto Taxi) are off the public website: old links land on all services.
        if ($service->isWithdrawn()) {
            return redirect()->route('services.index', status: 301);
        }

        $service->load('media');

        return view('services.show', [
            'service' => $service,
            'others' => Service::publicListing()->whereKeyNot($service->id)->ordered()->get(),
            'popularRoutes' => TaxiRoute::active()->with(['origin', 'destination'])->where('is_popular', true)->ordered()->limit(6)->get(),
            'cities' => City::active()->ordered()->get(),
            'vehicles' => VehicleCategory::active()->ordered()->get(),
            'seo' => Seo::make($service->seo_title ?: $service->title, $service->seo_description ?: $service->short_description, $service->media),
        ]);
    }
}
