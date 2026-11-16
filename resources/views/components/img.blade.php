@props([
    'media' => null,          // ?App\Models\MediaAsset
    'fallback' => null,       // path under public/, used when no media is set
    'alt' => null,
    'lazy' => true,
    'priority' => false,
    'sizes' => null,
])
@php
    $placeholder = asset(\App\Models\MediaAsset::PLACEHOLDER);
    if ($media) {
        $src = $media->safeUrl();
        $width = $media->width;
        $height = $media->height;
        $style = 'object-fit:'.$media->object_fit.';object-position:'.$media->objectPosition().';';
        $altText = $alt ?? $media->altText();
    } else {
        $src = $fallback ? asset($fallback) : $placeholder;
        $width = $height = null;
        $style = 'object-fit:cover;object-position:50% 50%;';
        $altText = $alt ?? '';
    }
@endphp
<img src="{{ $src }}" alt="{{ $altText }}"
     @if ($width) width="{{ $width }}" height="{{ $height }}" @endif
     loading="{{ $priority ? 'eager' : ($lazy ? 'lazy' : 'eager') }}"
     @if ($priority) fetchpriority="high" @endif
     decoding="async"
     @if ($sizes) sizes="{{ $sizes }}" @endif
     style="{{ $style }}"
     onerror="this.onerror=null;this.src='{{ $placeholder }}'"
     {{ $attributes }}>
