{{--
    About + Why choose us (OneWayTrip_Taxi_Homepage_Mockup.jpg): one compact band directly below the hero with three
    areas side by side — photo | About | Why choose us (2 × 2). Content comes from the CMS:
    Admin → Homepage sections → About (photo, heading, text, benefits, button) and → Why choose us (the four benefits).
--}}
@php($why = ($sections ?? collect())->get('why_choose'))
@php($hasWhy = $why && $why->items())
<section class="intro-band" id="about" aria-labelledby="about-title">
    <div @class(['intro-band__inner', 'intro-band__inner--no-why' => ! $hasWhy])>
        {{-- Photo with the yellow accent offset to the bottom-right --}}
        <div class="intro-band__media">
            <span class="intro-band__accent" aria-hidden="true"></span>
            <x-img :media="$section->media" fallback="images/demo/about-woman-in-car-mockup.jpg" alt="" class="intro-band__img" sizes="280px" />
        </div>

        {{-- About --}}
        <div class="intro-band__about">
            <span class="kicker">{{ $section->get('eyebrow') ?: 'About us' }}</span>
            <h2 id="about-title" class="intro-band__title">{{ $section->title }}</h2>
            @if ($section->subtitle)
                <p class="intro-band__tagline">{{ $section->subtitle }}</p>
            @endif
            @if ($section->get('body'))
                <p class="intro-band__text">{{ $section->get('body') }}</p>
            @endif
            @if ($section->items())
                <ul class="intro-band__list">
                    @foreach ($section->items() as $item)
                        <li><span class="intro-band__tick" aria-hidden="true"><x-icon name="check" :size="12" :stroke="3" /></span> {{ $item['text'] ?? '' }}</li>
                    @endforeach
                </ul>
            @endif
            @if ($section->get('cta_label'))
                <a href="{{ \App\Models\HeroSlide::href($section->get('cta_url') ?: '/about') }}" class="btn btn-gold intro-band__cta">{{ $section->get('cta_label') }} <x-icon name="arrow-right" :size="18" /></a>
            @endif
        </div>

        {{-- Why choose us (the separate dark section is folded in here, so it is not repeated further down) --}}
        @if ($hasWhy)
            <div class="intro-band__why" id="why-us">
                <h2 id="why-title" class="kicker">{{ $why->title }}</h2>
                <ul class="intro-band__features">
                    @foreach ($why->items() as $item)
                        <li>
                            <span class="intro-band__icon" aria-hidden="true"><x-icon :name="$item['icon'] ?? 'check'" :size="24" /></span>
                            <span>
                                <h3 class="intro-band__feature">{{ $item['title'] ?? '' }}</h3>
                                <p class="intro-band__desc">{{ $item['text'] ?? '' }}</p>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</section>
