<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FareRuleRequest;
use App\Models\FareRule;
use App\Models\VehicleCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class FareRuleController extends Controller
{
    public function index(): View
    {
        return view('admin.fares.index', [
            'vehicles' => VehicleCategory::with('fareRule')->ordered()->get(),
        ]);
    }

    public function edit(VehicleCategory $vehicle): View
    {
        return view('admin.fares.edit', [
            'vehicle' => $vehicle,
            'rule' => $vehicle->fareRule ?? new FareRule([
                'base_fare' => '0.00', 'per_km_rate' => null, 'minimum_km' => 0, 'minimum_fare' => '0.00',
                'driver_allowance' => '0.00', 'additional_charges' => [], 'is_active' => true,
            ]),
        ]);
    }

    public function update(FareRuleRequest $request, VehicleCategory $vehicle): RedirectResponse
    {
        FareRule::updateOrCreate(['vehicle_category_id' => $vehicle->id], $request->validated());

        return redirect()->route('admin.fares.edit', $vehicle)->with('status', "Fare rule for {$vehicle->name} saved. Estimates now use the new rates.");
    }
}
