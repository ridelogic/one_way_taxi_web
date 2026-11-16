@props(['route', 'headingLevel' => 3])
<article {{ $attributes->class(['card card-hover group flex flex-col overflow-hidden']) }}>
    <a href="{{ route('routes.show', $route->slug) }}" class="relative block aspect-[16/10] overflow-hidden bg-mint" tabindex="-1" aria-hidden="true">
        <x-img :media="$route->displayMedia()" alt="" class="size-full transition duration-500 group-hover:scale-105" />
        <span class="absolute top-3 left-3 rounded-full bg-white/95 px-3 py-1 text-xs font-bold text-forest shadow-soft">One-way</span>
    </a>
    <div class="flex flex-1 flex-col p-5">
        <h{{ $headingLevel }} class="flex flex-wrap items-center gap-x-2 text-lg font-bold">
            <a href="{{ route('routes.show', $route->slug) }}" class="hover:text-fresh">{{ $route->origin->name }}</a>
            <x-icon name="arrow-right" :size="18" class="text-gold" />
            <a href="{{ route('routes.show', $route->slug) }}" class="hover:text-fresh">{{ $route->destination->name }}</a>
        </h{{ $headingLevel }}>
        <ul class="mt-3 flex flex-wrap gap-x-5 gap-y-1.5 text-sm text-ink-soft">
            <li class="flex items-center gap-1.5"><x-icon name="road" :size="16" class="text-fresh" /> {{ $route->distanceLabel() }}</li>
            <li class="flex items-center gap-1.5"><x-icon name="clock" :size="16" class="text-fresh" /> {{ $route->durationLabel() }}</li>
        </ul>
        <div class="mt-5 flex items-end justify-between gap-3 border-t border-line pt-4">
            <div>
                <p class="text-xs font-semibold tracking-wide text-ink-soft uppercase">Starting fare</p>
                <p class="flex items-center gap-2"><span class="font-display text-2xl font-extrabold text-gold-600">{{ inr($route->base_fare) }}</span><x-sample-badge :show="$route->is_sample_fare" /></p>
            </div>
            <a href="{{ route('booking.create', ['pickup_location' => $route->origin->name, 'destination_location' => $route->destination->name]) }}" class="btn btn-gold btn-sm">
                Book Now <span class="sr-only">— {{ $route->name() }}</span>
            </a>
        </div>
    </div>
</article>
