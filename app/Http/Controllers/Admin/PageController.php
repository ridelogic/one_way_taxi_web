<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PageController as PublicPageController;
use App\Http\Requests\Admin\PageRequest;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Support\HtmlSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PageController extends Controller
{
    public function index(Request $request): View
    {
        $term = $this->queryText($request, 'q');

        return view('admin.pages.index', [
            'pages' => Page::query()
                ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', "%{$term}%")->orWhere('slug', 'like', "%{$term}%")))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
                ->orderByDesc('is_system')->orderBy('title')
                ->paginate(20)->withQueryString(),
            'search' => $term,
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.form', ['page' => new Page(['status' => 'draft', 'template' => 'default'])]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $page = DB::transaction(function () use ($request) {
            $page = Page::create($this->payload($request));
            $this->syncNavigation($page);

            return $page;
        });

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Page created.');
    }

    public function edit(Page $page): View|RedirectResponse
    {
        if ($page->slug === 'home') {
            return redirect()->route('admin.homepage.index')->with('status', 'The homepage is edited section by section.');
        }

        return view('admin.pages.form', ['page' => $page->load('heroMedia')]);
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $oldSlug = $page->slug;

        DB::transaction(function () use ($request, $page, $oldSlug) {
            $page->update($this->payload($request, $page));

            // Menu links follow the page to its new URL instead of pointing at the old one, which would 404.
            if ($page->slug !== $oldSlug) {
                NavigationItem::where('url', '/'.$oldSlug)->update(['url' => '/'.$page->slug]);
            }

            $this->syncNavigation($page);
        });

        return $page->slug === 'home'
            ? redirect()->route('admin.homepage.index')->with('status', 'Homepage SEO saved.')
            : redirect()->route('admin.pages.edit', $page)->with('status', 'Page saved.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        if ($page->is_system) {
            return back()->with('error', 'System pages (home, about, legal) can be unpublished but not deleted.');
        }

        DB::transaction(function () use ($page) {
            NavigationItem::where('url', '/'.$page->slug)->delete();
            $page->delete();
        });

        return redirect()->route('admin.pages.index')->with('status', 'Page deleted.');
    }

    public function preview(Page $page, PublicPageController $public): View
    {
        if ($page->slug === 'home') {
            abort(404);
        }

        return $public->render($page, preview: true);
    }

    /** @return array<string, mixed> */
    private function payload(PageRequest $request, ?Page $page = null): array
    {
        $data = $request->validated();
        $data['body'] = HtmlSanitizer::clean($data['body'] ?? '');
        $data['nav_order'] = $data['nav_order'] ?? 0;

        if ($data['status'] === 'published' && ! $page?->published_at) {
            $data['published_at'] = now();
        }

        return $data;
    }

    /** "Show in navigation" adds/removes a header menu link to the page. */
    private function syncNavigation(Page $page): void
    {
        if ($page->slug === 'home') {
            return;
        }

        $url = '/'.$page->slug;

        // Links to a draft page would 404, so hide them in every menu until it is published again.
        NavigationItem::where('url', $url)->update(['is_active' => $page->isPublished()]);

        $item = NavigationItem::where('menu', 'header')->where('url', $url)->first();

        if ($page->show_in_nav && $page->isPublished()) {
            NavigationItem::updateOrCreate(
                ['menu' => 'header', 'url' => $url],
                ['label' => $page->title, 'sort_order' => $page->nav_order ?: ($item?->sort_order ?? 50), 'is_active' => true],
            );
        } elseif ($item && ! $page->is_system) {
            $item->delete();
        }
    }
}
