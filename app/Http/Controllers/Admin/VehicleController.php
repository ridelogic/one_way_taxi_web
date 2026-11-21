<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VehicleRequest;
use App\Models\VehicleCategory;
use App\Services\Fleet\FleetFareService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class VehicleController extends Controller
{
    public function index(): View
    {
        return view('admin.vehicles.index', [
            'vehicles' => VehicleCategory::with(['media', 'fareRule'])->withCount('bookings')->ordered()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.vehicles.form', ['vehicle' => new VehicleCategory(['is_active' => true, 'passenger_capacity' => 4, 'luggage_capacity' => 2]), 'fleetModels' => $this->fleetModels()]);
    }

    public function store(VehicleRequest $request): RedirectResponse
    {
        $vehicle = VehicleCategory::create($request->validated());

        return redirect()->route('admin.vehicles.edit', $vehicle)->with('status', 'Vehicle added. Set its fare rule in Fare rules so estimates can be calculated.');
    }

    public function edit(VehicleCategory $vehicle): View
    {
        return view('admin.vehicles.form', ['vehicle' => $vehicle, 'fleetModels' => $this->fleetModels()]);
    }

    public function update(VehicleRequest $request, VehicleCategory $vehicle): RedirectResponse
    {
        $vehicle->update($request->validated());

        return redirect()->route('admin.vehicles.edit', $vehicle)->with('status', 'Vehicle saved.');
    }

    public function destroy(VehicleCategory $vehicle): RedirectResponse
    {
        if ($vehicle->bookings()->exists()) {
            return back()->with('error', 'This vehicle has bookings. Mark it inactive instead of deleting it so booking history stays intact.');
        }

        $vehicle->delete();

        return redirect()->route('admin.vehicles.index')->with('status', 'Vehicle deleted.');
    }

    /**
     * Fleet backend vehicle models (MongoDB motor_model) for the "Fleet model" link, or null when the fleet
     * integration is off or unreachable.
     *
     * @return array<int, string>|null
     */
    private function fleetModels(): ?array
    {
        $fares = app(FleetFareService::class);
        if (! $fares->available()) {
            return null;
        }

        try {
            return collect($fares->models(0))->mapWithKeys(fn ($m) => [$m['model_id'] => $m['model_name'].' (#'.$m['model_id'].')'])->all();
        } catch (\Throwable) {
            return null;
        }
    }
}
