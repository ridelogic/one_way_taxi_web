@php
    $title = $section->title ?: 'Ready for Your Next Journey?';
    $subtitle = $section->subtitle ?: 'Book a comfortable ride in minutes.';
    $primaryLabel = $section->get('primary_label') ?: 'Book Your Taxi';
    $primaryUrl = $section->get('primary_url') ?: '/book';
@endphp
<section class="home-section bg-white" aria-labelledby="cta-title">
    <div class="container-site">
        <div class="journey-cta">
            <x-img :media="$section->media" fallback="images/demo/scenic-city-highway.jpg" alt="" class="absolute inset-0 -z-10 size-full" />
            <span aria-hidden="true" class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,var(--color-navy)_0%,color-mix(in_oklab,var(--color-navy)_90%,transparent)_40%,color-mix(in_oklab,var(--color-navy)_45%,transparent)_75%,color-mix(in_oklab,var(--color-navy)_25%,transparent)_100%)]"></span>
            <span aria-hidden="true" class="absolute inset-y-0 left-0 w-1.5 bg-gold"></span>
            <div class="max-w-2xl px-6 py-10 sm:px-12 sm:py-12 lg:px-16 xl:px-20">
                <span class="kicker kicker-light">Book in minutes</span>
                <h2 id="cta-title" class="mt-4 text-[1.95rem] leading-[1.1] font-extrabold tracking-[-0.025em] text-white sm:text-[2.1rem] xl:text-[2.3rem]">{{ $title }}</h2>
                <p class="mt-4 max-w-xl text-white/85 sm:text-lg">{{ $subtitle }}</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ \App\Models\HeroSlide::href($primaryUrl) }}" class="btn btn-gold btn-lg">{{ $primaryLabel }} <x-icon name="arrow-right" :size="18" /></a>
                    @if ($settings->get('phone'))
                        <a href="{{ $settings->telLink() }}" class="btn btn-ghost-light btn-lg"><x-icon name="phone" :size="18" /> {{ $settings->get('phone') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
