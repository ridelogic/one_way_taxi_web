<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\DistrictPage;
use App\Models\GalleryPhoto;
use App\Models\PromoVideo;
use App\Models\VehicleCategory;
use Illuminate\Contracts\View\View;

/** Admin dashboard: live totals from the database, quick actions, recent bookings and quick links. */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            // [label, total, icon, link, tone] — every number is counted from the database, nothing is sample data.
            'stats' => [
                ['Total Bookings', Booking::count(), 'calendar', route('admin.bookings.index'), 'red'],
                ['Total Vehicles', VehicleCategory::count(), 'car', route('admin.vehicles.index'), 'yellow'],
                ['Total Videos', PromoVideo::count(), 'youtube', route('admin.videos.index', ['placement' => 'all']), 'red'],
                ['Total Gallery Images', GalleryPhoto::count(), 'image', route('admin.photos.index', ['placement' => 'all']), 'yellow'],
                ['Total Districts', DistrictPage::count(), 'map-pin', route('admin.districts.index'), 'red'],
            ],
            'actions' => [
                ['Add Vehicle', 'car', route('admin.vehicles.create')],
                ['Add Video', 'youtube', route('admin.videos.create')],
                ['Add Photo', 'image', route('admin.photos.index')],
                ['Manage Districts', 'map-pin', route('admin.districts.index')],
                ['Website Settings', 'sliders', route('admin.settings.edit')],
            ],
            'links' => [
                ['Manage Bookings', 'clipboard-check', route('admin.bookings.index')],
                ['Manage Vehicles', 'car', route('admin.vehicles.index')],
                ['Manage Fares', 'rupee', route('admin.fares.index')],
                ['Manage Routes', 'route', route('admin.routes.index')],
                ['Manage Districts', 'map-pin', route('admin.districts.index')],
            ],
            'recentBookings' => Booking::with(['pickupCity', 'destinationCity'])->latest()->limit(6)->get(),
        ]);
    }
}
