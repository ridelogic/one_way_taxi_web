<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromoVideoRequest;
use App\Models\DistrictPage;
use App\Models\PromoVideo;
use App\Services\YouTubeEmbedChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Admin → Video Promotions: YouTube videos for the homepage and district pages (one record, many districts). */
class PromoVideoController extends Controller
{
    public function index(Request $request): View
    {
        $term = $this->queryText($request, 'q');
        // Homepage Content → Videos opens on the homepage videos; 'all' lists every video (homepage and districts).
        $placement = (string) $request->query('placement', $request->filled('district') ? 'all' : 'home');
        $districtId = (int) $request->query('district', 0);

        return view('admin.videos.index', [
            'videos' => PromoVideo::query()
                ->with(['thumbnail', 'districts:id,name'])
                ->when($term !== '', fn ($q) => $q->where('title', 'like', "%{$term}%"))
                ->when($placement === 'home', fn ($q) => $q->where('show_on_home', true))
                ->when($placement === 'inactive', fn ($q) => $q->where('is_active', false))
                ->when($districtId > 0, fn ($q) => $q->whereHas('districts', fn ($d) => $d->whereKey($districtId)))
                ->ordered()->paginate(30)->withQueryString(),
            'districts' => DistrictPage::ordered()->get(['id', 'name']),
            'homeActive' => PromoVideo::active()->where('show_on_home', true)->count(),
            'search' => $term,
            'placement' => $placement,
            'districtId' => $districtId,
        ]);
    }

    public function create(): View
    {
        return $this->form(new PromoVideo(['is_active' => true, 'show_on_districts' => true, 'category' => 'promotional']));
    }

    public function store(PromoVideoRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $video = PromoVideo::create($request->safe()->except('district_ids') + $request->embedAttributes());
            $video->districts()->sync($request->validated('district_ids') ?? []);
        });

        return $this->backTo($request, 'Video added.'.$this->checkNote($request));
    }

    public function edit(PromoVideo $video): View
    {
        return $this->form($video->load('districts:id'));
    }

    public function update(PromoVideoRequest $request, PromoVideo $video): RedirectResponse
    {
        DB::transaction(function () use ($request, $video) {
            $video->update($request->safe()->except('district_ids') + $request->embedAttributes());
            $video->districts()->sync($request->validated('district_ids') ?? []);
        });

        return $this->backTo($request, 'Video saved.'.$this->checkNote($request));
    }

    /** Activate / deactivate from the list. Activation still respects the 10-videos-per-placement limit. */
    public function toggle(PromoVideo $video): RedirectResponse
    {
        if (! $video->is_active) {
            $max = PromoVideo::MAX_ACTIVE_PER_PLACEMENT;
            if ($video->show_on_home && PromoVideo::active()->where('show_on_home', true)->where('home_section', $video->home_section)->count() >= $max) {
                return back()->withErrors(['video' => "This homepage section already has {$max} active videos — deactivate one first."]);
            }
            if ($video->show_on_districts) {
                $full = $video->districts()->withCount(['videos as active_videos' => fn ($q) => $q->where('is_active', true)->where('show_on_districts', true)])
                    ->get()->filter(fn ($d) => $d->active_videos >= $max)->pluck('name');
                if ($full->isNotEmpty()) {
                    return back()->withErrors(['video' => "These districts already have {$max} active videos: ".$full->join(', ').'.']);
                }
            }
        }

        $video->update(['is_active' => ! $video->is_active]);

        return back()->with('status', '“'.$video->title.'” is now '.($video->is_active ? 'active.' : 'inactive.'));
    }

    public function destroy(PromoVideo $video): RedirectResponse
    {
        $video->delete(); // the district assignments are removed with it (cascade); nothing is stored on YouTube's side

        return redirect()->route('admin.videos.index', ['placement' => 'all'])->with('status', 'Video deleted.');
    }

    private function form(PromoVideo $video): View
    {
        return view('admin.videos.form', [
            'video' => $video,
            'returnTo' => $this->safeReturn(request()),
            'districts' => DistrictPage::ordered()->get(['id', 'name', 'region']),
        ]);
    }

    /** District Management → Videos: remove the video from one district only (the video itself is kept). */
    public function detach(DistrictPage $district, PromoVideo $video): RedirectResponse
    {
        $district->videos()->detach($video->id);

        return redirect()->route('admin.districts.tab', [$district, 'videos'])->with('status', '“'.$video->title.'” removed from '.$district->name.'.');
    }

    /** After saving, go back to the screen the form was opened from (a district tab), else the video list. */
    private function backTo(Request $request, string $message): RedirectResponse
    {
        $target = $this->safeReturn($request);

        return ($target ? redirect()->to($target) : redirect()->route('admin.videos.index', ['placement' => 'all']))->with('status', $message);
    }

    /** Only admin URLs on this site are accepted as return targets. */
    private function safeReturn(Request $request): ?string
    {
        $target = (string) ($request->input('return_to') ?? $request->query('return_to', ''));

        return $target !== '' && str_starts_with($target, url('/admin/')) ? $target : null;
    }

    /** Admin form: live check of a pasted link (video ID, YouTube's embed answer and the preview URL). */
    public function check(Request $request, YouTubeEmbedChecker $checker): JsonResponse
    {
        $id = PromoVideo::parseYoutubeId(is_string($request->query('url')) ? $request->query('url') : '');
        if (! $id) {
            return response()->json(['id' => null, 'status' => 'invalid', 'message' => 'This is not a YouTube video link. Use a youtube.com/watch, youtu.be, Shorts or embed link.']);
        }

        $result = $checker->check($id);

        return response()->json([
            'id' => $id,
            'status' => $result['status'],
            'message' => $result['message'],
            'title' => $result['title'],
            'thumbnail' => 'https://i.ytimg.com/vi/'.$id.'/hqdefault.jpg',
            'embed' => in_array($result['status'], [YouTubeEmbedChecker::OK, YouTubeEmbedChecker::UNKNOWN], true)
                ? (new PromoVideo(['youtube_id' => $id]))->embedUrl(false) : null,
        ]);
    }

    /** Extra note in the success message when YouTube could not be reached to verify the video. */
    private function checkNote(PromoVideoRequest $request): string
    {
        return ($request->embedCheck['status'] ?? null) === YouTubeEmbedChecker::UNKNOWN
            ? ' Note: YouTube could not be reached to verify it — open Edit later to check again.' : '';
    }
}