@php
    $items = $section->items();
    $mediaById = \App\Models\MediaAsset::whereIn('id', collect($items)->pluck('image')->filter())->get()->keyBy('id');
@endphp
@if ($items)
<section class="section bg-white" id="cabs" aria-labelledby="cabs-title">
    <div class="container-site">
        <div class="flex items-end justify-between gap-6">
            <x-section-heading eyebrow="Cabs & services" :title="$section->title" :subtitle="$section->subtitle" align="left" id="cabs-title" />
            <div class="mb-12 hidden gap-2 sm:flex">
                <button type="button" class="grid size-11 place-items-center rounded-full border border-line bg-white text-forest hover:border-gold" data-carousel-prev="cabs-track" aria-label="Scroll left"><x-icon name="chevron-right" :size="20" class="rotate-180" /></button>
                <button type="button" class="grid size-11 place-items-center rounded-full border border-line bg-white text-forest hover:border-gold" data-carousel-next="cabs-track" aria-label="Scroll right"><x-icon name="chevron-right" :size="20" /></button>
            </div>
        </div>
        <ul id="cabs-track" tabindex="0" aria-label="{{ $section->title }}" class="scroller grid snap-x snap-mandatory auto-cols-[80%] grid-flow-col gap-5 overflow-x-auto pb-4 sm:auto-cols-[45%] lg:auto-cols-[calc((100%-3*1.25rem)/4)]">
            @foreach ($items as $item)
                @php($media = $mediaById->get($item['image'] ?? 0))
                <li class="snap-start">
                    <a href="{{ \App\Models\HeroSlide::href($item['link'] ?? '/fleet') }}" class="card card-hover group flex h-full flex-col overflow-hidden">
                        <span class="relative block aspect-[4/3] overflow-hidden bg-mint">
                            <x-img :media="$media" alt="" class="size-full transition duration-500 group-hover:scale-105" />
                            @if (! empty($item['badge']))<span class="absolute top-3 left-3 rounded-full bg-gold px-3 py-1 text-xs font-bold text-white">{{ $item['badge'] }}</span>@endif
                        </span>
                        <span class="flex flex-1 flex-col p-5">
                            <strong class="font-display text-lg text-forest">{{ $item['title'] ?? '' }}</strong>
                            <span class="mt-1 flex-1 text-sm text-ink-soft">{{ $item['text'] ?? '' }}</span>
                            <span class="mt-3 inline-flex items-center gap-1.5 text-sm font-bold text-fresh">Explore <x-icon name="arrow-right" :size="16" /></span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
