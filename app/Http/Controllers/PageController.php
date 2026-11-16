<?php

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use App\Models\Page;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show(Request $request, Page $page): View
    {
        if ($page->slug === 'home') {
            abort(404);
        }

        abort_unless($page->isPublished(), 404);

        return $this->render($page);
    }

    /** Shared by the public route and the admin preview (which may render drafts). */
    public function render(Page $page, bool $preview = false): View
    {
        $page->load('heroMedia');

        $extras = [];
        if ($page->template === 'about') {
            $home = Page::where('slug', 'home')->first();
            $extras['homeSections'] = $home
                ? $home->sections()->where('is_active', true)->whereIn('key', ['stats', 'why_choose', 'how_it_works'])->with('media')->get()->keyBy('key')
                : collect();
        }

        $seo = Seo::make($page->seo_title ?: $page->title, $page->seo_description, $page->heroMedia);
        if ($preview) {
            $seo->noindex();
        }

        return view('pages.show', array_merge([
            'page' => $page,
            'preview' => $preview,
            'seo' => $seo,
        ], $extras));
    }

    public function credits(): View
    {
        return view('pages.credits', [
            'media' => MediaAsset::whereNotNull('credit')->orderBy('title')->get(),
            'seo' => Seo::make('Image Credits', 'Attribution for photographs used on this website.'),
        ]);
    }
}
