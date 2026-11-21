<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DistrictPage;
use App\Models\DistrictVehicle;
use App\Models\VehicleCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * District Management → Cars. Each car points at a Vehicle Master record (which keeps the fares used by booking);
 * a new master vehicle can be created from here and then becomes selectable in every district — but it is only
 * added to the district it was created from.
 */
class DistrictVehicleController extends Controller
{
    public function store(Request $request, DistrictPage $district): RedirectResponse
    {
        $data = $this->validated($request, true);

        DB::transaction(function () use ($district, $data) {
            if (($data['vehicle_mode'] ?? 'existing') === 'new') {
                $data['vehicle_category_id'] = $this->createMasterVehicle($data)->id;
            }
            $district->districtVehicles()->create($this->carFields($data));
        });

        return redirect()->route('admin.districts.tab', [$district, 'cars'])->with('status', 'Car added to '.$district->name.'.');
    }

    public function edit(DistrictVehicle $car): View
    {
        return view('admin.districts.car-edit', [
            'car' => $car->load('vehicle', 'district'),
            'district' => DistrictCmsController::withTabCounts($car->district),
            'vehicles' => VehicleCategory::ordered()->get(['id', 'name', 'passenger_capacity', 'is_active']),
            'tab' => 'cars',
        ]);
    }

    public function update(Request $request, DistrictVehicle $car): RedirectResponse
    {
        $car->update($this->carFields($this->validated($request, false)));

        return redirect()->route('admin.districts.tab', [$car->district, 'cars'])->with('status', 'Car saved.');
    }

    public function toggle(DistrictVehicle $car): RedirectResponse
    {
        $car->update(['is_active' => ! $car->is_active]);

        return back()->with('status', $car->displayName().($car->is_active ? ' is now shown.' : ' is now hidden.'));
    }

    public function destroy(DistrictVehicle $car): RedirectResponse
    {
        $district = $car->district;
        $car->delete(); // removes it from this district only; the Vehicle Master record stays

        return redirect()->route('admin.districts.tab', [$district, 'cars'])->with('status', 'Car removed from this district.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, bool $creating): array
    {
        $request->merge([
            'is_ac' => $request->boolean('is_ac'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'seats' => $request->filled('seats') ? $request->input('seats') : null,
        ]);
        $new = $creating && $request->input('vehicle_mode') === 'new';

        return $request->validate([
            'vehicle_mode' => ['nullable', Rule::in(['existing', 'new'])],
            'vehicle_category_id' => [$new ? 'nullable' : 'required', 'integer', Rule::exists('vehicle_categories', 'id')],
            'new_vehicle_name' => [$new ? 'required' : 'nullable', 'string', 'max:100'],
            'new_vehicle_category' => ['nullable', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:100'],
            'seats' => ['nullable', 'integer', 'min:1', 'max:60'],
            'is_ac' => ['boolean'],
            'media_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ], [], ['vehicle_category_id' => 'vehicle', 'new_vehicle_name' => 'new vehicle name', 'media_id' => 'car image']);
    }

    /** @param array<string, mixed> $data */
    private function carFields(array $data): array
    {
        return collect($data)->only(['vehicle_category_id', 'name', 'seats', 'is_ac', 'media_id', 'description', 'sort_order', 'is_active'])->all();
    }

    /** New Vehicle Master record (fares can then be set in Vehicle Master → Fares). @param array<string, mixed> $data */
    private function createMasterVehicle(array $data): VehicleCategory
    {
        $base = Str::slug($data['new_vehicle_name']) ?: 'vehicle';
        $slug = $base;
        for ($i = 2; VehicleCategory::where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return VehicleCategory::create([
            'name' => $data['new_vehicle_name'],
            'slug' => $slug,
            'category' => $data['new_vehicle_category'] ?: null,
            'passenger_capacity' => $data['seats'] ?? 4,
            'luggage_capacity' => 2,
            'media_id' => $data['media_id'] ?? null,
            'sort_order' => (int) VehicleCategory::max('sort_order') + 1,
            'is_active' => true,
        ]);
    }
}
