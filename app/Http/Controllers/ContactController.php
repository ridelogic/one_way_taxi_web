<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Models\ContactMessage;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('contact', [
            'seo' => Seo::make('Contact Us', 'Get in touch with One Way Taxi for bookings, quotes and support.'),
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        ContactMessage::create($request->safe()->except('website') + ['ip_address' => $request->ip()]);

        return redirect()->route('contact')->with('status', 'Thank you! Your message has been received. Our team will get back to you shortly.');
    }
}
