@if ($citiesByState->isNotEmpty())
@php
    // Coverage figures are counted from the live city and route data (Admin → Cities / Routes), never typed in.
    $cityCount = $citiesByState->flatten()->count();
    $routeCount = \App\Models\TaxiRoute::active()->count();
    $showcase = $destinations->take((int) ($section->get('limit') ?: 4));
@endphp
<section class="home-section relative overflow-hidden bg-white" id="service-areas" aria-labelledby="areas-title">
    <svg aria-hidden="true" class="pointer-events-none absolute inset-0 h-full w-full text-forest/[0.05]"><defs><pattern id="dots" width="28" height="28" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="2" fill="currentColor"/></pattern></defs><rect width="100%" height="100%" fill="url(#dots)"/></svg>
    <div class="container-site relative">
        <div class="grid items-center gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:gap-14">
            <div class="mx-auto w-full max-w-md">
                <img src="{{ asset('images/demo/india-map-hd.png') }}" alt="Illustrated map of India featuring landmarks and destinations across the country"
                     width="1312" height="1199" loading="lazy" decoding="async" class="h-auto w-full object-contain">

            </div>
            <div>
                <span class="kicker">{{ $section->get('eyebrow') ?: 'Pan India coverage' }}</span>
                <h2 id="areas-title" class="display-2">{{ $section->title }}</h2>
                @if ($section->subtitle)<p class="lead">{{ $section->subtitle }}</p>@endif

                <dl class="mt-6 grid max-w-md grid-cols-3 gap-3">
                    <div class="rounded-xl border border-line bg-white p-4 text-center"><dt class="text-xs font-semibold tracking-wide text-ink-soft uppercase">Cities</dt><dd class="mt-1 font-display text-3xl font-extrabold text-forest">{{ $cityCount }}</dd></div>
                    <div class="rounded-xl border border-line bg-white p-4 text-center"><dt class="text-xs font-semibold tracking-wide text-ink-soft uppercase">States</dt><dd class="mt-1 font-display text-3xl font-extrabold text-forest">{{ $citiesByState->count() }}</dd></div>
                    <div class="rounded-xl border border-line bg-white p-4 text-center"><dt class="text-xs font-semibold tracking-wide text-ink-soft uppercase">Routes</dt><dd class="mt-1 font-display text-3xl font-extrabold text-gold">{{ $routeCount }}</dd></div>
                </dl>

                <div class="mt-6 space-y-4">
                    @foreach ($citiesByState as $state => $stateCities)
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-bold tracking-wide text-forest uppercase"><x-icon name="map-pin" :size="16" class="text-gold" /> {{ $state }}</h3>
                            <ul class="mt-2 flex flex-wrap gap-2">
                                @foreach ($stateCities as $city)
                                    <li><a class="chip" href="{{ route('routes.index', ['from' => $city->id]) }}">{{ $city->name }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
                @if ($section->get('body'))
                    <p class="mt-6 text-ink-soft">{{ $section->get('body') }} <a href="{{ route('booking.create') }}" class="font-semibold text-gold-600 underline underline-offset-2">Send an enquiry</a></p>
                @endif
            </div>
        </div>

        @if ($showcase->isNotEmpty())
            {{-- Popular destinations as text cards: route links without landscape photography --}}
            <ul class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($showcase as $city)
                    <li>
                        <a href="{{ route('routes.index', ['to' => $city->id]) }}" class="dest-card">
                            <span class="flex items-center gap-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-gold-100 text-gold" aria-hidden="true"><x-icon name="map-pin" :size="20" /></span>
                                <span class="leading-tight">
                                    <span class="block font-display text-base font-bold text-forest">{{ $city->name }}</span>
                                    <span class="text-sm text-ink-soft">{{ $city->state }}</span>
                                </span>
                            </span>
                            <x-icon name="arrow-right" :size="18" class="shrink-0 text-gold" />
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
@endif
