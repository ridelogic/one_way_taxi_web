@if ($vehicles->isNotEmpty())
@php($fleet = $vehicles->take((int) ($section->get('limit') ?: 5)))
<section class="home-section bg-white" id="fleet" aria-labelledby="fleet-title">
    <div class="container-site">
        <x-home-heading :eyebrow="$section->get('eyebrow') ?: 'Our fleet'" :title="trim($section->title.' '.$section->get('title_highlight'))" :subtitle="$section->subtitle" id="fleet-title" />
        <ul class="grid grid-cols-1 gap-5 min-[480px]:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
            @foreach ($fleet as $vehicle)
                <li>
                    <a href="{{ route('fleet.show', $vehicle->slug) }}" class="fleet-card group">
                        <span class="fleet-card__media">
                            <x-img :media="$vehicle->media" alt="" class="size-full transition duration-500 group-hover:scale-105" />
                        </span>
                        <h3 class="mt-4 text-lg font-bold group-hover:text-gold">{{ $vehicle->name }}</h3>
                        @if ($vehicle->example_models)
                            <span class="text-xs font-medium text-ink-soft">{{ $vehicle->example_models }}</span>
                        @endif
                        <span class="mt-2 flex-1 text-sm leading-relaxed text-ink-soft">{{ $vehicle->short_description }}</span>
                        <span class="mt-3 flex justify-center gap-4 border-t border-line pt-3 text-[0.82rem] font-medium text-ink">
                            <span class="flex items-center gap-1.5"><x-icon name="users" :size="16" class="text-gold" /> {{ $vehicle->passenger_capacity }} Seats</span>
                            <span class="flex items-center gap-1.5"><x-icon name="briefcase" :size="16" class="text-gold" /> {{ $vehicle->luggage_capacity }} {{ \Illuminate\Support\Str::plural('Bag', $vehicle->luggage_capacity) }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
        @if ($section->get('button_label'))
            <div class="mt-10 text-center">
                <a href="{{ route('fleet.index') }}" class="btn btn-outline">{{ $section->get('button_label') }} <x-icon name="arrow-right" :size="18" /></a>
            </div>
        @endif
    </div>
</section>
@endif
