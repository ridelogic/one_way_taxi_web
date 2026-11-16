<section class="section bg-white" id="corporate" aria-labelledby="corporate-title">
    <div class="container-site grid items-center gap-12 lg:grid-cols-2">
        <div class="overflow-hidden rounded-[2rem] border-[6px] border-white shadow-lift">
            <x-img :media="$section->media" fallback="images/demo/fleet-premium-sedan.jpg" alt="" class="aspect-[5/4] w-full" />
        </div>
        <div>
            <x-section-heading eyebrow="Corporate" :title="$section->title" :subtitle="$section->subtitle" align="left" id="corporate-title" />
            @if ($section->get('body'))<p class="text-lg leading-relaxed text-ink-soft">{{ $section->get('body') }}</p>@endif
            <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                @foreach ($section->items() as $item)
                    <li class="flex items-center gap-3 rounded-xl bg-mint p-3">
                        <span class="grid size-10 place-items-center rounded-lg bg-white text-fresh"><x-icon :name="$item['icon'] ?? 'check'" :size="20" /></span>
                        <span class="leading-tight"><strong class="block text-sm text-forest">{{ $item['title'] ?? '' }}</strong><small class="text-ink-soft">{{ $item['text'] ?? '' }}</small></span>
                    </li>
                @endforeach
            </ul>
            @if ($section->get('cta_label'))
                <a href="{{ \App\Models\HeroSlide::href($section->get('cta_url') ?: '/corporate') }}" class="btn btn-gold mt-8">{{ $section->get('cta_label') }} <x-icon name="arrow-right" :size="18" /></a>
            @endif
        </div>
    </div>
</section>
