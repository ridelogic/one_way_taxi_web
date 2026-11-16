@if ($section->items())
<section class="bg-ivory pb-4" aria-label="{{ $section->title ?: 'Why book with us' }}">
    <div class="container-wide xl:pr-[7rem]">
        <ul class="grid overflow-hidden rounded-[1.5rem] bg-white shadow-soft ring-1 ring-forest/8 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($section->items() as $item)
                <li class="flex items-center gap-4 border-line px-6 py-5 not-last:border-b sm:[&:nth-child(odd)]:border-r lg:border-b-0 lg:not-last:border-r">
                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-forest text-gold"><x-icon :name="$item['icon'] ?? 'check'" :size="22" :stroke="2.1" /></span>
                    <span>
                        <span class="block font-display text-base font-extrabold text-forest">{{ $item['title'] ?? '' }}</span>
                        @if (! empty($item['text']))<span class="mt-0.5 block text-sm leading-snug text-ink-soft">{{ $item['text'] }}</span>@endif
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
