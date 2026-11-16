<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CityRequest;
use App\Models\City;
use App\Models\TaxiRoute;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index(Request $request): View
    {
        $term = $this->queryText($request, 'q');

        return view('admin.cities.index', [
            'cities' => City::query()
                ->withCount(['departingRoutes', 'arrivingRoutes'])
                ->with('media')
                ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('state', 'like', "%{$term}%")))
                ->ordered()->paginate(25)->withQueryString(),
            'search' => $term,
        ]);
    }

    public function create(): View
    {
        return view('admin.cities.form', ['city' => new City(['is_active' => true])]);
    }

    public function store(CityRequest $request): RedirectResponse
    {
        City::create($request->validated());

        return redirect()->route('admin.cities.index')->with('status', 'City added.');
    }

    public function edit(City $city): View
    {
        return view('admin.cities.form', ['city' => $city]);
    }

    public function update(CityRequest $request, City $city): RedirectResponse
    {
        $city->update($request->validated());

        return redirect()->route('admin.cities.index')->with('status', 'City saved.');
    }

    public function destroy(City $city): RedirectResponse
    {
        if (TaxiRoute::where('origin_city_id', $city->id)->orWhere('destination_city_id', $city->id)->exists()) {
            return back()->with('error', 'This city is used by one or more routes. Delete or change those routes first, or mark the city inactive.');
        }

        $city->delete();

        return redirect()->route('admin.cities.index')->with('status', 'City deleted.');
    }
}
