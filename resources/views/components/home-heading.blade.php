@props(['eyebrow' => null, 'title', 'subtitle' => null, 'id' => null, 'light' => false])
<div {{ $attributes->class(['home-heading']) }}>
    @if ($eyebrow)
        <span @class(['home-heading__eyebrow', 'text-champagne!' => $light])>{{ $eyebrow }}</span>
    @endif
    <h2 @if ($id) id="{{ $id }}" @endif @class(['home-heading__title', 'text-white' => $light])>{{ $title }}</h2>
    <span class="home-heading__rule" aria-hidden="true"></span>
    @if ($subtitle)
        <p @class(['home-heading__lead', 'text-white/75!' => $light])>{{ $subtitle }}</p>
    @endif
</div>
