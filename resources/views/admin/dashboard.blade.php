@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
    @php
        $business = $settings->get('business_name');
        $words = preg_split('/\s+/', \Illuminate\Support\Str::upper(trim($business)));
        $lastWord = count($words) > 1 ? array_pop($words) : null;
        $brandTitle = implode(' ', $words);

        // Booking status donut: open enquiries (pending, contacted or confirmed), completed and cancelled.
        $statusOf = fn (\App\Enums\BookingStatus ...$cases) => array_sum(array_map(fn ($case) => (int) ($statusCounts[$case->value] ?? 0), $cases));
        $donutTotal = $statusOf(...\App\Enums\BookingStatus::cases());
        $donut = [
            ['Completed', $statusOf(\App\Enums\BookingStatus::Completed), 'blue', 'completed', null],
            ['Pending', $statusOf(\App\Enums\BookingStatus::Pending, \App\Enums\BookingStatus::Contacted, \App\Enums\BookingStatus::Confirmed), 'orange', 'pending', 'Includes contacted and confirmed'],
            ['Cancelled', $statusOf(\App\Enums\BookingStatus::Cancelled), 'red', 'cancelled', null],
        ];
        $ring = 2 * M_PI * 54;

        // Chart palette (reference colours). Used as SVG attributes and, via CSS variables on .dash-row, by the HTML parts.
        $chart = ['blue' => '#1677FF', 'orange' => '#FA8C16', 'red' => '#F04438', 'green' => '#52C41A', 'empty' => '#EDF0F4', 'grid' => '#E9EEF5'];

        // Line chart geometry in percent of the plot area: the SVG stretches to its card, text and dots are HTML so they never scale.
        $lineChart = function (array $values): array {
            $step = max(1, (int) ceil(max(1, ...$values) / 4));
            $max = $step * 4;
            $n = count($values);
            $points = [];
            foreach ($values as $i => $v) {
                $points[] = [round(4 + ($n > 1 ? $i * 92 / ($n - 1) : 46), 2), round(100 - $v / $max * 100, 2), $v];
            }
            $line = 'M'.implode(' L', array_map(fn ($p) => $p[0].' '.$p[1], $points));

            return [
                'points' => $points, 'line' => $line, 'area' => $line.' L'.end($points)[0].' 100 L'.$points[0][0].' 100 Z',
                'grid' => array_map(fn ($i) => [$i * 25, $max - $step * $i], range(0, 4)),
            ];
        };
        $ranges = [
            'week' => ['Last 7 Days', array_column($days, 'label'), array_column($days, 'title'), array_column($days, 'bookings')],
            'months' => ['Last 6 Months', array_column($months, 'label'), array_column($months, 'title'), array_column($months, 'bookings')],
        ];

        // Revenue growth (six months, oldest first; revenue = estimated fares, see the controller).
        $compact = function (int $paise): string {
            $rupees = intdiv($paise, 100);
            foreach ([[10000000, 'Cr'], [100000, 'L'], [1000, 'K']] as [$unit, $suffix]) {
                if ($rupees >= $unit) {
                    return '₹'.rtrim(rtrim(number_format($rupees / $unit, 1), '0'), '.').$suffix;
                }
            }

            return \App\Support\Money::format($paise, true);
        };
        $monthPaise = array_column($months, 'paise');
        $revenueMax = max(1, ...$monthPaise);
        $quarter = array_sum(array_slice($monthPaise, -3));
        $previousQuarter = array_sum(array_slice($monthPaise, 0, 3));
        $growth = $previousQuarter > 0 ? (int) round(($quarter - $previousQuarter) / $previousQuarter * 100) : null;
        $growthLabel = $growth === null ? ($quarter > 0 ? 'New' : '0%') : ($growth > 0 ? '+' : '').number_format($growth).'%';

        $spark = function (array $values): string {
            $max = max(1, ...$values);
            $n = max(1, count($values) - 1);

            return implode(' ', array_map(fn ($v, $i) => round($i * 100 / $n, 1).','.round(30 - $v / $max * 26, 1), $values, array_keys($values)));
        };
    @endphp

    {{-- Promotional banner (decorative scene cropped from the local login artwork) --}}
    <section class="dash-banner" aria-labelledby="dash-banner-title">
        <div class="dash-banner__copy">
            <div class="dash-banner__logo">
                <svg class="dash-banner__mark" viewBox="0 0 80 68" aria-hidden="true" focusable="false"><path fill="#FBB70A" fill-rule="evenodd" d="M30 2h20a2 2 0 0 1 2 2v6h6.6c2.6 0 4.9 1.6 5.8 4l4.5 12.1 4.4 1.6A5 5 0 0 1 77 32.4V54a4 4 0 0 1-4 4h-1v6a3 3 0 0 1-3 3h-7a3 3 0 0 1-3-3v-6H21v6a3 3 0 0 1-3 3h-7a3 3 0 0 1-3-3v-6H7a4 4 0 0 1-4-4V32.4a5 5 0 0 1 3.7-4.7l4.4-1.6 4.5-12.1c.9-2.4 3.2-4 5.8-4H28V4a2 2 0 0 1 2-2Zm-8.3 14a2 2 0 0 0-1.9 1.3l-3.3 9h47l-3.3-9a2 2 0 0 0-1.9-1.3H21.7ZM14 35a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm52 0a5 5 0 1 0 0 10 5 5 0 0 0 0-10ZM27 41a2 2 0 0 0 0 4h26a2 2 0 0 0 0-4H27Z"/></svg>
                <span><span class="dash-banner__name">{{ $brandTitle }}@if ($lastWord) <span>{{ $lastWord }}</span>@endif</span><span class="dash-banner__sub">Admin CMS</span></span>
            </div>
            <h2 id="dash-banner-title" class="dash-banner__title">Manage Every Ride in <span>One Place</span></h2>
            <p class="dash-banner__lead">Control bookings, drivers, routes, fares and more with a powerful and easy to use admin panel.</p>
            <ul class="dash-banner__features">
                @foreach ([['calendar', 'Bookings'], ['users', 'Drivers'], ['route', 'Routes'], ['bar-chart', 'Reports']] as [$icon, $label])
                    <li><span class="dash-banner__icon" aria-hidden="true"><x-icon :name="$icon" :size="20" :stroke="1.9" /></span> {{ $label }}</li>
                @endforeach
            </ul>
        </div>
        <div class="dash-banner__scene" aria-hidden="true">
            <img src="{{ asset('images/demo/admin-login-scene.webp') }}" alt="" width="930" height="561" decoding="async">
        </div>
    </section>

    {{-- KPI cards --}}
    <div class="kpi-grid">
        @foreach ($kpis as $kpi)
            <a class="kpi kpi--{{ $kpi['tone'] }}" href="{{ $kpi['href'] }}">
                <span class="kpi__icon" aria-hidden="true"><x-icon :name="$kpi['icon']" :size="22" /></span>
                <span class="kpi__body">
                    <span class="kpi__label">{{ $kpi['label'] }}</span>
                    <span class="kpi__value">{{ number_format($kpi['total']) }}</span>
                    <span @class(['kpi__trend', 'is-up' => $kpi['week'] > 0])>
                        @if ($kpi['week'] > 0)<x-icon name="trending-up" :size="14" /> +{{ number_format($kpi['week']) }} this week @else No new this week @endif
                    </span>
                </span>
                <svg class="kpi__spark" viewBox="0 0 100 32" preserveAspectRatio="none" aria-hidden="true" focusable="false"><polyline points="{{ $spark($kpi['spark']) }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/></svg>
            </a>
        @endforeach
    </div>

    {{-- Analytics: booking overview, revenue growth (estimated fares) and booking status --}}
    <div class="dash-row" style="--chart-blue: {{ $chart['blue'] }}; --chart-orange: {{ $chart['orange'] }}; --chart-red: {{ $chart['red'] }}; --chart-green: {{ $chart['green'] }}; --chart-empty: {{ $chart['empty'] }};">
        <section class="acard dash-card dash-card--overview" aria-labelledby="overview-title">
            <div class="dash-card__head">
                <h2 class="acard__title" id="overview-title"><span class="dash-card__icon"><x-icon name="bar-chart" :size="18" /></span> Booking Overview</h2>
                <label class="visually-hidden" for="overview-range">Chart period</label>
                <select id="overview-range" class="dash-select" data-chart-range hidden>
                    @foreach ($ranges as $key => [$label])<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </div>
            @foreach ($ranges as $key => [$label, $labels, $titles, $values])
                @php($c = $lineChart($values))
                <div class="dash-chart" data-chart="{{ $key }}" @if (! $loop->first) hidden @endif>
                    <p class="dash-figure"><strong>{{ number_format(array_sum($values)) }}</strong> <span>{{ \Illuminate\Support\Str::plural('booking', array_sum($values)) }} · {{ strtolower($label) }}</span></p>
                    @if (array_sum($values) === 0)<p class="dash-hint">No bookings in this period yet</p>@endif
                    <div class="dash-line">
                        <div class="dash-line__plot">
                            @foreach ($c['grid'] as [$y, $tick])
                                <span class="dash-line__tick" style="top: {{ $y }}%" aria-hidden="true">{{ $tick }}</span>
                            @endforeach
                            <svg class="dash-line__svg" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                                <defs><linearGradient id="dash-area-{{ $key }}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="{{ $chart['blue'] }}" stop-opacity=".2"/><stop offset="1" stop-color="{{ $chart['blue'] }}" stop-opacity="0"/></linearGradient></defs>
                                @foreach ($c['grid'] as [$y])
                                    <line x1="0" x2="100" y1="{{ $y }}" y2="{{ $y }}" class="dash-line__grid" stroke="{{ $chart['grid'] }}" stroke-width="1" vector-effect="non-scaling-stroke"/>
                                @endforeach
                                <path d="{{ $c['area'] }}" fill="url(#dash-area-{{ $key }})"/>
                                <path d="{{ $c['line'] }}" class="dash-line__path" fill="none" stroke="{{ $chart['blue'] }}" stroke-width="3" vector-effect="non-scaling-stroke"/>
                            </svg>
                            <ol class="dash-line__points">
                                @foreach ($c['points'] as $i => [$x, $y, $v])
                                    <li style="left: {{ $x }}%; top: {{ $y }}%"><span class="dash-line__value">{{ number_format($v) }}</span><span class="visually-hidden"> {{ \Illuminate\Support\Str::plural('booking', $v) }} on {{ $titles[$i] }}</span></li>
                                @endforeach
                            </ol>
                        </div>
                        <div class="dash-line__labels" aria-hidden="true">
                            @foreach ($c['points'] as $i => [$x])<span style="left: {{ $x }}%">{{ $labels[$i] }}</span>@endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </section>

        <section class="acard dash-card" aria-labelledby="revenue-title">
            <div class="dash-card__head">
                <h2 class="acard__title" id="revenue-title"><span class="dash-card__icon dash-card__icon--green"><x-icon name="trending-up" :size="18" /></span> Revenue Growth</h2>
                <a href="{{ route('admin.bookings.index') }}" class="dash-card__link">Show all</a>
            </div>
            <p class="dash-figure"><strong @class(['is-up' => $growth > 0, 'is-down' => $growth !== null && $growth < 0])>{{ $growthLabel }}</strong> <span>From last quarter</span></p>
            <p class="dash-note">Estimated fares: {{ \App\Support\Money::format($quarter, true) }} in the last 3 months</p>
            <div class="dash-bars-wrap">
            <ol class="dash-bars dash-bars--revenue">
                @foreach ($months as $month)
                    <li title="{{ $month['title'] }}: {{ \App\Support\Money::format($month['paise'], true) }} estimated">
                        <span class="dash-bars__track" aria-hidden="true">
                            <span class="dash-bars__fill" style="height: {{ $month['paise'] ? max(4, round($month['paise'] / $revenueMax * 100)) : 0 }}%"></span>
                            @if ($loop->last && $month['paise'])<span class="dash-bars__tag">{{ $compact($month['paise']) }}</span>@endif
                        </span>
                        <span class="dash-bars__label"><span aria-hidden="true">{{ $month['label'] }}</span><span class="visually-hidden">{{ $month['title'] }}: {{ \App\Support\Money::format($month['paise'], true) }} estimated</span></span>
                    </li>
                @endforeach
            </ol>
            </div>
        </section>

        <section class="acard dash-card" aria-labelledby="status-title">
            <div class="dash-card__head">
                <h2 class="acard__title" id="status-title"><span class="dash-card__icon"><x-icon name="sliders" :size="18" /></span> Booking Status</h2>
                <a href="{{ route('admin.bookings.index') }}" class="dash-card__link">View all</a>
            </div>
            <div class="donut">
                <svg class="donut__chart" viewBox="0 0 140 140" aria-hidden="true" focusable="false">
                    <circle cx="70" cy="70" r="54" class="donut__track" fill="none" stroke="{{ $chart['empty'] }}" stroke-width="18"/>
                    @php($offset = 0)
                    @foreach ($donut as [$label, $count, $tone])
                        @if ($count > 0)
                            @php($length = $count / max(1, $donutTotal) * $ring)
                            <circle cx="70" cy="70" r="54" class="donut__part" fill="none" stroke="{{ $chart[$tone] }}" stroke-width="18" stroke-dasharray="{{ round($length, 2) }} {{ round($ring - $length, 2) }}" stroke-dashoffset="{{ round(-$offset, 2) }}"/>
                            @php($offset += $length)
                        @endif
                    @endforeach
                </svg>
                <p class="donut__total"><strong>{{ number_format($donutTotal) }}</strong> <span>Total {{ \Illuminate\Support\Str::plural('booking', $donutTotal) }}</span></p>
            </div>
            <ul class="donut__legend">
                @foreach ($donut as [$label, $count, $tone, $status, $note])
                    <li>
                        <a href="{{ route('admin.bookings.index', ['status' => $status]) }}" @if ($note) title="{{ $note }}" @endif>
                            <span class="donut__swatch" style="background: var(--chart-{{ $tone }})" aria-hidden="true"></span>
                            <span class="donut__name">{{ $label }}@if ($note)<span class="visually-hidden"> ({{ strtolower($note) }})</span>@endif</span>
                            <strong>{{ number_format($count) }}</strong>
                            <span class="donut__pct">{{ $donutTotal ? round($count / $donutTotal * 100) : 0 }}%</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    {{-- Recent bookings --}}
    <section class="acard recent" aria-labelledby="recent-title">
        <div class="dash-card__head">
            <h2 class="acard__title" id="recent-title">Recent Bookings</h2>
            <a href="{{ route('admin.bookings.index') }}" class="abtn abtn--sm abtn--outline">View All Bookings</a>
        </div>
        <div class="atable-wrap">
            <table class="atable recent__table">
                <thead><tr><th>Booking ID</th><th>Customer</th><th>Route</th><th>Date &amp; Time</th><th>Status</th><th>Fare</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($recentBookings as $booking)
                        @php($customer = $booking->customer_name ?: $booking->trip_type->label())
                        <tr>
                            <td><a class="ref" href="{{ route('admin.bookings.show', $booking) }}">{{ $booking->reference }}</a></td>
                            <td>
                                <span class="recent__customer">
                                    <span class="recent__avatar" aria-hidden="true">{{ \Illuminate\Support\Str::upper(mb_substr($customer, 0, 1)) }}</span>
                                    <span>{{ $customer }}<small class="muted">{{ $booking->customer_phone }}</small></span>
                                </span>
                            </td>
                            <td>
                                <span class="recent__route"><x-icon name="map-pin" :size="14" class="recent__pin" /> {{ $booking->pickupLabel() }} <span class="recent__arrow" aria-label="to">→</span> <x-icon name="map-pin" :size="14" class="recent__pin recent__pin--end" /> {{ $booking->destinationLabel() }}</span>
                            </td>
                            <td class="recent__date">{{ $booking->travel_date->format('d M Y') }}<small class="muted">{{ substr((string) $booking->travel_time, 0, 5) }}</small></td>
                            <td><x-admin.status :status="$booking->status" /></td>
                            <td class="recent__fare">{{ $booking->estimated_fare !== null ? \App\Support\Money::format($booking->estimated_fare) : '—' }}</td>
                            <td class="actions">
                                <span class="recent__actions">
                                    <a class="abtn abtn--sm" href="{{ route('admin.bookings.show', $booking) }}"><x-icon name="eye" :size="14" /> <span class="recent__view-text">View</span></a>
                                    <details class="row-menu">
                                        <summary class="abtn abtn--sm abtn--icon" aria-label="More actions for {{ $booking->reference }}"><x-icon name="more" :size="16" /></summary>
                                        <div class="row-menu__panel">
                                            <a href="{{ route('admin.bookings.show', $booking) }}"><x-icon name="edit" :size="14" /> Open &amp; update</a>
                                            @if ($booking->customer_phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $booking->customer_phone) }}"><x-icon name="phone" :size="14" /> Call customer</a>@endif
                                            @if ($booking->customer_email)<a href="mailto:{{ $booking->customer_email }}"><x-icon name="mail" :size="14" /> Email customer</a>@endif
                                        </div>
                                    </details>
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">No booking requests yet. They will appear here as soon as customers submit the booking form.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($recentBookings->isNotEmpty())<p class="recent__note">Fares are the estimates shown to customers when they booked.</p>@endif
    </section>
@endsection

@push('scripts')
    <script>
        (function () {
            // Booking overview: switch between the 7-day and 6-month charts (both are rendered; the 7-day one shows without JavaScript).
            var select = document.querySelector('[data-chart-range]');
            if (!select) return;
            select.hidden = false;
            select.addEventListener('change', function () {
                document.querySelectorAll('[data-chart]').forEach(function (chart) { chart.hidden = chart.getAttribute('data-chart') !== select.value; });
            });
        })();
        // Close an open row menu when clicking elsewhere.
        document.addEventListener('click', function (e) {
            document.querySelectorAll('details.row-menu[open]').forEach(function (menu) { if (!menu.contains(e.target)) menu.removeAttribute('open'); });
        });
    </script>
@endpush
