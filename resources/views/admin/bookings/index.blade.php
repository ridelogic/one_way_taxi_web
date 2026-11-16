@extends('admin.layout')

@section('title', 'Bookings & enquiries')

@section('content')
    <nav class="tabs" aria-label="Status filter">
        <a href="{{ route('admin.bookings.index', array_merge(request()->except(['status', 'page']), [])) }}" @class(['is-active' => empty($filters['status'])])>All ({{ $counts->sum() }})</a>
        @foreach (\App\Enums\BookingStatus::cases() as $status)
            <a href="{{ route('admin.bookings.index', array_merge(request()->except('page'), ['status' => $status->value])) }}" @class(['is-active' => ($filters['status'] ?? null) === $status->value])>{{ $status->label() }} ({{ $counts[$status->value] ?? 0 }})</a>
        @endforeach
    </nav>

    <form method="GET" class="filters">
        <input type="hidden" name="status" value="{{ $filters['status'] ?? '' }}">
        <div class="afield"><label for="q">Search</label><input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Reference, name, phone, email"></div>
        <div class="afield">
            <label for="vehicle">Vehicle</label>
            <select id="vehicle" name="vehicle">
                <option value="">Any</option>
                @foreach ($vehicles as $id => $name)
                    <option value="{{ $id }}" @selected((string) ($filters['vehicle'] ?? '') === (string) $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="afield">
            <label for="trip">Trip type</label>
            <select id="trip" name="trip">
                <option value="">Any</option>
                @foreach (\App\Enums\TripType::options() as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['trip'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="afield"><label for="from">Travel from</label><input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></div>
        <div class="afield"><label for="to">Travel to</label><input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}"></div>
        <button class="abtn abtn--primary" type="submit">Apply</button>
        <a class="abtn abtn--ghost" href="{{ route('admin.bookings.index') }}">Reset</a>
    </form>

    <div class="acard atable-wrap" style="padding:0">
        <table class="atable">
            <thead><tr><th>Reference</th><th>Customer</th><th>Trip</th><th>Type</th><th>Vehicle</th><th>Travel date</th><th>Estimate</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($bookings as $booking)
                    <tr>
                        <td><span class="ref">{{ $booking->reference }}</span><br><small class="muted">{{ $booking->created_at->format('d M Y, H:i') }}</small></td>
                        <td>{{ $booking->customer_name ?: '—' }}<br><a href="tel:{{ preg_replace('/[^0-9+]/', '', $booking->customer_phone) }}"><small>{{ $booking->customer_phone }}</small></a></td>
                        <td>{{ $booking->pickupLabel() }}@if ($booking->trip_type !== \App\Enums\TripType::Rental) → {{ $booking->destinationLabel() }}@else <small class="muted">({{ $booking->rental_package }})</small>@endif</td>
                        <td><span class="abadge abadge--slate">{{ $booking->trip_type->label() }}</span></td>
                        <td>{{ $booking->vehicleCategory?->name ?? '—' }}</td>
                        <td>{{ $booking->travel_date->format('d M Y') }}<br><small class="muted">{{ substr((string) $booking->travel_time, 0, 5) }}</small></td>
                        <td>{{ $booking->estimated_fare ? inr($booking->estimated_fare) : '—' }}</td>
                        <td><x-admin.status :status="$booking->status" /></td>
                        <td class="actions"><a class="abtn abtn--sm abtn--primary" href="{{ route('admin.bookings.show', $booking) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty">No bookings match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $bookings->links('admin.partials.pagination') }}
@endsection
