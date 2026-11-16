<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FaqRequest;
use App\Models\Faq;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class FaqController extends Controller
{
    public function index(): View
    {
        return view('admin.faqs.index', ['faqs' => Faq::ordered()->get()->groupBy('category')]);
    }

    public function create(): View
    {
        return view('admin.faqs.form', ['faq' => new Faq(['is_active' => true, 'category' => 'General']), 'categories' => $this->categories()]);
    }

    public function store(FaqRequest $request): RedirectResponse
    {
        Faq::create($request->validated());

        return redirect()->route('admin.faqs.index')->with('status', 'Question added.');
    }

    public function edit(Faq $faq): View
    {
        return view('admin.faqs.form', ['faq' => $faq, 'categories' => $this->categories()]);
    }

    public function update(FaqRequest $request, Faq $faq): RedirectResponse
    {
        $faq->update($request->validated());

        return redirect()->route('admin.faqs.index')->with('status', 'Question saved.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()->route('admin.faqs.index')->with('status', 'Question deleted.');
    }

    /** @return array<int, string> */
    private function categories(): array
    {
        return Faq::query()->distinct()->orderBy('category')->pluck('category')->all();
    }
}
