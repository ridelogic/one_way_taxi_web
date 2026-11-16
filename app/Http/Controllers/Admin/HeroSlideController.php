<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HeroSlideRequest;
use App\Models\HeroSlide;
use App\Support\Ordering;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HeroSlideController extends Controller
{
    public function index(): View
    {
        return view('admin.slides.index', ['slides' => HeroSlide::with(['media', 'foreground'])->ordered()->get()]);
    }

    public function create(): View
    {
        return view('admin.slides.form', ['slide' => new HeroSlide([
            'is_active' => true, 'overlay_strength' => 45, 'text_align' => 'left',
            'sort_order' => (int) HeroSlide::max('sort_order') + 1,
            'primary_label' => 'Book Now', 'primary_url' => '/book',
        ])]);
    }

    public function store(HeroSlideRequest $request): RedirectResponse
    {
        $slide = HeroSlide::create($request->validated());

        return redirect()->route('admin.slides.index')->with('status', $slide->is_active
            ? 'Slide added. It is live on the homepage slider.'
            : 'Slide added. It is hidden until you mark it visible.');
    }

    public function edit(HeroSlide $slide): View
    {
        return view('admin.slides.form', ['slide' => $slide]);
    }

    public function update(HeroSlideRequest $request, HeroSlide $slide): RedirectResponse
    {
        $slide->update($request->validated());

        return redirect()->route('admin.slides.edit', $slide)->with('status', $slide->is_active
            ? 'Slide saved. Changes are live on the homepage.'
            : 'Slide saved. It is hidden on the homepage.');
    }

    public function destroy(HeroSlide $slide): RedirectResponse
    {
        if ($slide->is_active && HeroSlide::active()->count() === 1) {
            return back()->with('error', 'This is the only visible slide. Add or show another slide before deleting it.');
        }

        $slide->delete();

        return redirect()->route('admin.slides.index')->with('status', 'Slide deleted.');
    }

    public function move(Request $request, HeroSlide $slide): RedirectResponse
    {
        Ordering::move($slide, HeroSlide::query()->ordered(), $request->input('direction') === 'up' ? -1 : 1);

        return back()->with('status', 'Slide order updated.');
    }
}
