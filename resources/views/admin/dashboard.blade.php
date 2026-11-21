@extends('admin.layout')

@section('title', 'Dashboard')
@section('subtitle', 'Welcome to '.$settings->get('business_name').' Admin Panel')

@section('content')
    {{-- Totals (live counts from the database) --}}
    <ul class="dash-stats" aria-label="Totals">
        @foreach ($stats as [$label, $total, $icon, $href, $tone])
            <li>
                <a href="{{ $href }}" class="dash-stat dash-stat--{{ $tone }}">
                    <span class="dash-stat__icon" aria-hidden="true"><x-icon :name="$icon" :size="24" /></span>
                    <span class="dash-stat__body">
                        <span class="dash-stat__label">{{ $label }}</span>
                        <span class="dash-stat__value">{{ number_format($total) }}</span>
                    </span>
                </a>
            </li>
        @endforeach
    </ul>

    {{-- Quick actions --}}
    <ul class="dash-actions" aria-label="Quick actions">
        @foreach ($actions as [$label, $icon, $href])
            <li><a href="{{ $href }}" class="dash-action"><x-icon :name="$icon" :size="30" :stroke="1.8" /><span>{{ $label }}</span></a></li>
        @endforeach
    </ul>

    <div class="dash-lower">
        {{-- Recent bookings --}}
        <section class="acard dash-recent" aria-labelledby="recent-title">
            <div class="dash-recent__head">
                <h2 id="recent-title" class="acard__title">Recent Bookings</h2>
                <a href="{{ route('admin.bookings.index') }}" class="dash-link">View All <x-icon name="arrow-right" :size="16" /></a>
            </div>
            <div class="atable-wrap">
                <table class="atable">
                    <thead><tr><th>#</th><th>Customer</th><th>Route</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($recentBookings as $booking)
                            <tr>
                                <td><a href="{{ route('admin.bookings.show', $booking) }}">{{ $booking->reference }}</a></td>
                                <td>{{ $booking->customer_name ?: '—' }}</td>
                                <td>{{ $booking->pickupLabel() }} <span aria-label="to">→</span> {{ $booking->destinationLabel() }}</td>
                                <td>{{ $booking->travel_date?->format('d M Y') }}</td>
                                <td><x-admin.status :status="$booking->status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="empty">No bookings yet. New website bookings appear here.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Quick links (existing modules only) --}}
        <section class="acard dash-quick" aria-labelledby="quick-title">
            <h2 id="quick-title" class="acard__title">Quick Links</h2>
            <ul>
                @foreach ($links as [$label, $icon, $href])
                    <li><a href="{{ $href }}"><x-icon :name="$icon" :size="18" class="dash-quick__icon" /> {{ $label }} <x-icon name="chevron-right" :size="18" class="dash-quick__chevron" /></a></li>
                @endforeach
            </ul>
        </section>
    </div>
@endsection
