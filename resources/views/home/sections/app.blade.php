@php
    // Store buttons and the QR code only link somewhere real once Admin → Settings → Mobile app has a store URL.
    // Until then they are shown as clearly marked, non-clickable "coming soon" placeholders.
    $play = $appLinks->playStore();
    $ios = $appLinks->appStore();
    $qr = $appLinks->qrSrc();
    $brand = \Illuminate\Support\Str::upper($settings->get('business_name'));
@endphp
<section class="home-section bg-ivory" id="app" aria-labelledby="app-title">
    <div class="container-site">
        <div class="app-banner grid items-center gap-10 lg:grid-cols-[0.9fr_1.2fr_0.6fr]">
            <div aria-hidden="true" class="pointer-events-none absolute -right-20 -bottom-24 size-80 rounded-full bg-gold/30 blur-3xl"></div>

            {{-- Phone mockups (illustration of the booking flow) --}}
            <div class="relative hidden h-72 justify-center sm:flex lg:h-80" aria-hidden="true">
                <div class="phone-mock absolute top-6 left-1/2 -translate-x-[85%] -rotate-6">
                    <div class="mt-3 flex items-center gap-1.5 font-display text-[0.6rem] font-extrabold text-forest"><x-icon name="taxi" :size="14" class="text-gold" /> {{ $brand }}</div>
                    <div class="mt-3 rounded-xl bg-mint p-2 text-[0.6rem] text-ink-soft">
                        <div class="rounded-md bg-white px-2 py-1.5">Pickup location</div>
                        <div class="mt-1.5 rounded-md bg-white px-2 py-1.5">Drop location</div>
                        <div class="mt-2 rounded-md bg-gold py-1.5 text-center font-bold text-white">Search Taxi</div>
                    </div>
                    <div class="mt-2 space-y-1.5"><div class="h-7 rounded-lg bg-mint"></div><div class="h-7 rounded-lg bg-mint"></div><div class="h-7 rounded-lg bg-mint"></div></div>
                </div>
                <div class="phone-mock absolute top-0 left-1/2 -translate-x-[10%] rotate-3">
                    <div class="mt-3 font-display text-[0.62rem] font-extrabold text-forest">Choose a vehicle</div>
                    <div class="mt-2 space-y-1.5 text-[0.58rem]">
                        @foreach (['Hatchback', 'Sedan', 'SUV', 'Innova'] as $car)
                            <div @class(['flex items-center gap-1.5 rounded-lg px-2 py-1.5', 'bg-gold text-white' => $loop->index === 1, 'bg-mint text-ink' => $loop->index !== 1])><x-icon name="car" :size="12" /> {{ $car }}</div>
                        @endforeach
                    </div>
                    <div class="mt-2 rounded-md bg-forest py-1.5 text-center text-[0.58rem] font-bold text-white">Confirm &amp; Ride</div>
                </div>
            </div>

            <div class="relative">
                <span class="font-display text-sm font-bold tracking-[0.14em] text-champagne uppercase">{{ $section->get('eyebrow') ?: 'Mobile app' }}</span>
                <h2 id="app-title" class="mt-2 text-[1.9rem] leading-tight font-extrabold text-white sm:text-[2.3rem]">{{ $section->title }}</h2>
                @if ($section->subtitle)<p class="mt-3 text-white/80 sm:text-lg">{{ $section->subtitle }}</p>@endif

                <div class="mt-7 flex flex-wrap gap-3">
                    @if ($play)
                        <a class="store-button" href="{{ $play }}" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path fill="currentColor" d="M4 2.5v19a1 1 0 0 0 1.5.86l16-9.5a1 1 0 0 0 0-1.72l-16-9.5A1 1 0 0 0 4 2.5z"/></svg>
                            <span class="leading-tight"><small class="block text-[0.66rem] opacity-80">Get it on</small><span class="font-display font-bold">Google Play</span></span>
                        </a>
                    @else
                        <span class="store-button store-button--pending" aria-disabled="true">
                            <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"><path fill="currentColor" d="M4 2.5v19a1 1 0 0 0 1.5.86l16-9.5a1 1 0 0 0 0-1.72l-16-9.5A1 1 0 0 0 4 2.5z"/></svg>
                            <span class="leading-tight"><small class="block text-[0.66rem] opacity-80">Coming soon to</small><span class="font-display font-bold">Google Play</span></span>
                        </span>
                    @endif
                    @if ($ios)
                        <a class="store-button" href="{{ $ios }}" target="_blank" rel="noopener">
                            <x-icon name="smartphone" :size="24" />
                            <span class="leading-tight"><small class="block text-[0.66rem] opacity-80">Download on the</small><span class="font-display font-bold">App Store</span></span>
                        </a>
                    @else
                        <span class="store-button store-button--pending" aria-disabled="true">
                            <x-icon name="smartphone" :size="24" />
                            <span class="leading-tight"><small class="block text-[0.66rem] opacity-80">Coming soon to the</small><span class="font-display font-bold">App Store</span></span>
                        </span>
                    @endif
                </div>
                @unless ($play || $ios)
                    <p class="mt-4 text-sm text-white/70">{{ $settings->get('app_coming_soon_text') }} Until then, <a href="{{ route('booking.create') }}" class="font-semibold text-white underline underline-offset-2">book online</a>@if ($settings->get('phone')) or call <a href="{{ $settings->telLink() }}" class="font-semibold text-white underline underline-offset-2">{{ $settings->get('phone') }}</a>@endif.</p>
                @endunless
            </div>

            <div class="relative flex justify-center lg:justify-end">
                @if ($qr)
                    <figure class="m-0 rounded-2xl bg-white p-3 text-center shadow-lift">
                        <img src="{{ $qr }}" alt="QR code to download the {{ $settings->get('business_name') }} app" width="132" height="132" class="size-[8.25rem]">
                        <figcaption class="mt-1.5 text-xs font-semibold text-forest">Scan to download</figcaption>
                    </figure>
                @else
                    <div class="grid size-40 place-items-center rounded-2xl border-2 border-dashed border-white/35 bg-white/5 p-4 text-center text-white/75">
                        <x-icon name="qr-code" :size="40" class="text-champagne" />
                        <span class="text-xs leading-snug">QR code appears here once the app is published</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
