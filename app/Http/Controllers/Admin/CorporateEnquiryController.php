<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EnquiryUpdateRequest;
use App\Models\CorporateEnquiry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CorporateEnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $status = EnquiryStatus::tryFrom($this->queryText($request, 'status'));
        $term = $this->queryText($request, 'q');

        return view('admin.enquiries.index', [
            'type' => 'corporate',
            'title' => 'Corporate enquiries',
            'search' => $term,
            'enquiries' => CorporateEnquiry::search($term)
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest()->paginate(25)->withQueryString(),
            'counts' => CorporateEnquiry::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'status' => $status,
        ]);
    }

    public function show(CorporateEnquiry $enquiry): View
    {
        return view('admin.enquiries.show', ['type' => 'corporate', 'enquiry' => $enquiry]);
    }

    public function update(EnquiryUpdateRequest $request, CorporateEnquiry $enquiry): RedirectResponse
    {
        $enquiry->update($request->validated());

        return redirect()->route('admin.corporate-enquiries.show', $enquiry)->with('status', "Enquiry {$enquiry->reference} updated.");
    }

    public function destroy(CorporateEnquiry $enquiry): RedirectResponse
    {
        $enquiry->delete();

        return redirect()->route('admin.corporate-enquiries.index')->with('status', 'Enquiry deleted.');
    }
}
