<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MediaUpdateRequest;
use App\Http\Requests\Admin\MediaUploadRequest;
use App\Models\MediaAsset;
use App\Services\MediaUploader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MediaController extends Controller
{
    public function __construct(private readonly MediaUploader $uploader) {}

    public function index(Request $request): View
    {
        return view('admin.media.index', [
            'media' => $this->query($request)->paginate(24)->withQueryString(),
            'search' => $this->queryText($request, 'q'),
        ]);
    }

    public function store(MediaUploadRequest $request): RedirectResponse|JsonResponse
    {
        $files = $request->file('files') ?? [$request->file('file')];

        $stored = DB::transaction(fn () => collect($files)->filter()->map(
            fn ($file) => $this->uploader->store($file, ['alt_text' => $request->input('alt_text')], $request->user()->id)
        ));

        if ($request->expectsJson()) {
            return response()->json(['data' => $stored->map->toPickerArray()->values()], 201);
        }

        return redirect()->route('admin.media.index')->with('status', $stored->count().' image(s) uploaded.');
    }

    public function edit(MediaAsset $medium): View
    {
        return view('admin.media.edit', [
            'media' => $medium,
            'usage' => $medium->usageCount(),
        ]);
    }

    public function update(MediaUpdateRequest $request, MediaAsset $medium): RedirectResponse
    {
        $medium->update($request->validated());

        return redirect()->route('admin.media.edit', $medium)->with('status', 'Image details saved.');
    }

    public function replace(MediaUploadRequest $request, MediaAsset $medium): RedirectResponse
    {
        $this->uploader->replace($medium, $request->file('file') ?? $request->file('files')[0]);

        return redirect()->route('admin.media.edit', $medium)->with('status', 'Image file replaced. Everywhere it is used now shows the new file.');
    }

    public function destroy(MediaAsset $medium): RedirectResponse
    {
        if (($uses = $medium->usageCount()) > 0) {
            return back()->with('error', "This image is used in {$uses} place(s). Replace or remove it there before deleting.");
        }

        $medium->delete();

        return redirect()->route('admin.media.index')->with('status', 'Image deleted.');
    }

    /** JSON list for the media picker modal. */
    public function picker(Request $request): JsonResponse
    {
        $page = $this->query($request)->paginate(30)->withQueryString();

        return response()->json([
            'data' => collect($page->items())->map->toPickerArray()->values(),
            'next_page_url' => $page->nextPageUrl(),
        ]);
    }

    private function query(Request $request)
    {
        $term = $this->queryText($request, 'q');

        return MediaAsset::query()
            ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', "%{$term}%")
                ->orWhere('alt_text', 'like', "%{$term}%")
                ->orWhere('original_name', 'like', "%{$term}%")))
            ->latest('id');
    }
}
