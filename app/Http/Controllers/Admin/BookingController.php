<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\TripType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookingUpdateRequest;
use App\Models\Booking;
use App\Models\VehicleCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string'],
            'vehicle' => ['nullable', 'integer'],
            'trip' => ['nullable', 'string'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $status = BookingStatus::tryFrom($filters['status'] ?? '');

        $bookings = Booking::query()
            ->with(['pickupCity', 'destinationCity', 'vehicleCategory'])
            ->search($filters['q'] ?? null)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($filters['vehicle'] ?? null, fn ($q, $v) => $q->where('vehicle_category_id', $v))
            ->when(TripType::tryFrom($filters['trip'] ?? ''), fn ($q, $t) => $q->where('trip_type', $t))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('travel_date', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('travel_date', '<=', $d))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'filters' => $filters,
            'vehicles' => VehicleCategory::ordered()->pluck('name', 'id'),
            'counts' => Booking::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(Booking $booking): View
    {
        return view('admin.bookings.show', [
            'booking' => $booking->load(['pickupCity', 'destinationCity', 'vehicleCategory', 'route']),
        ]);
    }

    public function update(BookingUpdateRequest $request, Booking $booking): RedirectResponse
    {
        $data = $request->validated();
        $status = BookingStatus::from($data['status']);

        $booking->fill([
            'status' => $status,
            'admin_notes' => $data['admin_notes'] ?? null,
            'estimated_fare' => $data['estimated_fare'] ?? $booking->estimated_fare,
        ]);

        if ($status === BookingStatus::Contacted && ! $booking->contacted_at) {
            $booking->contacted_at = now();
        }
        if ($status === BookingStatus::Confirmed && ! $booking->confirmed_at) {
            $booking->confirmed_at = now();
            $booking->contacted_at ??= now();
        }

        $booking->save();

        return redirect()->route('admin.bookings.show', $booking)->with('status', "Booking {$booking->reference} updated to {$status->label()}.");
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        $reference = $booking->reference;
        $booking->delete();

        return redirect()->route('admin.bookings.index')->with('status', "Booking {$reference} deleted.");
    }
}
