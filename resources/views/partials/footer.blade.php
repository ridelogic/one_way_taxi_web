@php
    $socials = array_filter([
        'facebook' => $settings->get('social_facebook'),
        'instagram' => $settings->get('social_instagram'),
        'x-social' => $settings->get('social_x'),
        'youtube' => $settings->get('social_youtube'),
    ]);
    $socialLabels = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'x-social' => 'X (Twitter)', 'youtube' => 'YouTube'];
    // Light footer in the logo colours: ivory #FFF9F0 / cream #FFF1D6, red #D71920, yellow #FFC400.
@endphp
{{-- All 38 Tamil Nadu district pages plus Union Territory pages (Puducherry): its own section directly above the footer (on every page) --}}
@if (isset($footerDistricts) && $footerDistricts->isNotEmpty())
    @php
        $tnDistrictCount = $footerDistricts->filter->isTamilNadu()->count();
        $territoryNames = $footerDistricts->reject->isTamilNadu()->pluck('name');
    @endphp
    <nav class="tn-districts" aria-labelledby="tn-districts-title">
        <div class="container-site py-8 lg:py-9">
            <div class="tn-districts__head">
                <div>
                    <span class="tn-districts__eyebrow">All {{ $tnDistrictCount }} districts{{ $territoryNames->isNotEmpty() ? ' + '.$territoryNames->join(', ').' (UT)' : '' }}</span>
                    <h2 id="tn-districts-title" class="tn-districts__title">Taxi Services Across Tamil Nadu{{ $territoryNames->isNotEmpty() ? ' & '.$territoryNames->join(', ') : '' }}</h2>
                </div>
                <a href="{{ route('districts.index') }}" class="tn-districts__all">View all districts <x-icon name="arrow-right" :size="15" /></a>
            </div>
            <ul class="tn-districts__grid">
                @foreach ($footerDistricts as $district)
                    <li>
                        <a href="{{ $district->url() }}" class="tn-district">
                            <span class="tn-district__icon" aria-hidden="true"><x-icon name="map-pin" :size="15" /></span>
                            <span class="tn-district__name"><span class="sr-only">Taxi in </span>{{ $district->name }}@unless ($district->isTamilNadu()) <span class="tn-district__tag">UT</span>@endunless</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>
@endif
<footer class="site-footer">
    {{-- Red and yellow swoosh, top-left corner (decorative) --}}
    <svg class="site-footer__swoosh" viewBox="0 0 400 60" preserveAspectRatio="none" aria-hidden="true" focusable="false">
        <path d="M0 0H400C300 4 150 20 0 60Z" fill="#FFC400"/>
        <path d="M0 0H330C240 3 120 16 0 46Z" fill="#E60000"/>
    </svg>

    @php
        // Small service icons, matched on the link's path (not the host, which may contain "localhost"). Fallback: car.
        $serviceIcon = function (string $href): string {
            $path = (string) parse_url($href, PHP_URL_PATH);

            return match (true) {
                str_contains($path, 'one-way') => 'car',
                str_contains($path, 'outstation') || str_contains($path, 'round') => 'refresh',
                str_contains($path, 'local') => 'map-pin',
                str_contains($path, 'rental') => 'calendar',
                str_contains($path, 'airport') => 'plane',
                default => 'car',
            };
        };
    @endphp

    <div class="container-site site-footer__grid">
        <div class="site-footer__brand">
            @include('partials.brand')
            <p class="site-footer__tagline">Your Journey. Our Priority.</p>
            <h2 class="sr-only">About {{ $settings->get('business_name') }}</h2>
            <p class="site-footer__about">{{ $settings->get('footer_about') }}</p>
            <ul class="site-footer__trust" aria-label="Why ride with us">
                <li><x-icon name="shield-check" :size="22" /><span>Safe &amp; Secure</span></li>
                <li><x-icon name="user" :size="22" /><span>Verified Drivers</span></li>
                <li><x-icon name="clock" :size="22" /><span>On-Time Service</span></li>
            </ul>
            @if ($socials)
                <ul class="mt-3 flex gap-2" aria-label="Social media">
                    @foreach ($socials as $icon => $url)
                        <li><a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $socialLabels[$icon] }}" class="footer-social"><x-icon :name="$icon" :size="16" /></a></li>
                    @endforeach
                </ul>
            @endif
        </div>

        <nav aria-label="Quick links" class="site-footer__col">
            <h2 class="footer-heading">Quick Links</h2>
            <ul class="footer-list">
                @foreach ($menus->get('footer_company', collect()) as $item)
                    <li><a href="{{ $item->href() }}" class="footer-link footer-link--icon"><x-icon name="chevron-right" :size="15" :stroke="2.6" class="footer-link__icon" />{{ $item->label }}</a></li>
                @endforeach
            </ul>
        </nav>

        <nav aria-label="Taxi services" class="site-footer__col">
            <h2 class="footer-heading">Taxi Services</h2>
            <ul class="footer-list footer-list--services">
                @foreach ($menus->get('footer_services', collect()) as $item)
                    <li><a href="{{ $item->href() }}" class="footer-link footer-link--icon"><x-icon :name="$serviceIcon($item->href())" :size="18" class="footer-link__icon" />{{ $item->label }}</a></li>
                @endforeach
            </ul>
        </nav>

        <div class="site-footer__col site-footer__col--contact">
            <div class="site-footer__contact">
                <h2 class="footer-heading">Contact Us</h2>
                <ul class="site-footer__contact-list">
                    @if ($settings->get('phone'))
                        <li><span class="footer-icon" aria-hidden="true"><x-icon name="phone" :size="15" /></span><a href="{{ $settings->telLink() }}" class="footer-link footer-link--strong">{{ $settings->get('phone') }}</a></li>
                    @endif
                    @if ($settings->get('email'))
                        <li><span class="footer-icon" aria-hidden="true"><x-icon name="mail" :size="15" /></span><a href="mailto:{{ $settings->get('email') }}" class="footer-link break-all">{{ $settings->get('email') }}</a></li>
                    @endif
                    @if ($settings->get('address'))
                        <li><span class="footer-icon" aria-hidden="true"><x-icon name="map-pin" :size="15" /></span><span class="text-[#151515]">{{ str_replace("\n", ', ', trim($settings->get('address'))) }}</span></li>
                    @endif
                    @if ($settings->get('business_hours'))
                        <li><span class="footer-icon" aria-hidden="true"><x-icon name="clock" :size="15" /></span><span class="site-footer__hours">{{ $settings->get('business_hours') }}</span></li>
                    @endif
                </ul>
                <a href="{{ route('booking.create') }}" class="footer-cta">Book a Taxi <x-icon name="arrow-right" :size="16" /></a>
            </div>
        </div>
    </div>

    <div class="site-footer__bar">
        <div class="container-site site-footer__bar-inner">
            {{-- White car with a yellow swoosh (decorative) --}}
            <svg class="site-footer__car" viewBox="0 0 120 40" aria-hidden="true" focusable="false">
                <path d="M4 36C30 30 70 30 116 22C86 34 46 38 4 36Z" fill="#FFC400"/>
                <path d="M8 24C24 14 40 8 62 8c10 0 18 4 26 10l14 2c4 1 6 3 6 6v2H92a7 7 0 0 0-14 0H48a7 7 0 0 0-14 0H22" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M46 12h14l-2 7H40Zm18 0h8l8 7H62Z" fill="#fff" opacity=".9"/>
                <circle cx="41" cy="28" r="4" fill="none" stroke="#fff" stroke-width="2.4"/>
                <circle cx="85" cy="28" r="4" fill="none" stroke="#fff" stroke-width="2.4"/>
                <path d="M2 20h14M6 15h10" stroke="#fff" stroke-width="2" stroke-linecap="round" opacity=".8"/>
            </svg>
            <p class="site-footer__copy">{{ str_replace('{year}', now()->year, $settings->get('footer_copyright')) }}</p>
            <ul class="site-footer__legal-list">
                @foreach ($menus->get('footer_legal', collect()) as $item)
                    <li><a href="{{ $item->href() }}" class="site-footer__legal">{{ $item->label }}</a></li>
                @endforeach
            </ul>
        </div>
        <svg class="site-footer__bar-swoosh" viewBox="0 0 260 60" preserveAspectRatio="none" aria-hidden="true" focusable="false">
            <path d="M260 0V60H40C130 52 210 30 260 0Z" fill="#FFC400"/>
            <path d="M260 14V60H110C180 50 230 34 260 14Z" fill="#E60000"/>
        </svg>
    </div>

    @if ($settings->get('whatsapp'))
        <a class="fixed right-5 bottom-5 z-40 grid size-14 place-items-center rounded-full bg-[#25d366] text-white shadow-lift" href="https://wa.me/{{ $settings->get('whatsapp') }}" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
            <x-icon name="whatsapp" :size="26" />
        </a>
    @endif
</footer>
