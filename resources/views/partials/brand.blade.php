@php
    $light = $light ?? false;
    $logo = $settings->media('logo_media_id');
    $words = preg_split('/\s+/', \Illuminate\Support\Str::upper(trim($settings->get('business_name'))));
    $lastWord = count($words) > 1 ? array_pop($words) : null;
@endphp
{{-- Below 360px (e.g. 320px phones) the mark and name step down slightly so the header's call and menu buttons still fit. --}}
<a href="{{ route('home') }}" class="group inline-flex shrink-0 items-center gap-2 min-[360px]:gap-2.5" aria-label="{{ $settings->get('business_name') }} — home">
    @if ($logo)
        <img src="{{ $logo->safeUrl() }}" alt="" class="h-10 w-auto object-contain min-[360px]:h-12" width="{{ $logo->width ?? 48 }}" height="{{ $logo->height ?? 48 }}">
    @else
        {{-- Built-in mark: gold taxi under a green swoosh --}}
        <svg class="h-8 w-[3.3rem] shrink-0 min-[360px]:h-9 min-[360px]:w-[3.8rem] sm:h-11 sm:w-[4.6rem]" viewBox="0 0 92 54" aria-hidden="true">
            <path d="M6 30C14 9 38 2 58 4c14 1.4 24 8 30 17" fill="none" stroke="var(--color-forest)" stroke-width="5.5" stroke-linecap="round"/>
            <path d="M8 42c0-4.5 2.6-7.6 7-8.5l10.5-2.2 8.8-7.6c2.4-2 5.3-3.1 8.4-3.1h15.6c3.6 0 6.8 1.4 9.3 4l7 7.2 6.4 1.2c4 .8 6.5 4 6.5 8V44c0 1.7-1.3 3-3 3H11c-1.7 0-3-1.3-3-3z" fill="var(--color-gold)"/>
            <path d="M33 31.5l6.3-5.4c1.4-1.2 3.2-1.9 5-1.9H50v7.3zM54 24.2h4.4c2.1 0 4.1.9 5.6 2.4l4.6 4.9H54z" fill="#fff"/>
            <rect x="40" y="16.5" width="12" height="4.6" rx="1.4" fill="var(--color-forest)"/>
            <circle cx="24" cy="46" r="6.6" fill="var(--color-forest)"/><circle cx="24" cy="46" r="2.6" fill="#fff"/>
            <circle cx="70" cy="46" r="6.6" fill="var(--color-forest)"/><circle cx="70" cy="46" r="2.6" fill="#fff"/>
        </svg>
    @endif
    <span class="flex flex-col leading-none">
        <span @class(['font-display text-[1.05rem] font-extrabold min-[360px]:text-[1.2rem] sm:text-[1.6rem] tracking-tight [word-spacing:0.12em]', 'text-white' => $light, 'text-forest' => ! $light])>{{ implode(' ', $words) }}@if ($lastWord) <span @class(['text-sun' => $light, 'text-gold' => ! $light])>{{ $lastWord }}</span>@endif</span>
        @if ($settings->get('tagline'))
            <span @class(['mt-1 text-[0.68rem] font-medium tracking-wide max-[359px]:max-w-[8rem] max-[359px]:leading-tight sm:text-[0.8rem]', 'text-white/70' => $light, 'text-ink-soft' => ! $light])>{{ $settings->get('tagline') }}</span>
        @endif
    </span>
</a>
