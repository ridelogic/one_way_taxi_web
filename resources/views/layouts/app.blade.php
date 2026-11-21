@php
    /** @var \App\Support\Seo $seo */
    $seo = $seo ?? \App\Support\Seo::make();
    $businessName = $settings->get('business_name');
    $favicon = $settings->media('favicon_media_id');
    $indexing = $settings->bool('search_engine_indexing') && ! $seo->noindex;
    $hex = fn (string $key, string $fallback) => preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $settings->get($key)) ? $settings->get($key) : $fallback;
@endphp
<!DOCTYPE html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $seo->fullTitle($settings) }}</title>
    <meta name="description" content="{{ $seo->metaDescription($settings) }}">
    <link rel="canonical" href="{{ $seo->canonicalUrl() }}">
    <meta name="robots" content="{{ $indexing ? 'index, follow' : 'noindex, nofollow' }}">
    @if ($seo->keywords)<meta name="keywords" content="{{ $seo->keywords }}">@endif
    <meta name="theme-color" content="{{ $hex('brand_green', '#080D18') }}">

    <meta property="og:site_name" content="{{ $businessName }}">
    <meta property="og:type" content="{{ $seo->type }}">
    <meta property="og:title" content="{{ $seo->ogTitle ?: $seo->fullTitle($settings) }}">
    <meta property="og:description" content="{{ $seo->ogDescription ? str($seo->ogDescription)->squish()->limit(200) : $seo->metaDescription($settings) }}">
    <meta property="og:url" content="{{ $seo->canonicalUrl() }}">
    <meta property="og:image" content="{{ $seo->imageUrl($settings) }}">
    <meta property="og:locale" content="en_IN">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo->ogTitle ?: $seo->fullTitle($settings) }}">
    <meta name="twitter:description" content="{{ $seo->ogDescription ? str($seo->ogDescription)->squish()->limit(200) : $seo->metaDescription($settings) }}">
    <meta name="twitter:image" content="{{ $seo->imageUrl($settings) }}">

    @if ($favicon)
        <link rel="icon" href="{{ $favicon->safeUrl() }}">
    @else
        {{-- /favicon.ico (16/32/48px, same mark) is picked up by browsers automatically --}}
        <link rel="icon" href="{{ asset(config('branding.favicon_32')) }}" type="image/png" sizes="32x32">
        <link rel="icon" href="{{ asset(config('branding.favicon_512')) }}" type="image/png" sizes="512x512">
    @endif
    <link rel="apple-touch-icon" href="{{ asset(config('branding.apple_touch_icon')) }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Brand colours from Admin → Settings → Business --}}
    <style>:root{--color-gold:{{ $hex('brand_gold', '#E60000') }};--color-forest:{{ $hex('brand_green', '#080D18') }};--color-fresh:{{ $hex('brand_green_fresh', '#B8030F') }}}</style>
    @stack('head')

    @if ($settings->bool('structured_data_enabled'))
        @php
            $schema = array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'TaxiService',
                'name' => $businessName,
                'url' => route('home'),
                'telephone' => $settings->get('phone'),
                'email' => $settings->get('email'),
                'image' => $seo->imageUrl($settings),
                'areaServed' => 'IN',
                'provider' => array_filter([
                    '@type' => 'LocalBusiness',
                    'name' => $businessName,
                    'telephone' => $settings->get('phone'),
                    'address' => $settings->get('address') ? str_replace("\n", ', ', $settings->get('address')) : null,
                ]),
                'sameAs' => array_values(array_filter([$settings->get('social_facebook'), $settings->get('social_instagram'), $settings->get('social_x'), $settings->get('social_youtube')])),
            ]);
        @endphp
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endif
</head>
<body class="@yield('body_class')">
    <a class="skip-link" href="#main">Skip to content</a>

    @include('partials.header')

    <main id="main">
        @yield('content')
    </main>

    @include('partials.footer')
    @stack('scripts')
</body>
</html>
