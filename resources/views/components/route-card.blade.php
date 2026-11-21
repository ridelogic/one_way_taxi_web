@props(['route', 'headingLevel' => 3])
{{-- Route ticket: pickup → drop, distance, travel time and the starting fare (all from Admin → Routes) --}}
<article {{ $attributes->class(['route-ticket']) }}>
    <div class="route-ticket__head">
        <span class="route-ticket__tag">One-way</span>
        <span class="flex flex-wrap items-center justify-end gap-x-4 gap-y-1 text-ink-soft">
            <span class="inline-flex items-center gap-1.5"><x-icon name="road" :size="14" class="text-gold" /> {{ $route->distanceLabel() }}</span>
            <span class="inline-flex items-center gap-1.5"><x-icon name="clock" :size="14" class="text-gold" /> {{ $route->durationLabel() }}</span>
        </span>
    </div>

    <h{{ $headingLevel }} class="route-ticket__path">
        <a href="{{ route('routes.show', $route->slug) }}" class="route-ticket__city">
            <small>Pickup</small>
            <strong>{{ $route->origin->name }}</strong>
            @if ($route->origin->state)<span>{{ $route->origin->state }}</span>@endif
        </a>
        <span class="route-ticket__line" aria-hidden="true">
            <span class="route-ticket__dash"></span>
            <span class="route-ticket__car"><x-icon name="car" :size="16" /></span>
            <span class="route-ticket__dash"></span>
        </span>
        <a href="{{ route('routes.show', $route->slug) }}" class="route-ticket__city text-right">
            <small><span class="sr-only">to </span>Drop</small>
            <strong>{{ $route->destination->name }}</strong>
            @if ($route->destination->state)<span>{{ $route->destination->state }}</span>@endif
        </a>
    </h{{ $headingLevel }}>

    <div class="route-ticket__foot">
        <div class="leading-tight">
            <p class="text-[0.7rem] font-bold tracking-[0.12em] text-ink-soft uppercase">Starting fare</p>
            <p class="mt-1 flex flex-wrap items-center gap-2"><span class="font-display text-[1.6rem] leading-none font-extrabold text-forest">{{ inr($route->base_fare) }}</span><x-sample-badge :show="$route->is_sample_fare" /></p>
        </div>
        <a href="{{ route('booking.create', ['pickup_location' => $route->origin->name, 'destination_location' => $route->destination->name]) }}" class="btn btn-gold btn-sm">
            Book Now <span class="sr-only">— {{ $route->name() }}</span>
        </a>
    </div>
</article>
