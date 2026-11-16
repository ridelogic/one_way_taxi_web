<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TaxiRouteRequest;
use App\Models\City;
use App\Models\TaxiRoute;
use App\Models\VehicleCategory;
use App\Services\FareCalculator;
use App\Support\HtmlSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxiRouteController extends Controller
{
    public function index(Request $request): View
    {
        $term = $this->queryText($request, 'q');

        return view('admin.routes.index', [
            'routes' => TaxiRoute::query()
                ->with(['origin', 'destination', 'media', 'destination.media'])
                ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q
                    ->whereHas('origin', fn ($c) => $c->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('destination', fn ($c) => $c->where('name', 'like', "%{$term}%"))))
                ->when($request->query('status') === 'active', fn ($q) => $q->where('is_active', true))
                ->when($request->query('status') === 'inactive', fn ($q) => $q->where('is_active', false))
                ->ordered()->paginate(25)->withQueryString(),
            'search' => $term,
        ]);
    }

    public function create(): View
    {
        return $this->form(new TaxiRoute(['is_active' => true, 'is_sample_fare' => false]));
    }

    public function store(TaxiRouteRequest $request): RedirectResponse
    {
        $route = DB::transaction(function () use ($request) {
            $route = TaxiRoute::create($this->payload($request));
            $this->syncFares($route, $request->validated('fares') ?? []);

            return $route;
        });

        return redirect()->route('admin.routes.edit', $route)->with('status', 'Route created.');
    }

    public function edit(TaxiRoute $taxiRoute): View
    {
        return $this->form($taxiRoute->load('fares'));
    }

    public function update(TaxiRouteRequest $request, TaxiRoute $taxiRoute): RedirectResponse
    {
        DB::transaction(function () use ($request, $taxiRoute) {
            $taxiRoute->update($this->payload($request));
            $this->syncFares($taxiRoute, $request->validated('fares') ?? []);
        });

        return redirect()->route('admin.routes.edit', $taxiRoute)->with('status', 'Route saved. The new fare is live on the website.');
    }

    public function destroy(TaxiRoute $taxiRoute): RedirectResponse
    {
        $taxiRoute->delete();

        return redirect()->route('admin.routes.index')->with('status', 'Route deleted. Existing bookings keep their saved details.');
    }

    private function form(TaxiRoute $route): View
    {
        $vehicles = VehicleCategory::ordered()->get();
        $calculator = app(FareCalculator::class);

        // Rule-based estimates shown next to the override inputs as a guide.
        $guides = $route->exists
            ? $vehicles->mapWithKeys(fn ($v) => [$v->id => $calculator->estimate($route->origin, $route->destination, $v)])
            : collect();

        return view('admin.routes.form', [
            'route' => $route,
            'cities' => City::ordered()->get()->mapWithKeys(fn ($c) => [$c->id => $c->label()]),
            'vehicles' => $vehicles,
            'fares' => $route->exists ? $route->fares->pluck('fare', 'vehicle_category_id') : collect(),
            'guides' => $guides,
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(TaxiRouteRequest $request): array
    {
        $data = collect($request->validated())->except('fares')->all();
        $data['description'] = HtmlSanitizer::clean($data['description'] ?? '');

        return $data;
    }

    /** Route-specific fares per vehicle; an empty input removes the override. */
    private function syncFares(TaxiRoute $route, array $fares): void
    {
        foreach (VehicleCategory::pluck('id') as $vehicleId) {
            $fare = $fares[$vehicleId] ?? null;

            if ($fare === null || $fare === '') {
                $route->fares()->where('vehicle_category_id', $vehicleId)->delete();
            } else {
                $route->fares()->updateOrCreate(['vehicle_category_id' => $vehicleId], ['fare' => $fare]);
            }
        }
    }
}
