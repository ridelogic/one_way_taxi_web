@if ($citiesByState->isNotEmpty())
@php
    // Coverage figures are counted from the live city and route data (Admin → Cities / Routes), never typed in.
    $cityCount = $citiesByState->flatten()->count();
    $routeCount = \App\Models\TaxiRoute::active()->count();
    $showcase = $destinations->take((int) ($section->get('limit') ?: 4));
@endphp
<section class="home-section relative overflow-hidden bg-mint" id="service-areas" aria-labelledby="areas-title">
    <svg aria-hidden="true" class="pointer-events-none absolute inset-0 h-full w-full text-forest/[0.05]"><defs><pattern id="dots" width="28" height="28" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="2" fill="currentColor"/></pattern></defs><rect width="100%" height="100%" fill="url(#dots)"/></svg>
    <div class="container-site relative">
        <div class="grid items-center gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:gap-14">
            <div class="mx-auto w-full max-w-md">
                <img src="{{ asset('images/demo/india-map-hd.png') }}" alt="Illustrated map of India featuring landmarks and destinations across the country"
                     width="1312" height="1199" loading="lazy" decoding="async" class="h-auto w-full object-contain">

            </div>
            <div>
                <span class="home-heading__eyebrow">{{ $section->get('eyebrow') ?: 'Pan India coverage' }}</span>
                <h2 id="areas-title" class="home-heading__title">{{ $section->title }}</h2>
                <span class="mt-4 block h-1 w-14 rounded-full bg-gold" aria-hidden="true"></span>
                @if ($section->subtitle)<p class="mt-5 text-lg text-ink-soft">{{ $section->subtitle }}</p>@endif

                <dl class="mt-6 grid max-w-md grid-cols-3 gap-3">
                    <div class="rounded-2xl bg-white p-4 text-center shadow-soft"><dt class="text-xs font-semibold tracking-wide text-ink-soft uppercase">Cities</dt><dd class="mt-1 font-display text-3xl font-extrabold text-forest">{{ $cityCount }}</dd></div>
                    <div class="rounded-2xl bg-white p-4 text-center shadow-soft"><dt class="text-xs font-semibold tracking-wide text-ink-soft uppercase">States</dt><dd class="mt-1 font-display text-3xl font-extrabold text-forest">{{ $citiesByState->count() }}</dd></div>
                    <div class="rounded-2xl bg-white p-4 text-center shadow-soft"><dt class="text-xs font-semibold tracking-wide text-ink-soft uppercase">Routes</dt><dd class="mt-1 font-display text-3xl font-extrabold text-gold">{{ $routeCount }}</dd></div>
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
            <ul class="mt-12 grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ($showcase as $city)
                    <li>
                        <a href="{{ route('routes.index', ['to' => $city->id]) }}" class="group relative block overflow-hidden rounded-2xl shadow-soft">
                            <x-img :media="$city->media" alt="" class="aspect-[4/3] w-full transition duration-500 group-hover:scale-105" />
                            <span class="absolute inset-0 bg-gradient-to-t from-navy/85 via-navy/10 to-transparent"></span>
                            <span class="absolute right-4 bottom-4 left-4 text-white">
                                <span class="block font-display text-lg font-bold">{{ $city->name }}</span>
                                <span class="flex items-center gap-1 text-sm text-champagne">{{ $city->state }} <x-icon name="arrow-right" :size="14" class="transition group-hover:translate-x-1" /></span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
@endif
