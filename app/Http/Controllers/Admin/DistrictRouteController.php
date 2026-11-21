<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DistrictPage;
use App\Models\DistrictRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** District Management → Routes: popular routes on the district page (fares only via a linked priced route). */
class DistrictRouteController extends Controller
{
    public function store(Request $request, DistrictPage $district): RedirectResponse
    {
        $district->districtRoutes()->create($this->validated($request));

        return redirect()->route('admin.districts.tab', [$district, 'routes'])->with('status', 'Route added.');
    }

    public function update(Request $request, DistrictRoute $districtRoute): RedirectResponse
    {
        $districtRoute->update($this->validated($request));

        return redirect()->route('admin.districts.tab', [$districtRoute->district, 'routes'])->with('status', 'Route saved.');
    }

    public function destroy(DistrictRoute $districtRoute): RedirectResponse
    {
        $district = $districtRoute->district;
        $districtRoute->delete();

        return redirect()->route('admin.districts.tab', [$district, 'routes'])->with('status', 'Route deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $request->merge([
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'taxi_route_id' => $request->filled('taxi_route_id') ? $request->input('taxi_route_id') : null,
        ]);

        return $request->validate([
            'from_location' => ['required', 'string', 'max:120'],
            'to_location' => ['required', 'string', 'max:120', 'different:from_location'],
            'description' => ['nullable', 'string', 'max:500'],
            'taxi_route_id' => ['nullable', 'integer', Rule::exists('taxi_routes', 'id')],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ], [], ['from_location' => 'pickup', 'to_location' => 'destination', 'taxi_route_id' => 'priced route']);
    }
}
