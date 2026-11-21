@extends('layouts.app')

@section('content')
    <x-page-banner :title="$route->origin->name.' to '.$route->destination->name.' Taxi'" :subtitle="$route->short_description" :media="$route->media" fallback="images/demo/routes-sedan-highway-sunset.jpg" :crumbs="['Routes' => route('routes.index'), $route->name() => null]">
        <ul class="mt-6 flex flex-wrap gap-3">
            <li class="flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-forest shadow-soft"><x-icon name="road" :size="16" class="text-fresh" /> {{ $route->distanceLabel() }}</li>
            <li class="flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-forest shadow-soft"><x-icon name="clock" :size="16" class="text-fresh" /> {{ $route->durationLabel() }}</li>
            <li class="flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-forest shadow-soft">From <span class="text-gold-600">{{ inr($route->base_fare) }}</span> <x-sample-badge :show="$route->is_sample_fare" /></li>
        </ul>
    </x-page-banner>

    <section class="section pt-12!">
        <div class="container-site grid items-start gap-10 lg:grid-cols-[1fr_400px]">
            <div>
                <span class="kicker">Fares on this route</span>
                <h2 class="display-2 text-[1.7rem]! sm:text-[2.1rem]!">Fares by vehicle type</h2>
                <div class="fare-board mt-6">
                    <div class="hidden grid-cols-[minmax(0,1fr)_auto_auto] gap-6 bg-forest px-6 py-3 font-display text-xs font-bold tracking-[0.14em] text-white/70 uppercase sm:grid">
                        <span>Vehicle</span><span class="w-32 text-right">Estimated fare</span><span class="w-20"></span>
                    </div>
                    @foreach ($vehicles as $vehicle)
                        @php($e = $estimates[$vehicle->id])
                        <div class="grid grid-cols-[auto_minmax(0,1fr)] items-center gap-x-4 gap-y-3 border-b border-line p-5 last:border-b-0 sm:grid-cols-[auto_minmax(0,1fr)_auto_auto] sm:gap-6 sm:px-6">
                            <span class="grid size-11 shrink-0 place-items-center rounded-lg bg-gold-100 text-gold" aria-hidden="true"><x-icon name="car" :size="20" /></span>
                            <div class="min-w-0">
                                <p class="font-display font-bold text-forest">{{ $vehicle->name }}</p>
                                <p class="text-sm text-ink-soft">{{ $vehicle->passenger_capacity }} seats · {{ $vehicle->luggage_capacity }} {{ \Illuminate\Support\Str::plural('bag', $vehicle->luggage_capacity) }}</p>
                            </div>
                            <p class="font-display text-xl font-extrabold text-forest max-sm:col-start-2 sm:w-32 sm:text-right">{{ $e['available'] ? $e['total_formatted'] : 'On request' }}</p>
                            <a class="btn btn-gold btn-sm max-sm:col-span-2 sm:w-20" href="{{ route('booking.create', ['pickup_location' => $route->origin->name, 'destination_location' => $route->destination->name, 'vehicle_category_id' => $vehicle->id]) }}">Book<span class="sr-only"> {{ $vehicle->name }}</span></a>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-sm text-ink-soft">{{ settings('fare_disclaimer') }}</p>

                @if ($route->description)
                    <div class="prose-site mt-10">{!! \App\Support\HtmlSanitizer::clean($route->description) !!}</div>
                @endif

                @if ($related->isNotEmpty())
                    <h2 class="display-2 mt-14 text-[1.7rem]! sm:text-[2.1rem]!">Related routes</h2>
                    <div class="mt-6 grid gap-5 sm:grid-cols-2">
                        @foreach ($related as $r)
                            <x-route-card :route="$r" />
                        @endforeach
                    </div>
                @endif
            </div>
            <aside class="lg:sticky lg:top-28">
                <x-booking-card :cities="$cities" :vehicles="$vehicles" :prefill="['pickup_location' => $route->origin->name, 'destination_location' => $route->destination->name]" />
            </aside>
        </div>
    </section>
@endsection
