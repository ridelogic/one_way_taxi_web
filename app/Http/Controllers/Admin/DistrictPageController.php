<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DistrictPageRequest;
use App\Models\DistrictPage;
use App\Support\HtmlSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Admin → District Management: the list of districts and the District Details tab. The other tabs (videos, photos,
 * SEO, cars, services, routes, FAQs) live in DistrictCmsController and the small item controllers.
 */
class DistrictPageController extends Controller
{
    public function index(Request $request): View
    {
        $term = $this->queryText($request, 'q');

        return view('admin.districts.index', [
            'districts' => DistrictPage::query()
                ->with(['heroMedia', 'cardMedia'])
                ->withCount(['videos', 'galleryPhotos', 'districtVehicles', 'districtFaqs'])
                ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('region', 'like', "%{$term}%")))
                ->ordered()->get(),
            'search' => $term,
        ]);
    }

    public function create(): View
    {
        return view('admin.districts.details', ['district' => new DistrictPage(['is_published' => true, 'state' => 'Tamil Nadu'])]);
    }

    public function store(DistrictPageRequest $request): RedirectResponse
    {
        $district = DistrictPage::create($this->payload($request));

        return redirect()->route('admin.districts.edit', $district)->with('status', 'District added. Now add its videos, photos, cars and other details using the tabs.');
    }

    /** District Details tab. */
    public function edit(DistrictPage $district): View
    {
        return view('admin.districts.details', ['district' => DistrictCmsController::withTabCounts($district)]);
    }

    public function update(DistrictPageRequest $request, DistrictPage $district): RedirectResponse
    {
        $district->update($this->payload($request));

        return redirect()->route('admin.districts.edit', $district)->with('status', 'District details saved — the public page is updated.');
    }

    /** Publish or unpublish in one click. */
    public function toggle(DistrictPage $district): RedirectResponse
    {
        $district->update(['is_published' => ! $district->is_published]);

        return back()->with('status', $district->name.($district->is_published ? ' is now live on the website.' : ' is now hidden from the website.'));
    }

    public function destroy(DistrictPage $district): RedirectResponse
    {
        $district->delete();

        return redirect()->route('admin.districts.index')->with('status', 'District deleted.');
    }

    /** @return array<string, mixed> */
    private function payload(DistrictPageRequest $request): array
    {
        $data = $request->validated();
        $data['content'] = HtmlSanitizer::clean($data['content'] ?? '');

        return $data;
    }
}
