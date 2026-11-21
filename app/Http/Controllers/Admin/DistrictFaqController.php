<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DistrictFaq;
use App\Models\DistrictPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** District Management → FAQs. */
class DistrictFaqController extends Controller
{
    public function store(Request $request, DistrictPage $district): RedirectResponse
    {
        $district->districtFaqs()->create($this->validated($request));

        return redirect()->route('admin.districts.tab', [$district, 'faqs'])->with('status', 'FAQ added.');
    }

    public function update(Request $request, DistrictFaq $faq): RedirectResponse
    {
        $faq->update($this->validated($request));

        return redirect()->route('admin.districts.tab', [$faq->district, 'faqs'])->with('status', 'FAQ saved.');
    }

    public function destroy(DistrictFaq $faq): RedirectResponse
    {
        $district = $faq->district;
        $faq->delete();

        return redirect()->route('admin.districts.tab', [$district, 'faqs'])->with('status', 'FAQ deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $request->merge(['is_active' => $request->boolean('is_active'), 'sort_order' => (int) $request->input('sort_order', 0)]);

        return $request->validate([
            'question' => ['required', 'string', 'max:200'],
            'answer' => ['required', 'string', 'max:2000'],
            'sort_order' => ['integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
        ]);
    }
}
