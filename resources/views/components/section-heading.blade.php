@props(['eyebrow' => null, 'title', 'subtitle' => null, 'align' => 'center', 'level' => 2, 'light' => false])
<div {{ $attributes->class(['mb-10 sm:mb-12', 'mx-auto max-w-2xl text-center' => $align === 'center', 'max-w-2xl' => $align !== 'center']) }}>
    @if ($eyebrow)
        <span @class(['eyebrow', 'eyebrow-light' => $light])>{{ $eyebrow }}</span>
    @endif
    <h{{ $level }} @class(['section-title mt-4', 'text-white' => $light])>{{ $title }}</h{{ $level }}>
    @if ($subtitle)
        <p @class(['section-lead', 'text-white/75' => $light])>{{ $subtitle }}</p>
    @endif
    {{ $slot }}
</div>
