<?php

namespace App\Http\Controllers;

use App\Enums\EnquiryStatus;
use App\Http\Requests\CorporateEnquiryRequest;
use App\Http\Requests\PartnerEnquiryRequest;
use App\Models\CorporateEnquiry;
use App\Models\Page;
use App\Models\PartnerEnquiry;
use App\Rules\IndianMobile;
use App\Support\Reference;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** Public Corporate and Become-a-Partner pages with their enquiry forms. */
class BusinessEnquiryController extends Controller
{
    public function corporate(): View
    {
        $section = $this->homeSection('corporate');

        return view('business.corporate', [
            'section' => $section,
            'seo' => Seo::make('Corporate Taxi Services', 'Employee transport, airport transfers and outstation travel for businesses, with consolidated billing on request.', $section?->media),
        ]);
    }

    public function storeCorporate(CorporateEnquiryRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['consent', 'website']);

        $enquiry = CorporateEnquiry::create(array_merge($data, [
            'reference' => Reference::generate('COR', CorporateEnquiry::class),
            'phone' => IndianMobile::normalize($data['phone']),
            'email' => mb_strtolower($data['email']),
            'status' => EnquiryStatus::New,
            'ip_address' => $request->ip(),
        ]));

        return redirect()->to(route('corporate').'#enquiry')
            ->with('status', "Thank you! Your corporate enquiry has been received. Reference: {$enquiry->reference}. Our corporate team will contact you shortly.");
    }

    public function partner(): View
    {
        $section = $this->homeSection('partner');

        return view('business.partner', [
            'section' => $section,
            'open' => settings()->bool('partner_programme_open'),
            'seo' => Seo::make('Become a Partner — Drive With Us', 'Drivers, owner-drivers and fleet owners: register your interest in partnering with One Way Taxi.', $section?->media),
        ]);
    }

    public function storePartner(PartnerEnquiryRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['consent', 'website']);

        $enquiry = PartnerEnquiry::create(array_merge($data, [
            'reference' => Reference::generate('PTR', PartnerEnquiry::class),
            'phone' => IndianMobile::normalize($data['phone']),
            'email' => isset($data['email']) ? mb_strtolower($data['email']) : null,
            'status' => EnquiryStatus::New,
            'ip_address' => $request->ip(),
        ]));

        return redirect()->to(route('partner').'#register')
            ->with('status', "Thank you for registering your interest. Reference: {$enquiry->reference}. Our partner team will call you to discuss next steps and document verification.");
    }

    public function app(): View
    {
        return view('business.app', [
            'section' => $this->homeSection('app'),
            'seo' => Seo::make('Download the App', 'Get the One Way Taxi app for Android and iOS, or book online.'),
        ]);
    }

    private function homeSection(string $key)
    {
        return Page::where('slug', 'home')->first()?->sections()->where('key', $key)->with('media')->first();
    }
}
