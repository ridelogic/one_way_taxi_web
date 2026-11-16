@extends('layouts.app')

@section('content')
    <x-page-banner :title="$route->origin->name.' to '.$route->destination->name.' Taxi'" :subtitle="$route->short_description" :media="$route->displayMedia()" :crumbs="['Routes' => route('routes.index'), $route->name() => null]">
        <ul class="mt-6 flex flex-wrap gap-3">
            <li class="flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-forest shadow-soft"><x-icon name="road" :size="16" class="text-fresh" /> {{ $route->distanceLabel() }}</li>
            <li class="flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-forest shadow-soft"><x-icon name="clock" :size="16" class="text-fresh" /> {{ $route->durationLabel() }}</li>
            <li class="flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-forest shadow-soft">From <span class="text-gold-600">{{ inr($route->base_fare) }}</span> <x-sample-badge :show="$route->is_sample_fare" /></li>
        </ul>
    </x-page-banner>

    <section class="section pt-12!">
        <div class="container-site grid items-start gap-10 lg:grid-cols-[1fr_400px]">
            <div>
                <h2 class="section-title text-2xl! sm:text-3xl!">Fares by vehicle</h2>
                <div class="card mt-6 divide-y divide-line">
                    @foreach ($vehicles as $vehicle)
                        @php($e = $estimates[$vehicle->id])
                        <div class="flex flex-wrap items-center gap-4 p-4 sm:flex-nowrap">
                            <div class="h-16 w-24 shrink-0 overflow-hidden rounded-xl bg-mint"><x-img :media="$vehicle->media" alt="" class="size-full" /></div>
                            <div class="min-w-0 flex-1">
                                <p class="font-display font-bold text-forest">{{ $vehicle->name }}</p>
                                <p class="text-sm text-ink-soft">{{ $vehicle->passenger_capacity }} seats · {{ $vehicle->luggage_capacity }} {{ \Illuminate\Support\Str::plural('bag', $vehicle->luggage_capacity) }}</p>
                            </div>
                            <p class="font-display text-xl font-extrabold text-gold-600">{{ $e['available'] ? $e['total_formatted'] : 'On request' }}</p>
                            <a class="btn btn-gold btn-sm" href="{{ route('booking.create', ['pickup_location' => $route->origin->name, 'destination_location' => $route->destination->name, 'vehicle_category_id' => $vehicle->id]) }}">Book</a>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-sm text-ink-soft">{{ settings('fare_disclaimer') }}</p>

                @if ($route->description)
                    <div class="prose-site mt-10">{!! \App\Support\HtmlSanitizer::clean($route->description) !!}</div>
                @endif

                @if ($related->isNotEmpty())
                    <h2 class="section-title mt-12 text-2xl! sm:text-3xl!">Related routes</h2>
                    <div class="mt-6 grid gap-6 sm:grid-cols-2">
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
