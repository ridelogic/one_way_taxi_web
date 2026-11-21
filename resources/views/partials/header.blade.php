{{-- Desktop utility bar: direct contact details (hidden below xl, where the menu panel lists them) --}}
@if ($settings->get('phone') || $settings->get('email') || $settings->get('business_hours'))
    <div class="topbar">
        <div class="container-wide flex h-10 items-center justify-between gap-6">
            <ul class="flex items-center gap-7">
                @if ($settings->get('phone'))
                    <li><a href="{{ $settings->telLink() }}" class="inline-flex items-center gap-2"><x-icon name="phone" :size="14" class="text-sun" /> {{ $settings->get('phone') }}</a></li>
                @endif
                @if ($settings->get('email'))
                    <li><a href="mailto:{{ $settings->get('email') }}" class="inline-flex items-center gap-2"><x-icon name="mail" :size="14" class="text-sun" /> {{ $settings->get('email') }}</a></li>
                @endif
            </ul>
            @if ($settings->get('business_hours'))
                <p class="inline-flex items-center gap-2"><x-icon name="clock" :size="14" class="text-sun" /> {{ $settings->get('business_hours') }}</p>
            @endif
        </div>
    </div>
@endif

<header class="site-header sticky top-0 z-50 border-b border-line bg-white" data-header>
    <div class="container-wide flex h-[72px] items-center gap-2 min-[360px]:gap-4 xl:h-[84px]">
        @include('partials.brand')

        <nav id="main-nav" class="nav-panel xl:ml-auto xl:flex xl:items-center" aria-label="Main navigation" data-nav>
            @php
                // Services dropdown: the public services (Bike/Auto Taxi are withdrawn site-wide, see Service::WITHDRAWN)
                // except Corporate Travel, which stays off the header menu (its page is kept).
                $headerServices = $navServices->reject(fn ($service) => $service->slug === 'corporate-taxi')->values();
            @endphp
            <ul class="flex flex-col gap-1 xl:flex-row xl:items-center xl:gap-1 2xl:gap-3">
                @foreach ($menus->get('header', collect()) as $item)
                    @php
                        $path = rtrim(parse_url($item->href(), PHP_URL_PATH) ?? '', '/');
                        $isServices = $path === rtrim(parse_url(route('services.index'), PHP_URL_PATH), '/') && $headerServices->isNotEmpty();
                        $active = $item->isCurrent();
                    @endphp
                    <li class="relative xl:flex xl:items-center">
                        <a href="{{ $item->href() }}" class="nav-link"
                           @if ($active) aria-current="page" @endif
                           @if ($item->open_in_new_tab) target="_blank" rel="noopener" @endif>{{ $item->label }}</a>
                        @if ($isServices)
                            {{-- Desktop: the label navigates to all services; this button opens the list of services --}}
                            <button type="button" class="nav-sub-toggle -ml-1" aria-expanded="false" aria-controls="nav-services" aria-label="Show services" data-submenu-toggle>
                                <x-icon name="chevron-down" :size="16" />
                            </button>
                            <ul id="nav-services" class="nav-sub space-y-0.5 pb-2 pl-4 xl:pb-2 xl:pl-2" data-submenu>
                                @foreach ($headerServices as $service)
                                    <li><a href="{{ route('services.show', $service->slug) }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-ink hover:bg-ivory hover:text-gold-600"><x-icon :name="$service->icon" :size="18" class="text-gold" /> {{ $service->title }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
            <div class="mt-6 grid gap-3 border-t border-line pt-6 xl:hidden">
                @if ($settings->get('phone'))
                    <a href="{{ $settings->telLink() }}" class="btn btn-outline"><x-icon name="phone" :size="18" /> {{ $settings->get('phone') }}</a>
                @endif
                <a href="{{ route('drivers.enroll') }}" class="btn btn-outline"><x-icon name="user" :size="18" /> Drive With Us</a>
                <a href="{{ route('booking.create') }}" class="btn btn-gold">Book a Taxi <x-icon name="arrow-right" :size="18" /></a>
            </div>
        </nav>

        <div class="ml-auto flex shrink-0 items-center gap-2 sm:gap-3 xl:ml-6 2xl:ml-8">
            @if ($settings->get('phone'))
                <a href="{{ $settings->telLink() }}" class="grid size-11 place-items-center rounded-full bg-gold-100 text-gold-600 xl:hidden" aria-label="Call {{ $settings->get('phone') }}"><x-icon name="phone" :size="20" /></a>
                <a href="{{ $settings->telLink() }}" class="header-call hidden xl:inline-flex" aria-label="Call {{ $settings->get('phone') }}">
                    <x-icon name="phone" :size="17" class="text-gold" />
                    <span class="leading-tight"><span class="block text-[0.68rem] font-semibold tracking-[0.12em] text-ink-soft uppercase">Call 24/7</span><span class="block font-display text-[0.92rem] font-bold whitespace-nowrap text-forest">{{ $settings->get('phone') }}</span></span>
                </a>
            @endif
            <a href="{{ route('drivers.enroll') }}" class="btn btn-outline hidden px-5 xl:inline-flex" title="Driver & taxi enrollment"><x-icon name="user" :size="18" /> Drive With Us</a>
            <a href="{{ route('booking.create') }}" class="btn btn-gold hidden px-6 sm:inline-flex">Book a Taxi <x-icon name="arrow-right" :size="18" /></a>
            <button class="grid size-11 place-items-center rounded-lg border border-line text-forest xl:hidden" type="button" aria-controls="main-nav" aria-expanded="false" aria-label="Open menu" data-nav-toggle>
                <x-icon name="menu" :size="24" class="[.nav-open_&]:hidden" />
                <x-icon name="x" :size="24" class="hidden [.nav-open_&]:block" />
            </button>
        </div>
    </div>
</header>
