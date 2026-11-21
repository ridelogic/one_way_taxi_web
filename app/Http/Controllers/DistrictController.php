<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\DistrictPage;
use App\Models\GalleryPhoto;
use App\Models\Page;
use App\Models\PromoAnnouncement;
use App\Models\PromoVideo;
use App\Models\TaxiRoute;
use App\Models\VehicleCategory;
use App\Services\FareCalculator;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Tamil Nadu district landing pages: /tamil-nadu (all districts) and /tamil-nadu/{district}.
 * Fares shown here always come from the existing route data and the FareCalculator — never typed into the page.
 */
class DistrictController extends Controller
{
    public function index(): View
    {
        $seo = Seo::make(
            'Taxi Services Across Tamil Nadu — All 38 Districts',
            'Book one-way, round-trip and local taxis in every Tamil Nadu district, from Chennai and Coimbatore to Madurai, Tiruchirappalli and Kanniyakumari.',
        );
        $seo->canonical = route('districts.index');

        return view('districts.index', [
            // The Tamil Nadu directory lists only Tamil Nadu districts; Union Territories have their own page.
            'districts' => DistrictPage::published()->tamilNadu()->ordered()->with(['heroMedia', 'cardMedia'])->get(['id', 'name', 'slug', 'state', 'region', 'headquarters', 'summary', 'intro', 'hero_media_id', 'card_media_id', 'image_alt']),
            'seo' => $seo,
        ]);
    }

    public function show(DistrictPage $district, FareCalculator $calculator): View|RedirectResponse
    {
        $preview = ! $district->is_published;
        abort_if($preview && ! auth()->user()?->isAdmin(), 404);

        // One address per page: a Union Territory is never served under /tamil-nadu, and a district never at /{slug}.
        if (url()->current() !== $district->url()) {
            return redirect()->to($district->url(), 301);
        }

        $district->load(['heroMedia', 'ogMedia', 'cities']);
        $routes = $district->routes(6);
        $vehicles = VehicleCategory::active()->ordered()->get();

        // Fare grid: one estimate per route and vehicle type, from the existing fare rules and route fares.
        $fares = $routes->mapWithKeys(fn (TaxiRoute $route) => [
            $route->id => $vehicles->mapWithKeys(fn (VehicleCategory $vehicle) => [
                $vehicle->id => $calculator->estimate($route->origin, $route->destination, $vehicle),
            ]),
        ]);

        $seo = Seo::make($district->seoTitle(), $district->seoDescription(), $district->ogMedia ?? $district->heroMedia);
        $seo->canonical = $district->canonicalUrl();
        $seo->keywords = $district->seo_keywords ?: null;
        $seo->ogTitle = $district->og_title ?: null;
        $seo->ogDescription = $district->og_description ?: null;
        $seo->noindex($preview);

        return view('districts.show', [
            'district' => $district,
            'routes' => $routes,
            'vehicles' => $vehicles,
            'fares' => $fares,
            'related' => $district->relatedPages(),
            'videos' => PromoVideo::forDistrict($district)->with('thumbnail')->get(),
            'photos' => GalleryPhoto::forDistrict($district)->with('media')->get(),
            'cars' => $district->districtVehicles()->active()->ordered()
                ->whereHas('vehicle')->with(['vehicle.fareRule', 'vehicle.media', 'media'])->get(),
            'routeFares' => $this->routeFares($district, $vehicles, $calculator),
            'schemas' => $this->schemas($district),
            'why' => Page::where('slug', 'home')->first()?->sections()->where('key', 'why_choose')->first(),
            'cities' => City::active()->ordered()->get(),
            // Offers ticker: one query for the enabled, current announcements that target this page.
            'announcements' => PromoAnnouncement::forTicker($district),
            'preview' => $preview,
            'seo' => $seo,
        ]);
    }

    /**
     * Lowest fare estimate per district route that is linked to an existing priced route (Admin → Routes), from the
     * existing FareCalculator — nothing is typed into the district.
     *
     * @return array<int, string> district route id => formatted lowest fare
     */
    private function routeFares(DistrictPage $district, $vehicles, FareCalculator $calculator): array
    {
        $fares = [];
        foreach ($district->districtRoutes()->active()->whereNotNull('taxi_route_id')->with('taxiRoute.origin', 'taxiRoute.destination')->get() as $item) {
            $route = $item->taxiRoute;
            if (! $route || ! $route->is_active || ! $route->origin || ! $route->destination) {
                continue;
            }
            $best = null;
            foreach ($vehicles as $vehicle) {
                $e = $calculator->estimate($route->origin, $route->destination, $vehicle);
                if ($e['available'] && ($best === null || $e['total'] < $best['total'])) {
                    $best = $e;
                }
            }
            if ($best) {
                $fares[$item->id] = $best['total_formatted'];
            }
        }

        return $fares;
    }

    /**
     * Structured data for the page: the breadcrumb trail, and FAQPage only for the FAQs visible on the page.
     * (The site-wide TaxiService schema is in the layout and uses only the verified business settings.)
     *
     * @return list<array<string, mixed>>
     */
    private function schemas(DistrictPage $district): array
    {
        // Districts: Home › Tamil Nadu › District. A Union Territory is not under Tamil Nadu: Home › Territory.
        $trail = [['name' => 'Home', 'item' => route('home')]];
        if ($district->isTamilNadu()) {
            $trail[] = ['name' => 'Tamil Nadu', 'item' => route('districts.index')];
        }
        $trail[] = ['name' => $district->name, 'item' => $district->url()];

        $schemas = [[
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $crumb, int $i) => ['@type' => 'ListItem', 'position' => $i + 1] + $crumb, $trail, array_keys($trail)),
        ]];

        // District service area: only the business name and phone from Admin → Settings (verified details), no claims.
        if (settings()->bool('structured_data_enabled')) {
            $schemas[] = array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'TaxiService',
                'name' => 'Taxi service in '.$district->name,
                'url' => $district->canonicalUrl(),
                'areaServed' => ['@type' => 'AdministrativeArea', 'name' => $district->isTamilNadu() ? $district->name.', '.DistrictPage::TAMIL_NADU : $district->name],
                'provider' => array_filter(['@type' => 'LocalBusiness', 'name' => settings('business_name'), 'telephone' => settings('phone')]),
            ]);
        }

        if ($faqs = $district->faqItems()) {
            $schemas[] = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => array_map(fn (array $f) => [
                    '@type' => 'Question',
                    'name' => $f['question'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']],
                ], $faqs),
            ];
        }

        return $schemas;
    }
}
