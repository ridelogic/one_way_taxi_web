@props(['districts', 'headingLevel' => null])
{{-- Multi-column list of district landing-page links (alphabetical, reads top-to-bottom per column) --}}
<ul {{ $attributes->class(['district-links']) }}>
    @foreach ($districts as $district)
        <li>
            <a href="{{ $district->url() }}" class="district-link">
                <x-icon name="map-pin" :size="16" class="shrink-0 text-gold" />
                <span>Taxi in <strong>{{ $district->name }}</strong></span>
            </a>
        </li>
    @endforeach
</ul>
