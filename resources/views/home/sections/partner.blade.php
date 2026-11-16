<section class="section bg-ivory" id="partner" aria-labelledby="partner-title">
    <div class="container-site">
        <div class="grid overflow-hidden rounded-[2rem] bg-forest shadow-lift lg:grid-cols-2">
            <div class="p-8 sm:p-12">
                <span class="eyebrow eyebrow-light">Drive with us</span>
                <h2 id="partner-title" class="section-title mt-4 text-white">{{ $section->title }}</h2>
                @if ($section->subtitle)<p class="mt-3 text-lg text-white/80">{{ $section->subtitle }}</p>@endif
                @if ($section->get('body'))<p class="mt-4 text-white/75">{{ $section->get('body') }}</p>@endif
                <ul class="mt-6 space-y-3">
                    @foreach ($section->items() as $item)
                        <li class="flex gap-3 text-white/85"><x-icon :name="$item['icon'] ?? 'check'" :size="20" class="mt-0.5 text-gold" /><span><strong class="text-white">{{ $item['title'] ?? '' }}</strong> — {{ $item['text'] ?? '' }}</span></li>
                    @endforeach
                </ul>
                @if ($section->get('cta_label'))
                    <a href="{{ \App\Models\HeroSlide::href($section->get('cta_url') ?: '/become-a-partner') }}" class="btn btn-gold mt-8">{{ $section->get('cta_label') }} <x-icon name="arrow-right" :size="18" /></a>
                @endif
            </div>
            <div class="relative min-h-72">
                <x-img :media="$section->media" fallback="images/demo/partner-driver.jpg" alt="" class="absolute inset-0 size-full" />
            </div>
        </div>
    </div>
</section>
