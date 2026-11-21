@props(['slides', 'autoplay' => 6, 'trust' => [], 'cities' => collect(), 'bookingSection' => null])
@php
    // Homepage hero: the site owner's OneWayTrip Taxi banner (public/images/demo/onewaytrip-hero-banner.jpg, cropped
    // from public/images/OneWayTrip-Taxi-Hero-Banner.png) shown at its natural aspect ratio, followed by the working
    // booking form on a matching red band. The banner already shows the heading, subtitle and highlights as artwork,
    // so each slide's text is also output as real text for search engines and screen readers (visually hidden).
    // CMS: Admin → Hero slides (banner image, heading text, optional buttons; several visible slides rotate);
    // Admin → Homepage sections → Hero (highlights).
    $multiple = $slides->count() > 1;
    $first = $slides->first();
    $trust = $trust ?: [
        ['icon' => 'shield-check', 'title' => 'Safe & Secure'],
        ['icon' => 'badge-check', 'title' => 'Verified Drivers'],
        ['icon' => 'clock', 'title' => 'On-Time Service'],
    ];
    $buttons = collect([
        $first?->primary_label && $first?->primary_url ? ['label' => $first->primary_label, 'url' => $first->primary_url, 'class' => 'btn btn-yellow'] : null,
        $first?->secondary_label && $first?->secondary_url ? ['label' => $first->secondary_label, 'url' => $first->secondary_url, 'class' => 'btn btn-ghost-light'] : null,
    ])->filter();
@endphp
<section class="home-hero" aria-labelledby="home-hero-title">
    <div class="home-hero__banner" @if ($multiple) data-slider data-autoplay="{{ (int) $autoplay }}" aria-roledescription="carousel" @endif>
        @forelse ($slides as $slide)
            @php($isFirst = $loop->first)
            <div @class(['hero-slide', 'is-active' => $isFirst]) data-slide
                 @if ($multiple) role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} of {{ $loop->count }}" @endif
                 @unless ($isFirst) aria-hidden="true" @endunless>
                <div class="sr-only">
                    @php($tag = $isFirst ? 'h1' : 'h2')
                    <{{ $tag }} @if ($isFirst) id="home-hero-title" @endif>
                        {{ $slide->heading }}
                        @if ($slide->highlight)<span class="block">{!! $slide->highlightHtml() !!}</span>@endif
                    </{{ $tag }}>
                    @if ($slide->subheading)<p>{{ $slide->subheading }}</p>@endif
                    @if ($slide->subtitle)<p>{{ $slide->subtitle }}</p>@endif
                </div>
                <x-img :media="$slide->media" fallback="images/demo/onewaytrip-hero-banner.jpg" alt="" :priority="$isFirst" :lazy="! $isFirst" class="home-hero__scene-img" />
            </div>
        @empty
            <h1 id="home-hero-title" class="sr-only">{{ $settings->get('business_name') }}</h1>
            <img src="{{ asset('images/demo/onewaytrip-hero-banner.jpg') }}" alt="" width="1774" height="512" fetchpriority="high" class="home-hero__scene-img">
        @endforelse

        @if ($multiple)
            <div class="home-hero__controls">
                <button type="button" class="carousel-btn" data-slider-prev aria-label="Previous banner"><x-icon name="chevron-left" :size="20" /></button>
                <button type="button" class="carousel-btn" data-slider-next aria-label="Next banner"><x-icon name="chevron-right" :size="20" /></button>
            </div>
        @endif
    </div>

    <ul class="sr-only">
        @foreach ($trust as $item)
            <li>{{ $item['title'] ?? '' }}</li>
        @endforeach
    </ul>

    {{-- The one booking form, positioned under the banner on its red band --}}
    <div class="home-hero__booking">
        <x-booking-panel :cities="$cities" :section="$bookingSection" :in-hero="true" />
        @if ($buttons->isNotEmpty())
            <div class="container-wide mt-6 flex flex-wrap justify-center gap-3">
                @foreach ($buttons as $button)
                    <a href="{{ \App\Models\HeroSlide::href($button['url']) }}" class="{{ $button['class'] }}">{{ $button['label'] }} <x-icon name="arrow-right" :size="18" /></a>
                @endforeach
            </div>
        @endif
    </div>
</section>
