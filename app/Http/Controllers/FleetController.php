<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\VehicleCategory;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

class FleetController extends Controller
{
    public function index(): View
    {
        return view('fleet.index', [
            'vehicles' => VehicleCategory::active()->with(['media', 'fareRule'])->ordered()->get(),
            'seo' => Seo::make('Our Fleet — Sedans, SUVs & Innova Crysta', 'Choose from economy sedans, premium sedans, SUVs and Innova Crysta for your one-way or outstation trip.'),
        ]);
    }

    public function show(VehicleCategory $vehicle): View
    {
        abort_unless($vehicle->is_active, 404);

        $vehicle->load(['media', 'fareRule']);

        return view('fleet.show', [
            'vehicle' => $vehicle,
            'others' => VehicleCategory::active()->with('media')->whereKeyNot($vehicle->id)->ordered()->get(),
            'cities' => City::active()->ordered()->get(),
            'vehicles' => VehicleCategory::active()->ordered()->get(),
            'seo' => Seo::make($vehicle->seo_title ?: $vehicle->name.' Taxi', $vehicle->seo_description ?: $vehicle->short_description, $vehicle->media),
        ]);
    }
}
