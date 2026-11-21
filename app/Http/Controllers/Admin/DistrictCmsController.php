<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DistrictPageRequest;
use App\Models\City;
use App\Models\DistrictPage;
use App\Models\PromoVideo;
use App\Models\TaxiRoute;
use App\Models\VehicleCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * District Management tabs: Videos, Photos, SEO Settings, Cars, Taxi Services, Routes and FAQs for one district.
 * Everything saved here is read by the public district page (/tamil-nadu/{slug}) — nothing is hard-coded in Blade.
 */
class DistrictCmsController extends Controller
{
    /** Tab labels shown at the top of every district screen (key => label). */
    public const TABS = [
        'details' => 'District Details', 'videos' => 'Videos', 'photos' => 'Photos', 'seo' => 'SEO Settings',
        'cars' => 'Cars', 'services' => 'Taxi Services', 'routes' => 'Routes', 'faqs' => 'FAQs',
    ];

    /** Loads the counts shown on the tab buttons. */
    public static function withTabCounts(DistrictPage $district): DistrictPage
    {
        return $district->loadCount(['videos', 'galleryPhotos', 'districtVehicles', 'districtRoutes', 'districtFaqs']);
    }

    public function show(DistrictPage $district, string $tab): View
    {
        self::withTabCounts($district);

        $data = match ($tab) {
            'videos' => ['videos' => $district->videos()->with('thumbnail')->ordered()->get()],
            'photos' => ['photos' => $district->galleryPhotos()->with(['media', 'districts:id'])->ordered()->get()],
            'cars' => [
                'cars' => $district->districtVehicles()->with(['vehicle.fareRule', 'vehicle.media', 'media'])->ordered()->get(),
                'vehicles' => VehicleCategory::ordered()->get(['id', 'name', 'passenger_capacity', 'is_active']),
            ],
            'services' => ['vehicles' => VehicleCategory::active()->with('fareRule')->ordered()->get()],
            'routes' => [
                'items' => $district->districtRoutes()->with('taxiRoute.origin', 'taxiRoute.destination')->ordered()->get(),
                'taxiRoutes' => TaxiRoute::with(['origin:id,name', 'destination:id,name'])->ordered()->get(['id', 'origin_city_id', 'destination_city_id']),
                'cities' => City::ordered()->get(['id', 'name', 'state']),
                'district' => $district->load('cities:id'),
            ],
            'faqs' => ['faqs' => $district->districtFaqs()->ordered()->get()],
            default => [],
        };

        return view('admin.districts.tabs.'.$tab, ['district' => $district, 'tab' => $tab] + $data);
    }

    public function update(Request $request, DistrictPage $district, string $tab): RedirectResponse
    {
        match ($tab) {
            'seo' => $this->saveSeo($request, $district),
            'services' => $this->saveServices($request, $district),
            'routes' => $this->saveRouteSettings($request, $district),
        };

        return redirect()->route('admin.districts.tab', [$district, $tab])->with('status', self::TABS[$tab].' saved — the public page is updated.');
    }

    private function saveSeo(Request $request, DistrictPage $district): void
    {
        $request->merge(['canonical_url' => trim((string) $request->input('canonical_url')) ?: null]);
        $data = $request->validate([
            'seo_title' => ['nullable', 'string', 'max:160'],
            'seo_description' => ['nullable', 'string', 'max:255'],
            'seo_keywords' => ['nullable', 'string', 'max:255'],
            'canonical_url' => ['nullable', 'url:http,https', 'max:255', Rule::unique('district_pages', 'canonical_url')->ignore($district->id)],
            'og_title' => ['nullable', 'string', 'max:160'],
            'og_description' => ['nullable', 'string', 'max:255'],
            'og_media_id' => ['nullable', 'integer', Rule::exists('media_assets', 'id')],
            'image_alt' => ['nullable', 'string', 'max:160'],
        ], [], ['og_media_id' => 'social sharing image', 'canonical_url' => 'canonical URL']);

        $district->update($data);
    }

    private function saveServices(Request $request, DistrictPage $district): void
    {
        $keys = array_keys(DistrictPageRequest::SERVICE_KEYS);
        $request->validate([
            'service_texts' => ['nullable', 'array'],
            'service_texts.*' => ['nullable', 'string', 'max:300'],
            'visible' => ['nullable', 'array'],
            'visible.*' => ['string', Rule::in($keys)],
        ]);

        $texts = [];
        foreach ($keys as $key) {
            $value = $request->input('service_texts.'.$key);
            if (is_string($value) && trim($value) !== '') {
                $texts[$key] = trim($value);
            }
        }
        $visible = (array) $request->input('visible', []);

        $district->update([
            'service_texts' => $texts ?: null,
            'hidden_services' => array_values(array_diff($keys, $visible)) ?: null,
        ]);
    }

    private function saveRouteSettings(Request $request, DistrictPage $district): void
    {
        $lines = fn (string $key) => array_values(array_filter(array_map('trim', preg_split('/\R/', is_string($request->input($key)) ? $request->input($key) : ''))));
        $request->merge([
            'pickup_locations' => $lines('pickup_locations'),
            'drop_locations' => $lines('drop_locations'),
            'popular_destinations' => $lines('popular_destinations'),
            'related_districts' => array_values(array_unique(array_map(fn ($v) => Str::slug($v), $lines('related_districts')))),
            'city_ids' => array_values(array_filter((array) $request->input('city_ids', []), 'is_numeric')),
        ]);
        $data = $request->validate([
            'pickup_locations' => ['array', 'max:20'], 'pickup_locations.*' => ['string', 'max:120'],
            'drop_locations' => ['array', 'max:20'], 'drop_locations.*' => ['string', 'max:120'],
            'popular_destinations' => ['array', 'max:12'], 'popular_destinations.*' => ['string', 'max:120'],
            'related_districts' => ['array', 'max:12'], 'related_districts.*' => ['string', 'max:120', Rule::exists('district_pages', 'slug')],
            'city_ids' => ['array'], 'city_ids.*' => ['integer', Rule::exists('cities', 'id')],
        ], [], ['related_districts.*' => 'nearby district', 'city_ids.*' => 'booking city']);

        DB::transaction(function () use ($district, $data) {
            $district->update(collect($data)->except('city_ids')->map(fn ($v) => $v ?: null)->all());
            $district->cities()->sync($data['city_ids']);
        });
    }

    /** Homepage section options for the video form. @return array<string, string> */
    public static function homeSections(): array
    {
        return PromoVideo::HOME_SECTIONS;
    }
}
