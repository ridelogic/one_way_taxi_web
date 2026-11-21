@php
    $light = $light ?? false; // on dark surfaces the logo sits on a white plate
    $tagline = $tagline ?? true;
    // Admin → Settings → Business → Logo overrides the official logo; otherwise the official artwork is used.
    $logo = $settings->media('logo_media_id');
    $logoSrc = $logo ? $logo->safeUrl() : asset(config('branding.logo.path'));
    $logoWidth = $logo ? ($logo->width ?? 164) : config('branding.logo.width');
    $logoHeight = $logo ? ($logo->height ?? 48) : config('branding.logo.height');
@endphp
{{-- The logo already carries the business name, so the image alone is the brand (the link keeps the name for screen readers). --}}
<a href="{{ route('home') }}" class="inline-flex shrink-0 flex-col items-start gap-3" aria-label="{{ $settings->get('business_name') }} — home">
    <span @class(['brand-plate' => $light, 'brand-plate--header' => $light && ! $tagline, 'inline-flex' => ! $light])>
        <img src="{{ $logoSrc }}" alt="{{ $settings->get('business_name') }}" class="brand-logo" width="{{ $logoWidth }}" height="{{ $logoHeight }}" decoding="async">
    </span>
    @if ($light && $tagline && $settings->get('tagline'))
        <span class="font-display text-sm font-semibold tracking-wide text-champagne">{{ $settings->get('tagline') }}</span>
    @endif
</a>
