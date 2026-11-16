@props(['slides', 'autoplay' => 6, 'trust' => [], 'cities' => collect(), 'bookingSection' => null])
@php
    // Homepage hero: full-bleed airport-at-sunset photo, navy gradient on the text side, three benefits
    // (CMS: Admin → Hero slides for the text and scene photo; Admin → Homepage sections → Hero for the benefits).
    // The booking search panel overlaps its bottom edge.
    $multiple = $slides->count() > 1;
    $first = $slides->first();
    $scene = $first?->media;
    $trust = $trust ?: [
        ['icon' => 'shield-check', 'title' => 'Safe & Secure Travel'],
        ['icon' => 'headset', 'title' => '24/7 Customer Support'],
        ['icon' => 'map', 'title' => 'Pan India Service'],
    ];
@endphp
<section class="home-hero" aria-label="Introduction and booking">
    <div class="home-hero__stage">
        <div class="home-hero__media">
            <x-img :media="$scene" fallback="images/demo/hero-airport-taxi-golden-hour.jpg" alt="" :priority="true" :lazy="false" class="home-hero__scene-img" />
        </div>
        <span class="home-hero__veil" aria-hidden="true"></span>

        <div class="container-site home-hero__content">
            <div @if ($multiple) data-slider data-autoplay="{{ (int) $autoplay }}" aria-roledescription="carousel" @endif>
                <div class="relative">
                    @forelse ($slides as $slide)
                        @php($isFirst = $loop->first)
                        <div @class(['hero-slide', 'is-active' => $isFirst]) data-slide
                             @if ($multiple) role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} of {{ $loop->count }}" @endif
                             @unless ($isFirst) aria-hidden="true" @endunless>
                            @if ($slide->eyebrow)
                                <span class="home-hero__eyebrow"><span class="home-hero__eyebrow-icon"><x-icon name="shield-check" :size="15" :stroke="2.4" /></span>{{ $slide->eyebrow }}</span>
                            @endif
                            @php($tag = $isFirst ? 'h1' : 'h2')
                            <{{ $tag }} class="home-hero__title">
                                {{ $slide->heading }}
                                @if ($slide->highlight)<span class="block">{!! $slide->highlightHtml() !!}</span>@endif
                            </{{ $tag }}>
                            @if ($slide->subheading)
                                <p class="home-hero__subheading">{{ $slide->subheading }}</p>
                            @endif
                            @if ($slide->subtitle)
                                <p class="home-hero__subtitle">{{ $slide->subtitle }}</p>
                            @endif
                            @if ($slide->primary_label || $slide->secondary_label)
                                <div class="mt-6 flex flex-wrap gap-3">
                                    @if ($slide->primary_label && $slide->primary_url)
                                        <a href="{{ \App\Models\HeroSlide::href($slide->primary_url) }}" class="btn btn-gold btn-pill" @unless ($isFirst) tabindex="-1" @endunless>{{ $slide->primary_label }} <x-icon name="arrow-right" :size="18" /></a>
                                    @endif
                                    @if ($slide->secondary_label && $slide->secondary_url)
                                        <a href="{{ \App\Models\HeroSlide::href($slide->secondary_url) }}" class="btn btn-ghost-light btn-pill" @unless ($isFirst) tabindex="-1" @endunless>{{ $slide->secondary_label }}</a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <h1 class="home-hero__title">{{ $settings->get('business_name') }}</h1>
                    @endforelse
                </div>
            </div>

            <ul class="home-hero__benefits">
                @foreach ($trust as $item)
                    <li>
                        <span class="home-hero__benefit-icon" aria-hidden="true"><x-icon :name="$item['icon'] ?? 'check'" :size="20" :stroke="2.2" /></span>
                        <span class="max-w-[6.5rem] sm:max-w-none">{{ $item['title'] ?? '' }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- The one booking form: a search panel on the hero's bottom edge --}}
    <x-booking-panel :cities="$cities" :section="$bookingSection" />
</section>
