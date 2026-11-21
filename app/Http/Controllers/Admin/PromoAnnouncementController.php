<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromoAnnouncementRequest;
use App\Models\DistrictPage;
use App\Models\PromoAnnouncement;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Admin → Promotional Announcements: the one offers ticker for the homepage and all location pages.
 * Everything starts disabled; each announcement targets the homepage and/or all or selected location pages.
 */
class PromoAnnouncementController extends Controller
{
    public function index(): View
    {
        if (! PromoAnnouncement::tableReady()) {
            // The new table's migration has not been run on this database yet (pending approval).
            return view('admin.announcements.index', ['announcements' => collect(), 'liveCount' => 0, 'tableMissing' => true]);
        }

        $announcements = PromoAnnouncement::ordered()->when(PromoAnnouncement::targetingReady(), fn ($q) => $q->with('districts:id,name'))->get();

        return view('admin.announcements.index', [
            'announcements' => $announcements,
            'liveCount' => $announcements->filter->isLive()->count(),
            'targetingPending' => ! PromoAnnouncement::targetingReady(),
        ]);
    }

    public function create(): View|RedirectResponse
    {
        if (! PromoAnnouncement::targetingReady()) {
            return $this->pending();
        }

        return $this->form(new PromoAnnouncement(['sort_order' => (int) PromoAnnouncement::max('sort_order') + 1]));
    }

    public function store(PromoAnnouncementRequest $request): RedirectResponse
    {
        if (! PromoAnnouncement::targetingReady()) {
            return $this->pending();
        }

        $announcement = DB::transaction(function () use ($request) {
            $announcement = PromoAnnouncement::create($request->announcementAttributes());
            $announcement->districts()->sync($request->districtIds());

            return $announcement;
        });

        return redirect()->route('admin.announcements.index')->with('status', $this->savedNote($announcement, 'Announcement added'));
    }

    public function edit(PromoAnnouncement $announcement): View|RedirectResponse
    {
        if (! PromoAnnouncement::targetingReady()) {
            return $this->pending();
        }

        return $this->form($announcement->load('districts:id'));
    }

    public function update(PromoAnnouncementRequest $request, PromoAnnouncement $announcement): RedirectResponse
    {
        if (! PromoAnnouncement::targetingReady()) {
            return $this->pending();
        }

        DB::transaction(function () use ($request, $announcement) {
            $announcement->update($request->announcementAttributes());
            $announcement->districts()->sync($request->districtIds());
        });

        return redirect()->route('admin.announcements.index')->with('status', $this->savedNote($announcement->fresh('districts'), 'Announcement saved'));
    }

    public function toggle(PromoAnnouncement $announcement): RedirectResponse
    {
        $announcement->update(['is_enabled' => ! $announcement->is_enabled]);

        return back()->with('status', $this->savedNote(PromoAnnouncement::targetingReady() ? $announcement->load('districts') : $announcement, $announcement->is_enabled ? 'Announcement enabled' : 'Announcement disabled'));
    }

    public function destroy(PromoAnnouncement $announcement): RedirectResponse
    {
        $announcement->delete(); // its location links go with it (cascade)

        return redirect()->route('admin.announcements.index')->with('status', 'Announcement deleted.');
    }

    /** The ticker exactly as visitors would see it, with only this announcement, under the real site header. */
    public function preview(PromoAnnouncement $announcement): View
    {
        $seo = Seo::make('Preview: '.$announcement->title);
        $seo->noindex(true);

        return view('admin.announcements.preview', ['announcement' => PromoAnnouncement::targetingReady() ? $announcement->load('districts:id,name,slug,state') : $announcement, 'seo' => $seo]);
    }

    /** Adding/editing needs the page-targeting columns; until that migration runs, only enable/disable/delete work. */
    private function pending(): RedirectResponse
    {
        return redirect()->route('admin.announcements.index')->withErrors(['announcement' => 'Adding and editing announcements is paused until the page-targeting database update has been applied.']);
    }

    private function form(PromoAnnouncement $announcement): View
    {
        return view('admin.announcements.form', [
            'announcement' => $announcement,
            // The real location pages (38 Tamil Nadu districts + Puducherry), from the database.
            'districts' => DistrictPage::ordered()->get(['id', 'name', 'state', 'sort_order']),
        ]);
    }

    private function savedNote(PromoAnnouncement $a, string $action): string
    {
        $where = mb_strtolower($a->targetSummary());

        return $action.' — '.match ($a->displayStatus()) {
            'Live' => 'it is now showing on: '.$where.'.',
            'Scheduled' => 'it will show on '.$where.' from '.$a->starts_at->format('d M Y, h:i A').'.',
            'Expired' => 'its end date has passed, so it is not showing.',
            default => 'it is disabled and not showing on the website.',
        };
    }
}
