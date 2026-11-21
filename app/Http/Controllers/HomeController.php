<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\DistrictPage;
use App\Models\Faq;
use App\Models\GalleryPhoto;
use App\Models\HeroSlide;
use App\Models\Page;
use App\Models\PromoAnnouncement;
use App\Models\PromoVideo;
use App\Models\Service;
use App\Models\TaxiRoute;
use App\Models\Testimonial;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $page = Page::where('slug', 'home')->first();

        $sections = $page
            ? $page->sections()->where('is_active', true)->with('media')->get()->keyBy('key')
            : collect();

        $limit = fn (string $key, int $default) => (int) ($sections->get($key)?->get('limit') ?: $default);
        $slides = HeroSlide::active()->with(['media', 'foreground'])->ordered()->get(); // media = scene, foreground = vehicle
        $cities = City::active()->ordered()->get();

        return view('home', [
            'page' => $page,
            'sections' => $sections,
            'slides' => $slides,
            'cities' => $cities,
            // Offers ticker: only enabled, currently valid announcements (Admin → Promotional Announcements).
            'announcements' => PromoAnnouncement::forTicker(),
            'popularRoutes' => TaxiRoute::active()
                ->with(['origin', 'destination', 'media', 'destination.media'])
                ->orderByDesc('is_popular')->ordered()
                ->limit($limit('routes', 5))->get(),
            // The booking form always renders in the hero, so load its CMS content even if the section is hidden.
            'bookingSection' => $page?->sections()->where('key', 'booking')->first(),
            'services' => Service::publicListing()->where('show_on_home', true)->with('media')->ordered()->get(),
            'destinations' => $cities->where('is_popular_destination', true)->take($limit('destinations', 8))->load('media'),
            'citiesByState' => $cities->groupBy(fn (City $c) => $c->state ?: 'Other'),
            'districts' => DistrictPage::published()->ordered()->with(['heroMedia', 'cardMedia'])->get(['id', 'name', 'slug', 'state', 'summary', 'intro', 'hero_media_id', 'card_media_id', 'image_alt']),
            // Homepage Content (admin): each section loads only active items marked for the homepage.
            'homeVideos' => $sections->has('videos') ? PromoVideo::forHome('featured')->with('thumbnail')->get() : new Collection,
            'promoVideos' => $sections->has('promo_videos') ? PromoVideo::forHome('promotional')->with('thumbnail')->get() : new Collection,
            'galleryPhotos' => $sections->has('gallery') ? GalleryPhoto::forHome()->with('media')->get() : new Collection,
            'faqs' => Faq::active()->where('show_on_home', true)->ordered()->limit($limit('faqs', 6))->get(),
            'testimonials' => $this->testimonials($limit('testimonials', 6)),
            'seo' => tap(Seo::make($page?->seo_title, $page?->seo_description, $slides->first()?->media), function (Seo $seo) {
                $seo->canonical = route('home');
            }),
        ]);
    }

    /**
     * Genuine reviews only. While demo mode is on and no genuine review exists yet, the clearly labelled
     * sample reviews fill the section so the layout can be previewed; in production they never appear.
     */
    private function testimonials(int $limit): Collection
    {
        $genuine = Testimonial::active()->where('is_sample', false)->ordered()->limit($limit)->get();

        if ($genuine->isNotEmpty() || ! settings()->bool('demo_mode')) {
            return $genuine;
        }

        return Testimonial::active()->where('is_sample', true)->ordered()->limit($limit)->get();
    }
}
