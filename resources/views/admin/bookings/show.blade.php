@extends('admin.layout')

@section('title', 'Booking '.$booking->reference)

@section('content')
    @php
        $tel = preg_replace('/[^0-9+]/', '', $booking->customer_phone);
        $wa = preg_replace('/[^0-9]/', '', $booking->customer_phone);
        $from = $booking->pickup_location ?: $booking->pickupLabel();
        $to = $booking->drop_location ?: $booking->destinationLabel();
        $time = $booking->travel_time ? \Illuminate\Support\Carbon::parse($booking->travel_time)->format('g:i A') : null;
        $steps = [
            ['Received', $booking->created_at],
            ['Contacted', $booking->contacted_at],
            ['Confirmed', $booking->confirmed_at],
        ];
    @endphp

    <div class="page-head">
        <div>
            <a class="back-link" href="{{ route('admin.bookings.index') }}"><x-icon name="arrow-right" :size="14" class="flip" /> All bookings</a>
            <h2 class="page-head__title"><span class="ref">{{ $booking->reference }}</span> <x-admin.status :status="$booking->status" /></h2>
            <p>Received {{ $booking->created_at->format('l, j M Y \a\t H:i') }} ({{ $booking->created_at->diffForHumans() }}) via {{ $booking->source }}.</p>
        </div>
        <div class="page-head__actions">
            <a class="abtn abtn--primary" href="tel:{{ $tel }}"><x-icon name="phone" :size="16" /> Call customer</a>
            <a class="abtn" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"><x-icon name="whatsapp" :size="16" /> WhatsApp</a>
        </div>
    </div>

    <section class="trip-summary" aria-label="Trip summary">
        <div class="trip-summary__route">
            <div>
                <span class="trip-summary__label">From</span>
                <strong>{{ $from }}</strong>
                @if ($booking->pickupCity)<small>Matched: {{ $booking->pickupCity->name }}</small>@endif
            </div>
            <span class="trip-summary__arrow" aria-hidden="true"><x-icon name="arrow-right" :size="20" /></span>
            <div>
                <span class="trip-summary__label">To</span>
                <strong>{{ $booking->trip_type === \App\Enums\TripType::Rental ? ($booking->rental_package ?: 'Rental') : $to }}</strong>
                @if ($booking->destinationCity)<small>Matched: {{ $booking->destinationCity->name }}</small>@endif
            </div>
        </div>
        <ul class="trip-summary__chips">
            <li><x-icon name="calendar" :size="15" /> {{ $booking->travel_date->format('D, j M Y') }}</li>
            <li><x-icon name="clock" :size="15" /> {{ $time ?? 'Time to confirm' }}</li>
            <li><x-icon name="route" :size="15" /> {{ $booking->trip_type->label() }}</li>
            <li><x-icon name="car" :size="15" /> {{ $booking->vehicleCategory?->name ?? 'Vehicle to confirm' }}</li>
            @if ($booking->passengers)<li><x-icon name="users" :size="15" /> {{ $booking->passengers }} {{ \Illuminate\Support\Str::plural('passenger', $booking->passengers) }}</li>@endif
            @if ($booking->return_date)<li><x-icon name="calendar" :size="15" /> Returns {{ $booking->return_date->format('j M') }}</li>@endif
        </ul>
        <div class="trip-summary__fare">
            <span class="trip-summary__label">{{ $booking->estimated_fare ? 'Estimate / quote' : 'Fare' }}</span>
            <strong>{{ $booking->estimated_fare ? inr($booking->estimated_fare) : 'Quote manually' }}</strong>
        </div>
    </section>

    <div class="layout-2">
        <div>
            <section class="acard">
                <h3 class="acard__title"><x-icon name="user" :size="18" /> Customer</h3>
                <div class="person">
                    <span class="person__avatar" aria-hidden="true">{{ \Illuminate\Support\Str::upper(mb_substr($booking->customer_name ?: '?', 0, 1)) }}</span>
                    <div class="person__body">
                        <strong>{{ $booking->customer_name ?: 'Name not provided' }}</strong>
                        <span><a href="tel:{{ $tel }}">{{ $booking->customer_phone }}</a>@if ($booking->customer_email) · <a href="mailto:{{ $booking->customer_email }}">{{ $booking->customer_email }}</a>@endif</span>
                    </div>
                    <div class="person__actions">
                        <a class="abtn abtn--sm" href="tel:{{ $tel }}"><x-icon name="phone" :size="14" /> Call</a>
                        <a class="abtn abtn--sm" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener"><x-icon name="whatsapp" :size="14" /> WhatsApp</a>
                        @if ($booking->customer_email)<a class="abtn abtn--sm" href="mailto:{{ $booking->customer_email }}"><x-icon name="mail" :size="14" /> Email</a>@endif
                    </div>
                </div>
            </section>

            <section class="acard">
                <h3 class="acard__title"><x-icon name="route" :size="18" /> Trip details</h3>
                <dl class="detail-grid">
                    <div><dt>Pickup address</dt><dd>{{ $booking->pickup_address ?: '—' }}</dd></div>
                    <div><dt>Drop address</dt><dd>{{ $booking->drop_address ?: '—' }}</dd></div>
                    <div><dt>Travel date</dt><dd>{{ $booking->travel_date->format('l, j M Y') }}</dd></div>
                    <div><dt>Pickup time</dt><dd>{{ $time ?? '—' }}</dd></div>
                    <div><dt>Trip type</dt><dd>{{ $booking->trip_type->label() }}@if ($booking->rental_package) — {{ $booking->rental_package }}@endif</dd></div>
                    <div><dt>Return date</dt><dd>{{ $booking->return_date?->format('l, j M Y') ?? '—' }}</dd></div>
                    <div><dt>Vehicle</dt><dd>{{ $booking->vehicleCategory?->name ?? '—' }}</dd></div>
                    <div><dt>Passengers</dt><dd>{{ $booking->passengers ?: '—' }}</dd></div>
                    <div><dt>Configured route</dt><dd>@if ($booking->route)<a href="{{ route('admin.routes.edit', $booking->route) }}">{{ $booking->route->name() }}</a>@else No published route — quote manually @endif</dd></div>
                    @if ($booking->distance_km)
                        <div><dt>Road distance</dt><dd>{{ (float) $booking->distance_km }} km @if ($booking->duration_sec)· {{ intdiv($booking->duration_sec, 3600) ? intdiv($booking->duration_sec, 3600).' hr ' : '' }}{{ intdiv($booking->duration_sec % 3600, 60) }} min @endif (one way)</dd></div>
                    @endif
                    @if ($booking->fleet_model_id)
                        <div><dt>Fleet dispatcher</dt><dd>
                            @if ($booking->fleet_trip_id)
                                Booking ID <strong>{{ $booking->fleet_trip_id }}</strong> in Manage Booking
                            @elseif ($booking->fleet_sync_error)
                                <span class="abadge abadge--red">Not sent</span> <small class="muted">{{ $booking->fleet_sync_error }}</small>
                            @else
                                <span class="muted">Not sent (no fleet fare for this trip) — add it in the admin panel</span>
                            @endif
                        </dd></div>
                    @endif
                    <div><dt>IP address</dt><dd class="muted">{{ $booking->ip_address ?: '—' }}</dd></div>
                    <div class="span-all"><dt>Customer notes</dt><dd>{{ $booking->customer_notes ?: '—' }}</dd></div>
                </dl>
            </section>

            <section class="acard">
                <h3 class="acard__title"><x-icon name="rupee" :size="18" /> Fare estimate at time of request</h3>
                @if ($booking->fare_breakdown)
                    <table class="atable fare-table">
                        <tbody>
                            @foreach ($booking->fare_breakdown['lines'] ?? [] as $line)
                                <tr><td>{{ $line['label'] }}</td><td class="num">{{ $line['formatted'] ?? inr($line['amount']) }}</td></tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr><td>Estimated total</td><td class="num">{{ inr($booking->estimated_fare) }}</td></tr></tfoot>
                    </table>
                    @if (! empty($booking->fare_breakdown['excluded']))
                        <p class="muted fare-note">Not included: {{ implode('; ', $booking->fare_breakdown['excluded']) }}</p>
                    @endif
                    @if (! empty($booking->fare_breakdown['is_sample']))
                        <p><span class="abadge abadge--amber">Calculated from sample fare data</span></p>
                    @endif
                @else
                    <p class="muted">No automatic estimate was available for this request. Call the customer with a quote and record it in “Update enquiry”.</p>
                @endif
            </section>
        </div>

        <aside>
            <form method="POST" action="{{ route('admin.bookings.update', $booking) }}" class="acard acard--accent">
                @csrf
                @method('PUT')
                <h3 class="acard__title"><x-icon name="edit" :size="18" /> Update enquiry</h3>
                <x-admin.select name="status" label="Status" :options="\App\Enums\BookingStatus::options()" :value="$booking->status->value" />
                <x-admin.input name="estimated_fare" label="Quoted fare (₹)" type="number" step="0.01" min="0" :value="$booking->estimated_fare" help="Update after confirming the final price with the customer." />
                <x-admin.textarea name="admin_notes" label="Internal notes" :value="$booking->admin_notes" rows="6" help="Only visible to administrators." />
                <button type="submit" class="abtn abtn--primary abtn--block">Save changes</button>
            </form>

            <section class="acard">
                <h3 class="acard__title"><x-icon name="clock" :size="18" /> Progress</h3>
                <ol class="stepper">
                    @foreach ($steps as [$label, $at])
                        <li @class(['is-done' => $at])>
                            <span class="stepper__dot" aria-hidden="true">@if ($at)<x-icon name="check" :size="12" :stroke="3" />@endif</span>
                            <span class="stepper__label">{{ $label }}</span>
                            <span class="stepper__time">{{ $at?->format('d M, H:i') ?? 'Pending' }}</span>
                        </li>
                    @endforeach
                </ol>
                <p class="muted stepper__updated">Last updated {{ $booking->updated_at->format('d M, H:i') }}</p>
            </section>

            <section class="danger-zone">
                <div>
                    <strong>Delete booking</strong>
                    <p>Permanent. Mark it Cancelled instead to keep the record.</p>
                </div>
                <x-admin.delete :action="route('admin.bookings.destroy', $booking)" label="Delete" confirm="Delete this booking permanently? Consider marking it Cancelled instead." />
            </section>
        </aside>
    </div>
@endsection
