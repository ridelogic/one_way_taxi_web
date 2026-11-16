@props(['vehicle', 'headingLevel' => 3])
<article {{ $attributes->class(['card card-hover group flex flex-col overflow-hidden']) }}>
    <a href="{{ route('fleet.show', $vehicle->slug) }}" class="relative block aspect-[4/3] overflow-hidden bg-mint" tabindex="-1" aria-hidden="true">
        <x-img :media="$vehicle->media" alt="" class="size-full transition duration-500 group-hover:scale-105" />
        @if ($vehicle->category)
            <span class="absolute top-3 left-3 rounded-full bg-forest px-3 py-1 text-xs font-bold text-white">{{ $vehicle->category }}</span>
        @endif
    </a>
    <div class="flex flex-1 flex-col p-5">
        <h{{ $headingLevel }} class="text-lg font-bold"><a href="{{ route('fleet.show', $vehicle->slug) }}" class="hover:text-fresh">{{ $vehicle->name }}</a></h{{ $headingLevel }}>
        @if ($vehicle->example_models)
            <p class="mt-1 text-sm text-ink-soft">{{ $vehicle->example_models }}</p>
        @endif
        <ul class="mt-4 grid grid-cols-2 gap-2 text-sm">
            <li class="flex items-center gap-2 rounded-lg bg-mint px-3 py-2 font-medium"><x-icon name="users" :size="16" class="text-fresh" /> {{ $vehicle->passenger_capacity }} seats</li>
            <li class="flex items-center gap-2 rounded-lg bg-mint px-3 py-2 font-medium"><x-icon name="briefcase" :size="16" class="text-fresh" /> {{ $vehicle->luggage_capacity }} {{ \Illuminate\Support\Str::plural('bag', $vehicle->luggage_capacity) }}</li>
        </ul>
        <div class="mt-auto flex items-center justify-between gap-3 pt-5">
            @if ($vehicle->relationLoaded('fareRule') && $vehicle->fareRule)
                <p class="text-sm text-ink-soft"><span class="font-display text-lg font-extrabold text-gold-600">{{ inr($vehicle->fareRule->per_km_rate) }}</span>/km <x-sample-badge /></p>
            @else
                <span></span>
            @endif
            <a href="{{ route('fleet.show', $vehicle->slug) }}" class="btn btn-outline btn-sm">View Details</a>
        </div>
    </div>
</article>
