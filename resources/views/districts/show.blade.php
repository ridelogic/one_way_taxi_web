@extends('layouts.app')

@section('content')
    @php
        $name = $district->name;
        $pickups = $district->pickupLocations();
        $phone = $settings->get('phone');
    @endphp

    @if ($preview)
        <div class="sticky top-0 z-[60] bg-gold px-4 py-2.5 text-center text-sm font-bold text-white">Preview — this district page is unpublished and hidden from visitors and search engines.</div>
    @endif

    {{-- Scrolling offers ticker below the header (Admin → Promotional Announcements): only enabled, current
         announcements targeting this page; renders nothing when there are none. Same component as the homepage. --}}
    <x-offer-ticker :announcements="$announcements ?? collect()" />

    {{-- B · Hero: local transport photograph, charcoal overlay, district heading --}}
    <section class="district-hero" aria-labelledby="district-title">
        <div class="district-hero__stage">
            <x-img :media="$district->heroMedia" fallback="images/demo/routes-sedan-highway-sunset.jpg" :alt="$district->imageAlt()" :priority="true" :lazy="false" class="district-hero__img" />
            <span class="district-hero__veil" aria-hidden="true"></span>
            <div class="container-site district-hero__content">
                <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-1.5 text-sm text-white/70">
                    <a href="{{ route('home') }}" class="hover:text-sun">Home</a>
                    <x-icon name="chevron-right" :size="14" class="text-white/40" />
                    @if ($district->isTamilNadu())
                        <a href="{{ route('districts.index') }}" class="hover:text-sun">Tamil Nadu</a>
                        <x-icon name="chevron-right" :size="14" class="text-white/40" />
                    @endif
                    <span aria-current="page" class="font-semibold text-sun">{{ $name }}</span>
                </nav>
                <span class="home-hero__eyebrow mt-6">{{ $district->region ?: $district->state }}</span>
                <h1 id="district-title" class="district-hero__title">{{ $district->displayHeading() }}</h1>
                @if ($district->intro)
                    <p class="home-hero__subtitle">{{ $district->intro }}</p>
                @endif
                <ul class="district-hero__facts">
                    @if ($district->headquarters)
                        <li><x-icon name="building" :size="16" class="text-sun" /> HQ: {{ $district->headquarters }}</li>
                    @endif
                    @if ($district->nearest_airport)
                        <li><x-icon name="plane" :size="16" class="text-sun" /> {{ $district->nearest_airport }}</li>
                    @endif
                    <li><x-icon name="route" :size="16" class="text-sun" /> {{ $routes->isNotEmpty() ? $routes->count().' popular '.\Illuminate\Support\Str::plural('route', $routes->count()).' with fares' : 'Fares on request' }}</li>
                </ul>
            </div>
        </div>

        {{-- C · Booking widget: the existing search (GET /book), pickup prefilled with this district --}}
        <x-booking-panel :cities="$cities" :title="'Book a taxi in '.$name" :prefill="['pickup_location' => $district->pickupName()]" :local="true" />
    </section>

    {{-- E · Taxi services in the district: the four trip types of the booking system, each opening the booking page prefilled --}}
    @php
        $pickupName = $district->pickupName();
        // Default descriptions; editors can replace each one per district (Admin → District Management → Service descriptions).
        $tripCards = [
            ['key' => 'one_way', 'icon' => 'route', 'title' => 'One Way Taxi', 'text' => 'One-way outstation drops from '.$name.' to any city — you pay for the distance travelled, not the return.', 'params' => ['trip_type' => 'one_way'], 'cta' => 'Book one way'],
            ['key' => 'round_trip', 'icon' => 'swap', 'title' => 'Round Trip Taxi', 'text' => 'Outstation journeys that bring you back to '.$name.', with the driver staying for the length of your trip.', 'params' => ['trip_type' => 'round_trip'], 'cta' => 'Book a round trip'],
            ['key' => 'local', 'icon' => 'map-pin', 'title' => 'Local Taxi', 'text' => 'Point-to-point rides within '.$name.' — home, office, station, bus stand or hospital.', 'params' => ['trip_type' => 'local'], 'cta' => 'Book a local trip'],
            ['key' => 'rental', 'icon' => 'clock', 'title' => 'Rental Taxi', 'text' => 'A car with driver by the hour or package for errands, meetings and sightseeing around '.$name.'.', 'params' => ['trip_type' => 'rental'], 'cta' => 'Book a rental'],
        ];
        // Services switched off for this district (Admin → District Management → Taxi Services) are not shown.
        $tripCards = array_values(array_filter($tripCards, fn ($card) => $district->serviceVisible($card['key'])));
    @endphp
    <section class="home-section bg-ivory" aria-labelledby="services-title">
        <div class="container-site">
            <div class="section-head">
                <div>
                    <span class="kicker">Our services</span>
                    <h2 id="services-title" class="display-2">Taxi Services in {{ $name }}</h2>
                    <p class="lead">Choose your trip type — every booking shows a fare estimate first and is confirmed by our team before your driver arrives.</p>
                </div>
                <a href="{{ route('services.index') }}" class="text-link shrink-0">All services <x-icon name="arrow-right" :size="18" /></a>
            </div>
            <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($tripCards as $card)
                    <li class="trust-card">
                        <span class="trust-card__icon" aria-hidden="true"><x-icon :name="$card['icon']" :size="22" /></span>
                        <h3 class="mt-5 text-lg font-bold">{{ $card['title'] }} <span class="sr-only">in {{ $name }}</span></h3>
                        <p class="district-card__text mt-2">{{ $district->serviceText($card['key']) ?? $card['text'] }}</p>
                        <a href="{{ route('booking.create', $card['params'] + ['pickup_location' => $pickupName]) }}" class="district-card__link">{{ $card['cta'] }} <x-icon name="arrow-right" :size="16" /></a>
                    </li>
                @endforeach
            </ul>
            @if ($district->nearest_airport && $district->serviceVisible('airport'))
                {{-- Airport transfers are a service booked as One Way trips (not a separate trip type) --}}
                <div class="mt-6 flex flex-col gap-4 rounded-xl border border-line bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="flex items-start gap-3 text-[0.95rem] text-ink"><span class="grid size-10 shrink-0 place-items-center rounded-lg bg-gold-100 text-gold" aria-hidden="true"><x-icon name="plane" :size="18" /></span><span><strong class="font-display text-forest">Airport transfers</strong><span class="block text-ink-soft">{{ $district->serviceText('airport') ?? 'Nearest airport: '.$district->nearest_airport.'. Book as a One Way trip with the airport as your drop.' }}</span></span></p>
                    <a href="{{ route('services.show', 'airport-transfers') }}" class="text-link shrink-0">Airport transfers <x-icon name="arrow-right" :size="16" /></a>
                </div>
            @endif
        </div>
    </section>

    {{-- District video stories (Admin → Video Promotions, assigned to this district) --}}
    <x-video-gallery :videos="$videos" :title="'Taxi Experiences in '.$name" subtitle="Videos from our drivers, vehicles and passengers travelling in and around {{ $name }}." eyebrow="Video stories" heading-id="videos-title" class="bg-white" />

    {{-- District photo gallery (Admin → District Management → Photos) --}}
    <x-photo-gallery :photos="$photos" :title="$name.' Photo Gallery'" eyebrow="Gallery" heading-id="district-gallery-title" class="bg-ivory" />

    {{-- Cars offered in this district (Admin → District Management → Cars); rates come from Vehicle Master → Fares --}}
    @if ($cars->isNotEmpty())
        <section class="home-section bg-white" aria-labelledby="cars-title">
            <div class="container-site">
                <x-home-heading eyebrow="Our cars" :title="'Cars Available in '.$name" subtitle="Choose a car and book — you’ll see the fare estimate before you confirm, and our team confirms every trip by phone." id="cars-title" />
                <ul class="car-grid">
                    @foreach ($cars as $car)
                        <li class="car-card">
                            <span class="car-card__media">
                                <x-img :media="$car->image()" fallback="images/demo/routes-sedan-highway-sunset.jpg" :alt="$car->displayName().' taxi in '.$name" class="size-full" sizes="(min-width: 1280px) 320px, (min-width: 640px) 50vw, 100vw" />
                            </span>
                            <div class="car-card__body">
                                <h3 class="car-card__name">{{ $car->displayName() }}</h3>
                                <p class="car-card__facts">
                                    @if ($car->name && $car->vehicle && $car->name !== $car->vehicle->name)<span>{{ $car->vehicle->name }}</span>@endif
                                    @if ($car->seatCount())<span>{{ $car->seatCount() }} seats</span>@endif
                                    <span>{{ $car->is_ac ? 'AC' : 'Non-AC' }}</span>
                                </p>
                                @if ($car->description)<p class="district-card__text">{{ $car->description }}</p>@endif
                                @if ($car->vehicle?->fareRule?->is_active && (float) $car->vehicle->fareRule->per_km_rate > 0)
                                    <p class="car-card__rate">₹{{ rtrim(rtrim($car->vehicle->fareRule->per_km_rate, '0'), '.') }}/km</p>
                                @endif
                                <a href="{{ route('booking.create', ['pickup_location' => $district->pickupName(), 'vehicle_category_id' => $car->vehicle_category_id]) }}" class="district-card__link mt-auto">Book {{ $car->displayName() }} <x-icon name="arrow-right" :size="16" /></a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- G · Popular pickup and drop locations --}}
    @php($drops = $district->dropLocations())
    @if ($pickups || $drops)
        @php($placeIcon = fn (string $place) => \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($place), 'airport') ? 'plane' : (\Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($place), ['junction', 'railway', 'station', 'central', 'egmore']) ? 'train' : 'map-pin'))
        <section class="home-section bg-ivory" aria-labelledby="pickups-title">
            <div class="container-site">
                <x-home-heading eyebrow="Pickup & drop points" :title="'Popular Pickup and Drop Locations in '.$name" subtitle="Common pickup and drop points across the district. Your exact address is confirmed by our team when you book." id="pickups-title" />
                <div @class(['grid gap-10', 'lg:grid-cols-2 lg:gap-14' => $pickups && $drops])>
                    @if ($pickups)
                        <div>
                            <h3 class="mb-4 font-display text-xl font-extrabold text-[#111827]">Pickup locations</h3>
                            <ul class="place-list">
                                @foreach ($pickups as $place)
                                    <li class="pickup-item"><span class="grid size-10 shrink-0 place-items-center rounded-lg bg-gold-100 text-gold" aria-hidden="true"><x-icon :name="$placeIcon($place)" :size="18" /></span><span class="font-semibold text-forest">{{ $place }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if ($drops)
                        <div>
                            <h3 class="mb-4 font-display text-xl font-extrabold text-[#111827]">Drop locations</h3>
                            <ul class="place-list">
                                @foreach ($drops as $place)
                                    <li><a href="{{ route('booking.create', ['trip_type' => 'one_way', 'pickup_location' => $district->pickupName(), 'destination_location' => $place]) }}" class="pickup-item"><span class="grid size-10 shrink-0 place-items-center rounded-lg bg-gold-100 text-gold" aria-hidden="true"><x-icon :name="$placeIcon($place)" :size="18" /></span><span class="font-semibold text-forest">{{ $place }}<span class="sr-only"> — book a taxi</span></span></a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- Popular destinations from the district (booking links only — no fares are claimed) --}}
    @if ($destinations = $district->popularDestinations())
        <section class="home-section bg-white" aria-labelledby="destinations-title">
            <div class="container-site">
                <div class="section-head">
                    <div>
                        <span class="kicker">Popular destinations</span>
                        <h2 id="destinations-title" class="display-2">Popular Trips from {{ $name }}</h2>
                        <p class="lead">Places travellers often visit from {{ $name }}. Choose one to open the booking page with your trip filled in — you’ll see the fare estimate before you confirm.</p>
                    </div>
                </div>
                <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($destinations as $place)
                        <li>
                            <a href="{{ route('booking.create', ['trip_type' => 'one_way', 'pickup_location' => $pickupName, 'destination_location' => $place]) }}" class="dest-card">
                                <span class="flex items-center gap-3">
                                    <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-gold-100 text-gold" aria-hidden="true"><x-icon name="flag" :size="18" /></span>
                                    <span class="leading-tight">
                                        <span class="block font-display text-base font-bold text-forest">{{ $place }}</span>
                                        <span class="text-sm text-ink-soft">From {{ $pickupName }}</span>
                                    </span>
                                </span>
                                <span class="shrink-0 text-sm font-bold text-gold-600">Book <span class="sr-only">{{ $pickupName }} to {{ $place }}</span><x-icon name="arrow-right" :size="16" class="inline" /></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- Popular taxi routes chosen by the editor (booking links; fares are only ever shown from the live route table below) --}}
    @if ($curated = $district->curatedRoutes())
        <section class="home-section bg-ivory" aria-labelledby="curated-routes-title">
            <div class="container-site">
                <x-home-heading eyebrow="Popular routes" :title="'Popular Taxi Routes from '.$name" subtitle="Routes travellers often book. Choose one to open the booking page with your trip filled in — the fare estimate is shown before you confirm." id="curated-routes-title" />
                <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($curated as $r)
                        <li>
                            <a href="{{ route('booking.create', ['trip_type' => 'one_way', 'pickup_location' => $r['from'], 'destination_location' => $r['to']]) }}" class="route-chip">
                                <span class="min-w-0">
                                    <span class="block">{{ $r['from'] }} <span class="text-gold" aria-hidden="true">→</span><span class="sr-only">to</span> {{ $r['to'] }}</span>
                                    @if ($r['description'])<span class="mt-1 block text-sm font-medium text-[#475569]">{{ $r['description'] }}</span>@endif
                                    @if ($fare = $routeFares[$r['route']->id] ?? null)<span class="mt-1 block text-sm text-[#B00000]">From {{ $fare }}</span>@endif
                                </span>
                                <span class="shrink-0 text-sm text-gold-600">Book <x-icon name="arrow-right" :size="16" class="inline" /></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- F · Routes & fares (live route data; nothing is typed into this page) --}}
    <section class="home-section bg-white" id="fares" aria-labelledby="fares-title">
        <div class="container-site">
            <div class="section-head">
                <div>
                    <span class="kicker">Routes &amp; fares</span>
                    <h2 id="fares-title" class="display-2">{{ $name }} Taxi Fares</h2>
                    <p class="lead">
                        @if ($routes->isNotEmpty())
                            Estimated one-way fares by vehicle type for popular routes{{ $district->cities->isNotEmpty() ? ' from '.$district->cities->pluck('name')->join(', ', ' and ') : '' }}. Tolls, permits and parking are extra where applicable.
                        @else
                            Fares for trips from {{ $name }} depend on your exact pickup, destination and vehicle. Share your trip and our team will send you a fare before you book.
                        @endif
                    </p>
                </div>
                @if ($routes->isNotEmpty() && ($firstCity = $district->cities->first()))
                    <a href="{{ route('routes.index', ['from' => $firstCity->id]) }}" class="btn btn-outline shrink-0">All routes from {{ $firstCity->name }} <x-icon name="arrow-right" :size="18" /></a>
                @endif
            </div>

            @if ($routes->isNotEmpty())
                {{-- Desktop / tablet: fare grid --}}
                <div class="fare-board hidden md:block">
                    <div class="overflow-x-auto">
                        <table class="fare-grid">
                            <caption class="sr-only">Estimated fares by route and vehicle type for {{ $name }}</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Route</th>
                                    @foreach ($vehicles as $vehicle)
                                        <th scope="col" class="text-right">{{ $vehicle->name }}<span class="block text-[0.68rem] font-medium tracking-normal text-white/55 normal-case">{{ $vehicle->passenger_capacity }} seats</span></th>
                                    @endforeach
                                    <th scope="col"><span class="sr-only">Book</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($routes as $route)
                                    <tr>
                                        <th scope="row">
                                            <a href="{{ route('routes.show', $route->slug) }}" class="font-display font-extrabold text-forest hover:underline">{{ $route->origin->name }} <span class="text-gold" aria-hidden="true">→</span><span class="sr-only">to</span> {{ $route->destination->name }}</a>
                                            <span class="mt-1 block text-xs font-medium text-ink-soft">{{ $route->distanceLabel() }} · {{ $route->durationLabel() }}@if ($route->is_sample_fare && settings()->bool('demo_mode')) · <span class="badge-sample">Sample</span>@endif</span>
                                        </th>
                                        @foreach ($vehicles as $vehicle)
                                            @php($e = $fares[$route->id][$vehicle->id])
                                            <td class="text-right">
                                                @if ($e['available'])
                                                    <a href="{{ route('booking.create', ['pickup_location' => $route->origin->name, 'destination_location' => $route->destination->name, 'vehicle_category_id' => $vehicle->id]) }}" class="font-display font-bold text-forest hover:text-gold-600 hover:underline">{{ $e['total_formatted'] }}<span class="sr-only"> — book {{ $vehicle->name }}</span></a>
                                                @else
                                                    <span class="text-xs text-ink-soft">On request</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="text-right"><a href="{{ route('booking.create', ['pickup_location' => $route->origin->name, 'destination_location' => $route->destination->name]) }}" class="btn btn-gold btn-sm">Book<span class="sr-only"> {{ $route->name() }}</span></a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile: one card per route --}}
                <div class="grid gap-4 md:hidden">
                    @foreach ($routes as $route)
                        <article class="fare-board">
                            <div class="flex items-start justify-between gap-3 border-b border-line bg-ivory px-5 py-4">
                                <div>
                                    <h3 class="font-display text-lg font-extrabold"><a href="{{ route('routes.show', $route->slug) }}">{{ $route->origin->name }} <span class="text-gold" aria-hidden="true">→</span><span class="sr-only">to</span> {{ $route->destination->name }}</a></h3>
                                    <p class="text-xs text-ink-soft">{{ $route->distanceLabel() }} · {{ $route->durationLabel() }}</p>
                                </div>
                                <x-sample-badge :show="$route->is_sample_fare" />
                            </div>
                            <dl class="divide-y divide-line">
                                @foreach ($vehicles as $vehicle)
                                    @php($e = $fares[$route->id][$vehicle->id])
                                    <div class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                                        <dt class="font-semibold text-ink">{{ $vehicle->name }} <span class="font-normal text-ink-soft">· {{ $vehicle->passenger_capacity }} seats</span></dt>
                                        <dd class="font-display font-bold text-forest">{{ $e['available'] ? $e['total_formatted'] : 'On request' }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                            <div class="px-5 py-4"><a href="{{ route('booking.create', ['pickup_location' => $route->origin->name, 'destination_location' => $route->destination->name]) }}" class="btn btn-gold btn-sm w-full">Book this route</a></div>
                        </article>
                    @endforeach
                </div>
                <p class="mt-4 text-sm text-ink-soft">{{ settings('fare_disclaimer') }}</p>
            @else
                <div class="fare-request">
                    <span class="grid size-14 shrink-0 place-items-center rounded-xl bg-gold text-white" aria-hidden="true"><x-icon name="rupee" :size="26" /></span>
                    <div class="flex-1">
                        <h3 class="text-xl font-extrabold">Get a fare for your {{ $name }} trip</h3>
                        <p class="mt-1.5 text-ink-soft">We don’t publish fixed fares for {{ $name }} routes yet. Tell us your pickup and drop — our team confirms the fare by phone before anything is booked.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('booking.create', ['pickup_location' => $district->pickupName()]) }}" class="btn btn-gold">Request a fare <x-icon name="arrow-right" :size="18" /></a>
                        @if ($phone)
                            <a href="{{ $settings->telLink() }}" class="btn btn-outline"><x-icon name="phone" :size="18" /> {{ $phone }}</a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- D · Why choose One Way Taxi --}}
    @if ($why && $why->items())
        <section class="home-section bg-ivory" aria-labelledby="why-title">
            <div class="container-site">
                <x-home-heading eyebrow="Why travellers choose us" :title="'Why Choose One Way Taxi in '.$name.'?'" :subtitle="$why->subtitle" id="why-title" />
                <ul class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($why->items() as $item)
                        <li class="trust-card">
                            <span class="trust-card__icon" aria-hidden="true"><x-icon :name="$item['icon'] ?? 'check'" :size="22" /></span>
                            <h3 class="mt-5 text-[1.05rem] font-bold">{{ $item['title'] ?? '' }}</h3>
                            <p class="mt-2 text-[0.93rem] leading-relaxed text-ink-soft">{{ $item['text'] ?? '' }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- District FAQs (also published as FAQPage structured data — only the questions visible here) --}}
    @if ($faqs = $district->faqItems())
        <section class="home-section bg-white" aria-labelledby="district-faq-title">
            <div class="container-site grid items-start gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:gap-16">
                <x-home-heading eyebrow="FAQs" :title="'Taxi Questions About '.$name" subtitle="Answers to common questions about booking a taxi in the district." id="district-faq-title" class="text-left! lg:sticky lg:top-28 [&_.home-heading__rule]:mx-0! [&_.home-heading__lead]:mx-0!" />
                <div class="faq-list">
                    @foreach ($faqs as $faq)
                        <details @if ($loop->first) open @endif>
                            <summary>{{ $faq['question'] }} <span class="faq-list__icon" aria-hidden="true"><x-icon name="plus" :size="16" /></span></summary>
                            <p class="faq-list__answer">{{ $faq['answer'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- H · District travel guide (unique content per district) --}}
    @if ($district->content)
        <section class="home-section bg-ivory" aria-label="Travelling in {{ $name }}">
            <div class="container-site grid items-start gap-12 lg:grid-cols-[1.25fr_0.75fr] lg:gap-16">
                <article class="prose-site">{!! \App\Support\HtmlSanitizer::clean($district->content) !!}</article>
                <aside class="contact-panel lg:sticky lg:top-28">
                    <span class="kicker kicker-light">Book in {{ $name }}</span>
                    <h2 class="mt-3 text-2xl font-extrabold text-white">Plan your trip with us</h2>
                    <ul class="mt-6">
                        <li class="contact-item"><span class="contact-item__icon" aria-hidden="true"><x-icon name="route" :size="20" /></span><span class="text-sm text-white/85">One-way fares — pay only for the distance you travel</span></li>
                        <li class="contact-item"><span class="contact-item__icon" aria-hidden="true"><x-icon name="check-circle" :size="20" /></span><span class="text-sm text-white/85">Fare estimate before you book, confirmed by phone</span></li>
                        @if ($phone)
                            <li class="contact-item"><span class="contact-item__icon" aria-hidden="true"><x-icon name="phone" :size="20" /></span><span><small class="block text-xs font-semibold tracking-wide text-white/55 uppercase">Call 24/7</small><a href="{{ $settings->telLink() }}" class="font-display text-lg font-bold text-white hover:underline">{{ $phone }}</a></span></li>
                        @endif
                    </ul>
                    <a href="#book-ride" class="btn btn-yellow mt-6">Book a taxi in {{ $name }} <x-icon name="arrow-right" :size="18" /></a>
                </aside>
            </div>
        </section>
    @endif

    {{-- I · Related districts --}}
    <section class="home-section bg-white" aria-labelledby="related-title">
        <div class="container-site">
            <div class="section-head">
                <div>
                    <span class="kicker">Nearby districts</span>
                    <h2 id="related-title" class="display-2">Taxi Services Near {{ $name }}</h2>
                </div>
                <a href="{{ route('districts.index') }}" class="btn btn-outline shrink-0">All Tamil Nadu districts <x-icon name="arrow-right" :size="18" /></a>
            </div>
            @if ($related->isNotEmpty())
                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($related as $page)
                        <li>
                            <a href="{{ $page->url() }}" class="dest-card">
                                <span class="flex items-center gap-3">
                                    <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-gold-100 text-gold" aria-hidden="true"><x-icon name="map-pin" :size="20" /></span>
                                    <span class="leading-tight">
                                        <span class="block font-display text-base font-bold text-forest">Taxi in {{ $page->name }}</span>
                                        <span class="text-sm text-ink-soft">{{ $page->region ?: ($page->state ?: 'Tamil Nadu') }}</span>
                                    </span>
                                </span>
                                <x-icon name="arrow-right" :size="18" class="shrink-0 text-gold" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- Internal links: district → service pages → booking --}}
            <nav class="mt-10 border-t border-line pt-8" aria-label="Taxi services">
                <h3 class="font-display text-lg font-extrabold text-[#111827]">Taxi services available from {{ $name }}</h3>
                <ul class="mt-4 flex flex-wrap gap-3">
                    @foreach ([['One way taxi', 'one-way-taxi'], ['Round trip taxi', 'outstation-taxi'], ['Local taxi', 'local-taxi'], ['Rental taxi with driver', 'rentals'], ['Airport transfers', 'airport-transfers']] as [$label, $slug])
                        <li><a href="{{ route('services.show', $slug) }}" class="inline-flex items-center gap-2 rounded-full border border-line bg-white px-4 py-2 text-sm font-semibold text-[#111827] hover:border-[#D71920]/40">{{ $label }} <x-icon name="arrow-right" :size="14" class="text-gold" /></a></li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </section>

    {{-- Structured data: breadcrumb trail, and FAQPage for the FAQs visible on this page --}}
    @push('head')
        @foreach ($schemas as $schema)
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
        @endforeach
    @endpush
@endsection
