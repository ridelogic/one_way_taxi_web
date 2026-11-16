@if ($popularRoutes->isNotEmpty())
@php
    $map = ($id = $section->get('map_media_id')) ? \App\Models\MediaAsset::find($id) : null;
    $scenic = ($id = $section->get('scenic_media_id')) ? \App\Models\MediaAsset::find($id) : null;
@endphp
<section class="relative overflow-hidden bg-mint pt-4 pb-12 sm:pb-14" id="routes" aria-labelledby="routes-title">
    {{-- Right: scenic image bleeding off the edge (wide screens) --}}
    <div class="absolute top-4 right-0 hidden w-[18%] max-w-[300px] min-[1380px]:block" aria-hidden="true">
        <x-img :media="$scenic" fallback="images/demo/design-scenic-journeys.webp" alt="" class="aspect-[274/226] w-full rounded-l-[999px]" />
    </div>

    <div class="container-wide relative grid items-center gap-8 lg:grid-cols-[210px_minmax(0,1fr)] min-[1380px]:pr-[15.5%] 2xl:pr-[260px]">
        {{-- Left: India map illustration --}}
        <div class="relative hidden lg:block">
            <x-img :media="$map" fallback="images/demo/design-india-map.png" :alt="$map?->altText() ?? 'Illustrated map of India'" class="w-full" />
            @if ($section->get('map_title') || $section->get('map_highlight'))
                <p class="absolute right-0 bottom-6 text-right font-display text-[1.6rem] leading-[1.05] font-extrabold text-forest [text-shadow:0_0_8px_#fff,0_0_3px_#fff,0_0_1px_#fff]">
                    @foreach (preg_split('/\s+/', (string) $section->get('map_title')) as $word)<span class="block">{{ $word }}</span>@endforeach
                    <span class="block text-[1.9rem] text-gold-600">{{ $section->get('map_highlight') }}</span>
                </p>
            @endif
        </div>

        <div class="min-w-0">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <span class="eyebrow">{{ $section->get('eyebrow') ?: 'Popular routes & fares' }}</span>
                    <h2 id="routes-title" class="mt-2 text-[1.9rem] leading-tight font-extrabold text-ink sm:text-[2.1rem]">{{ $section->title }}</h2>
                </div>
                @if ($section->get('button_label'))
                    <a href="{{ \App\Models\HeroSlide::href($section->get('button_url') ?: '/routes') }}" class="btn btn-sm btn-pill shrink-0 border-2 border-gold bg-white px-5 text-forest hover:bg-gold hover:text-white">{{ $section->get('button_label') }} <x-icon name="arrow-right" :size="16" /></a>
                @endif
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($popularRoutes as $route)
                    <article class="flex gap-3 rounded-xl border border-line/70 bg-white p-2.5 shadow-soft transition hover:-translate-y-0.5 hover:shadow-lift">
                        <a href="{{ route('routes.show', $route->slug) }}" class="block size-24 shrink-0 overflow-hidden rounded-lg xl:h-[6.5rem] xl:w-[5.5rem]" tabindex="-1" aria-hidden="true">
                            <x-img :media="$route->displayMedia()" alt="" class="size-full" />
                        </a>
                        <div class="flex min-w-0 flex-1 flex-col py-1 pr-1">
                            <h3 class="text-[0.95rem] leading-snug font-bold text-ink"><a href="{{ route('routes.show', $route->slug) }}" class="hover:text-forest">{{ $route->origin->name }} to {{ $route->destination->name }}</a></h3>
                            <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[0.8rem] text-ink-soft">
                                <span class="flex items-center gap-1"><x-icon name="road" :size="15" /> {{ \Illuminate\Support\Str::upper($route->distanceLabel()) }}</span>
                                <span class="flex items-center gap-1"><x-icon name="clock" :size="15" /> {{ $route->durationLabel() }}</span>
                            </p>
                            <div class="mt-auto flex items-end justify-between gap-1.5 pt-2">
                                <p class="leading-tight whitespace-nowrap">
                                    <span class="block font-display text-[1.15rem] font-extrabold text-gold-600">{{ inr($route->base_fare) }}@if ($route->is_sample_fare && settings()->bool('demo_mode'))<span class="text-sm align-super" title="Sample fare">*</span>@endif</span>
                                    <span class="text-xs text-ink-soft">Starting from</span>
                                </p>
                                <a href="{{ route('booking.create', ['pickup_location' => $route->origin->name, 'destination_location' => $route->destination->name]) }}" class="btn btn-gold shrink-0 gap-1 rounded-lg px-2 py-1.5 text-[0.72rem]">Book Now <x-icon name="arrow-right" :size="14" /><span class="sr-only"> — {{ $route->name() }}</span></a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            @if (settings()->bool('demo_mode') && $popularRoutes->contains('is_sample_fare', true))
                <p class="mt-4 text-xs text-ink-soft">* Sample fares pending verification. Tolls, permits and parking extra as applicable.</p>
            @endif
        </div>
    </div>
</section>
@endif
