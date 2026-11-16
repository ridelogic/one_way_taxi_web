<header class="site-header sticky top-0 z-50 border-b border-line/70 bg-white transition-shadow" data-header>
    <div class="container-wide flex h-[72px] items-center gap-2 min-[360px]:gap-4">
        @include('partials.brand')

        <nav id="main-nav" class="nav-panel xl:ml-auto xl:flex xl:items-center" aria-label="Main navigation" data-nav>
            <ul class="flex flex-col gap-1 xl:flex-row xl:items-center xl:gap-1 2xl:gap-4">
                @foreach ($menus->get('header', collect()) as $item)
                    @php
                        $path = rtrim(parse_url($item->href(), PHP_URL_PATH) ?? '', '/');
                        $isServices = $path === rtrim(parse_url(route('services.index'), PHP_URL_PATH), '/') && $navServices->isNotEmpty();
                        $active = $item->isCurrent();
                    @endphp
                    <li class="group relative">
                        <a href="{{ $item->href() }}"
                           @class([
                               'relative flex items-center gap-1 px-2 py-3 text-[1.05rem] whitespace-nowrap transition xl:px-1.5 xl:py-2 xl:text-[0.94rem] 2xl:px-2 2xl:text-[0.98rem]',
                               'font-bold text-forest xl:after:absolute xl:after:right-2 xl:after:-bottom-1 xl:after:left-2 xl:after:h-[3px] xl:after:rounded-full xl:after:bg-gold' => $active,
                               'font-medium text-ink/75 hover:text-forest' => ! $active,
                           ])
                           @if ($active) aria-current="page" @endif
                           @if ($item->open_in_new_tab) target="_blank" rel="noopener" @endif>
                            {{ $item->label }}
                            @if ($isServices)<x-icon name="chevron-down" :size="16" class="hidden transition group-hover:rotate-180 xl:block" />@endif
                        </a>
                        @if ($isServices)
                            <ul class="space-y-0.5 pb-2 pl-4 xl:invisible xl:absolute xl:top-full xl:left-0 xl:w-64 xl:translate-y-2 xl:rounded-2xl xl:border xl:border-line xl:bg-white xl:p-2 xl:opacity-0 xl:shadow-lift xl:transition xl:group-focus-within:visible xl:group-focus-within:translate-y-0 xl:group-focus-within:opacity-100 xl:group-hover:visible xl:group-hover:translate-y-0 xl:group-hover:opacity-100">
                                @foreach ($navServices as $service)
                                    <li><a href="{{ route('services.show', $service->slug) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-ink hover:bg-mint hover:text-gold-600"><x-icon :name="$service->icon" :size="18" class="text-gold" /> {{ $service->title }}</a></li>
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
                <a href="{{ route('booking.create') }}" class="btn btn-gold btn-pill">Book a Taxi <x-icon name="arrow-right" :size="18" /></a>
            </div>
        </nav>

        <div class="ml-auto flex shrink-0 items-center gap-2 sm:gap-3 xl:ml-3 2xl:ml-4 2xl:gap-5">
            @if ($settings->get('phone'))
                <a href="{{ $settings->telLink() }}" class="hidden items-center gap-2.5 xl:flex" aria-label="Call {{ $settings->get('phone') }}">
                    <span class="grid size-11 place-items-center rounded-full bg-gold-100 text-gold-600"><x-icon name="phone" :size="20" class="fill-current" /></span>
                    <span class="leading-tight">
                        <span class="block text-xs font-medium text-ink-soft">Call 24/7</span>
                        <span class="block font-display text-base font-extrabold whitespace-nowrap text-forest 2xl:text-lg">{{ $settings->get('phone') }}</span>
                    </span>
                </a>
                <a href="{{ $settings->telLink() }}" class="grid size-11 place-items-center rounded-full bg-gold-100 text-gold-600 xl:hidden" aria-label="Call {{ $settings->get('phone') }}"><x-icon name="phone" :size="20" /></a>
            @endif
            <a href="{{ route('booking.create') }}" class="btn btn-gold btn-pill hidden px-6 sm:inline-flex">Book a Taxi <x-icon name="arrow-right" :size="18" /></a>
            <button class="grid size-11 place-items-center rounded-xl border border-line text-forest xl:hidden" type="button" aria-controls="main-nav" aria-expanded="false" aria-label="Open menu" data-nav-toggle>
                <x-icon name="menu" :size="24" class="[.nav-open_&]:hidden" />
                <x-icon name="x" :size="24" class="hidden [.nav-open_&]:block" />
            </button>
        </div>
    </div>
</header>
