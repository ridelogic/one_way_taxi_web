@props(['districts', 'headingLevel' => 3])
{{-- District cards: landmark photo, district name, short description and a link to the district's landing page --}}
<ul {{ $attributes->class(['district-grid']) }}>
    @foreach ($districts as $district)
        <li>
            <a href="{{ $district->url() }}" class="district-card">
                <span class="district-card__media">
                    @unless ($district->isTamilNadu())<span class="district-card__tag">{{ $district->typeLabel() }}</span>@endunless
                    <x-img :media="$district->cardImage()" fallback="images/demo/routes-sedan-highway-sunset.jpg" :alt="$district->image_alt ?: ($district->cardImage()?->altText() ?: $district->imageAlt())" class="size-full" sizes="(min-width: 1024px) 290px, (min-width: 640px) 50vw, 100vw" />
                    <h{{ $headingLevel }} class="district-card__name">{{ $district->name }}</h{{ $headingLevel }}>
                </span>
                <span class="district-card__body">
                    <span class="district-card__text">{{ $district->cardSummary() }}</span>
                    <span class="district-card__link">Explore Taxi Services <x-icon name="arrow-right" :size="16" /></span>
                </span>
            </a>
        </li>
    @endforeach
</ul>
