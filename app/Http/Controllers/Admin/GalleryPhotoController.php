<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DistrictPage;
use App\Models\GalleryPhoto;
use App\Services\MediaUploader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Photo gallery (Homepage Content → Photos, and District Management → Photos). One record per photo; it can show in
 * the homepage gallery, in any number of district galleries, or both. Files are stored through the Media Library.
 */
class GalleryPhotoController extends Controller
{
    public function __construct(private readonly MediaUploader $uploader) {}

    public function index(Request $request): View
    {
        $placement = (string) $request->query('placement', $request->filled('district') ? 'all' : 'home');
        $districtId = (int) $request->query('district', 0);

        return view('admin.photos.index', [
            'photos' => GalleryPhoto::query()->with(['media', 'districts:id,name'])
                ->when($placement === 'home', fn ($q) => $q->where('show_on_home', true))
                ->when($placement === 'inactive', fn ($q) => $q->where('is_active', false))
                ->when($districtId > 0, fn ($q) => $q->whereHas('districts', fn ($d) => $d->whereKey($districtId)))
                ->ordered()->paginate(36)->withQueryString(),
            'districts' => DistrictPage::ordered()->get(['id', 'name']),
            'placement' => $placement,
            'districtId' => $districtId,
        ]);
    }

    /** Upload one or more photos with the same title, description and placement. */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, true);

        $count = DB::transaction(function () use ($request, $data) {
            $order = (int) ($data['sort_order'] ?? 0);
            foreach ($request->file('files') as $i => $file) {
                $media = $this->uploader->store($file, ['alt_text' => $data['title'] ?? null], $request->user()->id);
                $photo = GalleryPhoto::create(['media_id' => $media->id, 'sort_order' => $order + $i] + $this->photoFields($data));
                $photo->districts()->sync($data['district_ids']);
            }

            return count($request->file('files'));
        });

        return $this->backTo($request, $count === 1 ? 'Photo uploaded.' : "{$count} photos uploaded.");
    }

    public function edit(GalleryPhoto $photo): View
    {
        return view('admin.photos.form', [
            'photo' => $photo->load('media', 'districts:id'),
            'districts' => DistrictPage::ordered()->get(['id', 'name']),
            'returnTo' => $this->safeReturn(request()),
        ]);
    }

    public function update(Request $request, GalleryPhoto $photo): RedirectResponse
    {
        $data = $this->validated($request, false, $photo);

        DB::transaction(function () use ($photo, $data) {
            $photo->update(['sort_order' => (int) ($data['sort_order'] ?? 0)] + $this->photoFields($data));
            $photo->districts()->sync($data['district_ids']);
        });

        return $this->backTo($request, 'Photo saved.');
    }

    public function toggle(GalleryPhoto $photo): RedirectResponse
    {
        $photo->update(['is_active' => ! $photo->is_active]);

        return back()->with('status', 'Photo is now '.($photo->is_active ? 'shown.' : 'hidden.'));
    }

    /** Removes the photo from every gallery. The image file stays in Website Settings → Image library. */
    public function destroy(Request $request, GalleryPhoto $photo): RedirectResponse
    {
        $photo->delete();

        return $this->backTo($request, 'Photo deleted from the gallery.');
    }

    /** District Management → Photos: remove the photo from one district only. */
    public function detach(DistrictPage $district, GalleryPhoto $photo): RedirectResponse
    {
        $district->galleryPhotos()->detach($photo->id);

        return redirect()->route('admin.districts.tab', [$district, 'photos'])->with('status', 'Photo removed from '.$district->name.'.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, bool $uploading, ?GalleryPhoto $photo = null): array
    {
        $placement = $request->input('placement');
        $request->merge([
            'show_on_home' => in_array($placement, ['home', 'both'], true),
            'show_on_districts' => in_array($placement, ['districts', 'both'], true),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'district_ids' => array_values(array_unique(array_map('intval', array_filter((array) $request->input('district_ids', []), 'is_numeric')))),
        ]);
        $extensions = implode(',', array_diff(MediaUploader::ALLOWED_EXTENSIONS, ['ico']));

        $validator = validator($request->all(), [
            'files' => [$uploading ? 'required' : 'prohibited', 'array', 'min:1', 'max:20'],
            'files.*' => ['file', 'image', 'mimes:'.$extensions, 'max:'.MediaUploader::MAX_KILOBYTES],
            'title' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'placement' => ['required', Rule::in(['home', 'districts', 'both'])],
            'district_ids' => ['array', 'max:'.max(1, DistrictPage::count())], // every district / UT page at most
            'district_ids.*' => ['integer', Rule::exists('district_pages', 'id')],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ], [
            'files.required' => 'Choose at least one photo to upload.',
            'files.*.image' => 'Only image files (JPG, PNG, WebP or GIF) can be uploaded.',
            'files.*.max' => 'Each photo must be '.(MediaUploader::MAX_KILOBYTES / 1024).' MB or smaller.',
        ], ['files' => 'photos', 'district_ids' => 'districts', 'placement' => 'where to show']);

        $validator->after(function (Validator $v) use ($request) {
            if ($request->input('placement') !== 'home' && $request->input('district_ids') === []) {
                $v->errors()->add('district_ids', 'Select at least one district, or choose “Homepage” only.');
            }
        });

        return $validator->validate() + ['show_on_home' => $request->boolean('show_on_home'), 'show_on_districts' => $request->boolean('show_on_districts')];
    }

    /** @param array<string, mixed> $data */
    private function photoFields(array $data): array
    {
        return [
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'show_on_home' => (bool) $data['show_on_home'],
            'show_on_districts' => (bool) $data['show_on_districts'],
            'is_active' => (bool) $data['is_active'],
        ];
    }

    private function backTo(Request $request, string $message): RedirectResponse
    {
        $target = $this->safeReturn($request);

        return ($target ? redirect()->to($target) : redirect()->route('admin.photos.index', ['placement' => 'all']))->with('status', $message);
    }

    /** Only admin URLs on this site are accepted as return targets. */
    private function safeReturn(Request $request): ?string
    {
        $target = (string) ($request->input('return_to') ?? $request->query('return_to', ''));

        return $target !== '' && str_starts_with($target, url('/admin/')) ? $target : null;
    }
}
