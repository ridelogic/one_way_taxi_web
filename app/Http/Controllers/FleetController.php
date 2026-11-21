<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

/**
 * The website no longer has a Fleet section. Vehicle categories still power the booking form and the
 * fare estimates (Admin → Vehicles and Fare Rules), so the old /fleet addresses permanently redirect to the
 * booking page, where visitors choose their vehicle — bookmarks and search results never hit a dead page.
 */
class FleetController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('booking.create', status: 301);
    }

    public function show(string $vehicle): RedirectResponse
    {
        return redirect()->route('booking.create', status: 301);
    }
}
