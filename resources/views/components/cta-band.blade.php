@props(['section' => null])
@php
    $title = $section?->title ?: 'Ready for your next journey?';
    $subtitle = $section?->subtitle ?: 'Get a fare estimate in seconds or talk to our team.';
    $primaryLabel = $section?->get('primary_label') ?: 'Book Your Ride';
    $primaryUrl = $section?->get('primary_url') ?: '/book';
    $secondaryLabel = $section?->get('secondary_label') ?: 'Call Us';
    $secondaryUrl = $section?->get('secondary_url') ?: settings()->telLink();
@endphp
<section class="py-16 sm:py-20" aria-labelledby="cta-title">
    <div class="container-site">
        <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-forest to-fresh px-6 py-12 text-center shadow-lift sm:px-12 sm:py-16">
            <div aria-hidden="true" class="pointer-events-none absolute -top-20 -left-16 size-72 rounded-full bg-gold/25 blur-3xl"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -right-10 -bottom-24 size-72 rounded-full bg-white/10 blur-2xl"></div>
            @if ($section?->media)
                <div aria-hidden="true" class="absolute inset-0 opacity-15 mix-blend-luminosity"><x-img :media="$section->media" alt="" class="size-full" /></div>
            @endif
            <div class="relative mx-auto max-w-2xl">
                <h2 id="cta-title" class="text-3xl font-extrabold text-white sm:text-4xl">{{ $title }}</h2>
                <p class="mt-4 text-lg text-white/85">{{ $subtitle }}</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <a href="{{ \App\Models\HeroSlide::href($primaryUrl) }}" class="btn btn-gold btn-lg">{{ $primaryLabel }} <x-icon name="arrow-right" :size="18" /></a>
                    <a href="{{ \App\Models\HeroSlide::href($secondaryUrl) }}" class="btn btn-ghost-light btn-lg"><x-icon name="phone" :size="18" /> {{ $secondaryLabel }}</a>
                </div>
            </div>
        </div>
    </div>
</section>
