<?php

namespace App\Http\Controllers;

use App\Models\DistrictPage;
use App\Models\Page;
use App\Models\Service;
use App\Models\TaxiRoute;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'priority' => '1.0', 'lastmod' => Page::where('slug', 'home')->value('updated_at')],
            ['loc' => route('services.index'), 'priority' => '0.8'],
            ['loc' => route('routes.index'), 'priority' => '0.9'],
            ['loc' => route('districts.index'), 'priority' => '0.8'],
            ['loc' => route('faqs'), 'priority' => '0.6'],
            ['loc' => route('contact'), 'priority' => '0.6'],
            ['loc' => route('booking.create'), 'priority' => '0.8'],
        ]);

        Page::published()->where('slug', '!=', 'home')->get(['slug', 'updated_at'])
            ->each(fn ($p) => $urls->push(['loc' => route('pages.show', $p->slug), 'priority' => '0.5', 'lastmod' => $p->updated_at]));
        Service::publicListing()->get(['slug', 'updated_at'])
            ->each(fn ($s) => $urls->push(['loc' => route('services.show', $s->slug), 'priority' => '0.7', 'lastmod' => $s->updated_at]));
        DistrictPage::published()->ordered()->get(['slug', 'state', 'updated_at'])
            ->each(fn ($d) => $urls->push(['loc' => $d->url(), 'priority' => '0.7', 'lastmod' => $d->updated_at]));
        TaxiRoute::active()->get(['slug', 'updated_at'])
            ->each(fn ($r) => $urls->push(['loc' => route('routes.show', $r->slug), 'priority' => '0.8', 'lastmod' => $r->updated_at]));

        return response()
            ->view('seo.sitemap', ['urls' => $urls], 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $lines = ['User-agent: *'];

        if (settings()->bool('search_engine_indexing')) {
            $lines[] = 'Disallow: /admin';
            $lines[] = 'Disallow: /booking/';
            $lines[] = '';
            $lines[] = 'Sitemap: '.route('sitemap');
        } else {
            $lines[] = 'Disallow: /';
        }

        return response(implode("\n", $lines)."\n", 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
