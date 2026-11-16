<section class="section bg-white" id="about" aria-labelledby="about-title">
    <div class="container-site grid items-center gap-12 lg:grid-cols-2">
        <div class="overflow-hidden rounded-[2rem] border-[6px] border-white shadow-lift">
            <x-img :media="$section->media" fallback="images/demo/scenic-western-ghats.jpg" alt="" class="aspect-[5/4] w-full" />
        </div>
        <div>
            <x-section-heading eyebrow="About us" :title="$section->title" :subtitle="$section->subtitle" align="left" id="about-title" />
            @if ($section->get('body'))<p class="text-lg leading-relaxed text-ink-soft">{{ $section->get('body') }}</p>@endif
            <ul class="mt-6 space-y-3">
                @foreach ($section->items() as $item)
                    <li class="flex items-center gap-3 font-medium"><x-icon name="check-circle" :size="20" class="text-fresh" /> {{ $item['text'] ?? '' }}</li>
                @endforeach
            </ul>
            @if ($section->get('cta_label'))
                <a href="{{ \App\Models\HeroSlide::href($section->get('cta_url') ?: '/about') }}" class="btn btn-green mt-8">{{ $section->get('cta_label') }} <x-icon name="arrow-right" :size="18" /></a>
            @endif
        </div>
    </div>
</section>
