@php
    $title = $section->title ?: 'Ready for Your Next Journey?';
    $subtitle = $section->subtitle ?: 'Book a comfortable ride in minutes.';
    $primaryLabel = $section->get('primary_label') ?: 'Book Your Taxi';
    $primaryUrl = $section->get('primary_url') ?: '/book';
@endphp
<section class="home-section bg-white pt-4!" aria-labelledby="cta-title">
    <div class="container-site">
        <div class="journey-cta">
            <x-img :media="$section->media" fallback="images/demo/hero-munnar-highway.jpg" alt="" class="absolute inset-0 -z-10 size-full" />
            <span aria-hidden="true" class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,var(--color-navy)_0%,color-mix(in_oklab,var(--color-navy)_88%,transparent)_38%,color-mix(in_oklab,var(--color-navy)_30%,transparent)_70%,transparent_100%)]"></span>
            <div class="max-w-xl px-6 py-14 sm:px-12 sm:py-16 lg:py-20">
                <h2 id="cta-title" class="text-[1.9rem] leading-tight font-extrabold text-white sm:text-[2.5rem]">{{ $title }}</h2>
                <p class="mt-3 text-white/85 sm:text-lg">{{ $subtitle }}</p>
                <div class="mt-7 flex flex-wrap gap-3">
                    <a href="{{ \App\Models\HeroSlide::href($primaryUrl) }}" class="btn btn-gold btn-lg">{{ $primaryLabel }} <x-icon name="arrow-right" :size="18" /></a>
                    @if ($settings->get('phone'))
                        <a href="{{ $settings->telLink() }}" class="btn btn-ghost-light btn-lg"><x-icon name="phone" :size="18" /> {{ $settings->get('phone') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
