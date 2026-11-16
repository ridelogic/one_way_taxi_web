{{-- Store buttons and QR code. Only rendered as links when real store URLs are configured. --}}
@props(['showQr' => true, 'light' => false])
@if ($appLinks->available())
    <div {{ $attributes->class(['flex flex-wrap items-center gap-5']) }}>
        <div class="flex flex-wrap gap-3">
            @if ($appLinks->playStore())
                <a class="inline-flex items-center gap-3 rounded-xl bg-ink px-5 py-2.5 text-white hover:bg-forest" href="{{ $appLinks->playStore() }}" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path fill="currentColor" d="M4 2.5v19a1 1 0 0 0 1.5.86l16-9.5a1 1 0 0 0 0-1.72l-16-9.5A1 1 0 0 0 4 2.5z"/></svg>
                    <span class="leading-tight"><small class="block text-[0.68rem] opacity-80">Get it on</small><span class="font-display font-bold">Google Play</span></span>
                </a>
            @endif
            @if ($appLinks->appStore())
                <a class="inline-flex items-center gap-3 rounded-xl bg-ink px-5 py-2.5 text-white hover:bg-forest" href="{{ $appLinks->appStore() }}" target="_blank" rel="noopener">
                    <x-icon name="smartphone" :size="24" />
                    <span class="leading-tight"><small class="block text-[0.68rem] opacity-80">Download on the</small><span class="font-display font-bold">App Store</span></span>
                </a>
            @endif
        </div>
        @if ($showQr && ($qr = $appLinks->qrSrc()))
            <figure class="m-0 text-center">
                <img src="{{ $qr }}" alt="QR code to download the {{ $settings->get('business_name') }} app" width="120" height="120" class="rounded-xl border border-line bg-white p-1.5">
                <figcaption class="mt-1 text-xs text-ink-soft">Scan to download</figcaption>
            </figure>
        @endif
    </div>
@else
    <p {{ $attributes->class(['flex items-center gap-2 rounded-xl border border-dashed px-4 py-3 text-sm', 'border-white/30 text-white/80' => $light, 'border-gold bg-gold-50 text-ink' => ! $light]) }}><x-icon name="smartphone" :size="18" /> {{ $settings->get('app_coming_soon_text') }}</p>
@endif
