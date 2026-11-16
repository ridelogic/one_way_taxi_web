<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EnquiryUpdateRequest;
use App\Models\PartnerEnquiry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PartnerEnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $status = EnquiryStatus::tryFrom($this->queryText($request, 'status'));
        $term = $this->queryText($request, 'q');

        return view('admin.enquiries.index', [
            'type' => 'partner',
            'title' => 'Partner registrations',
            'search' => $term,
            'enquiries' => PartnerEnquiry::search($term)
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest()->paginate(25)->withQueryString(),
            'counts' => PartnerEnquiry::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'status' => $status,
        ]);
    }

    public function show(PartnerEnquiry $enquiry): View
    {
        return view('admin.enquiries.show', ['type' => 'partner', 'enquiry' => $enquiry]);
    }

    public function update(EnquiryUpdateRequest $request, PartnerEnquiry $enquiry): RedirectResponse
    {
        $enquiry->update($request->validated());

        return redirect()->route('admin.partner-enquiries.show', $enquiry)->with('status', "Registration {$enquiry->reference} updated.");
    }

    public function destroy(PartnerEnquiry $enquiry): RedirectResponse
    {
        $enquiry->delete();

        return redirect()->route('admin.partner-enquiries.index')->with('status', 'Registration deleted.');
    }
}
