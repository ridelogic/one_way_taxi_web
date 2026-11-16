@php
    $socials = array_filter([
        'facebook' => $settings->get('social_facebook'),
        'instagram' => $settings->get('social_instagram'),
        'x-social' => $settings->get('social_x'),
        'youtube' => $settings->get('social_youtube'),
    ]);
    $socialLabels = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'x-social' => 'X (Twitter)', 'youtube' => 'YouTube'];
    // On touch screens the links get a taller tap area (padding offset by negative margin, so the spacing looks the same).
    $linkClass = 'text-white/75 hover:text-champagne max-lg:inline-block max-lg:-my-1 max-lg:py-1';
    $headingClass = 'font-display text-base font-bold text-white after:mt-2.5 after:block after:h-0.5 after:w-8 after:rounded-full after:bg-gold';
@endphp
<footer class="relative bg-navy text-white/80">
    <div class="container-site grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_1.2fr] lg:py-16">
        <div>
            @include('partials.brand', ['light' => true])
            <p class="mt-5 max-w-sm text-sm leading-relaxed text-white/70">{{ $settings->get('footer_about') }}</p>
            @if ($socials)
                <ul class="mt-5 flex gap-2" aria-label="Social media">
                    @foreach ($socials as $icon => $url)
                        <li><a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $socialLabels[$icon] }}" class="grid size-10 place-items-center rounded-full bg-white/10 text-white hover:bg-gold"><x-icon :name="$icon" :size="18" /></a></li>
                    @endforeach
                </ul>
            @endif
        </div>

        <nav aria-label="Quick links">
            <h2 class="{{ $headingClass }}">Quick Links</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                @foreach ($menus->get('footer_company', collect()) as $item)
                    <li><a href="{{ $item->href() }}" class="{{ $linkClass }}">{{ $item->label }}</a></li>
                @endforeach
            </ul>
        </nav>

        <nav aria-label="Our services">
            <h2 class="{{ $headingClass }}">Our Services</h2>
            <ul class="mt-4 space-y-2.5 text-sm">
                @foreach ($menus->get('footer_services', collect()) as $item)
                    <li><a href="{{ $item->href() }}" class="{{ $linkClass }}">{{ $item->label }}</a></li>
                @endforeach
            </ul>
        </nav>

        <div>
            <h2 class="{{ $headingClass }}">Contact Us</h2>
            <ul class="mt-4 space-y-3 text-sm">
                @if ($settings->get('phone'))
                    <li class="flex gap-3"><x-icon name="phone" :size="18" class="mt-0.5 shrink-0 text-champagne" /><a href="{{ $settings->telLink() }}" class="{{ $linkClass }}">{{ $settings->get('phone') }}</a></li>
                @endif
                @if ($settings->get('email'))
                    <li class="flex gap-3"><x-icon name="mail" :size="18" class="mt-0.5 shrink-0 text-champagne" /><a href="mailto:{{ $settings->get('email') }}" class="{{ $linkClass }} break-all">{{ $settings->get('email') }}</a></li>
                @endif
                @if ($settings->get('address'))
                    <li class="flex gap-3"><x-icon name="map-pin" :size="18" class="mt-0.5 shrink-0 text-champagne" /><span>{!! nl2br(e($settings->get('address'))) !!}</span></li>
                @endif
                @if ($settings->get('business_hours'))
                    <li class="flex gap-3"><x-icon name="clock" :size="18" class="mt-0.5 shrink-0 text-champagne" /><span>{{ $settings->get('business_hours') }}</span></li>
                @endif
            </ul>
            <a href="{{ route('booking.create') }}" class="btn btn-gold btn-sm mt-6">Book a Taxi <x-icon name="arrow-right" :size="16" /></a>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="container-site flex flex-col gap-3 py-5 text-sm text-white/60 sm:flex-row sm:items-center sm:justify-between">
            <p>{{ str_replace('{year}', now()->year, $settings->get('footer_copyright')) }}</p>
            <ul class="flex flex-wrap gap-x-5 gap-y-2">
                @foreach ($menus->get('footer_legal', collect()) as $item)
                    <li><a href="{{ $item->href() }}" class="hover:text-champagne max-lg:inline-block max-lg:-my-1 max-lg:py-1">{{ $item->label }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>

    @if ($settings->get('whatsapp'))
        <a class="fixed right-5 bottom-5 z-40 grid size-14 place-items-center rounded-full bg-[#25d366] text-white shadow-lift hover:scale-105" href="https://wa.me/{{ $settings->get('whatsapp') }}" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
            <x-icon name="whatsapp" :size="26" />
        </a>
    @endif
</footer>
