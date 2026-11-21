@php
    // Simple six-item sidebar. Each item covers a group of screens; groups with several screens get a tab bar.
    $nav = [
        ['Dashboard', 'admin.dashboard', 'grid', ['admin.dashboard']],
        ['Bookings', 'admin.bookings.index', 'clipboard-check', ['admin.bookings.*', 'admin.corporate-enquiries.*', 'admin.partner-enquiries.*', 'admin.driver-enrollments.*', 'admin.messages.*']],
        ['District Management', 'admin.districts.index', 'map-pin', ['admin.districts.*', 'admin.district-cars.*', 'admin.district-routes.*', 'admin.district-faqs.*']],
        ['Homepage Content', 'admin.videos.index', 'home', ['admin.videos.*', 'admin.photos.*', 'admin.homepage.*']],
        ['Promotional Announcements', 'admin.announcements.index', 'bell', ['admin.announcements.*']],
        ['Vehicle Master', 'admin.vehicles.index', 'car', ['admin.vehicles.*', 'admin.fares.*']],
        ['Website Settings', 'admin.settings.edit', 'sliders', ['admin.settings.*', 'admin.pages.*', 'admin.services.*', 'admin.faqs.*', 'admin.testimonials.*', 'admin.navigation.*', 'admin.media.*', 'admin.cities.*', 'admin.routes.*', 'admin.account.*']],
    ];
    $groupTabs = [
        'Bookings' => [
            ['Bookings', 'admin.bookings.index', 'admin.bookings.*'],
            ['Corporate enquiries', 'admin.corporate-enquiries.index', 'admin.corporate-enquiries.*'],
            ['Driver registrations', 'admin.partner-enquiries.index', 'admin.partner-enquiries.*'],
            ['Driver enrollments', 'admin.driver-enrollments.index', 'admin.driver-enrollments.*'],
            ['Messages', 'admin.messages.index', 'admin.messages.*'],
        ],
        'Homepage Content' => [
            ['Videos', 'admin.videos.index', 'admin.videos.*'],
            ['Photos', 'admin.photos.index', 'admin.photos.*'],
            ['Homepage sections', 'admin.homepage.index', 'admin.homepage.*'],
        ],
        'Vehicle Master' => [
            ['Vehicles', 'admin.vehicles.index', 'admin.vehicles.*'],
            ['Fares', 'admin.fares.index', 'admin.fares.*'],
        ],
        'Website Settings' => [
            ['Business details', 'admin.settings.edit', 'admin.settings.*'],
            ['Pages', 'admin.pages.index', 'admin.pages.*'],
            ['Services', 'admin.services.index', 'admin.services.*'],
            ['General FAQs', 'admin.faqs.index', 'admin.faqs.*'],
            ['Testimonials', 'admin.testimonials.index', 'admin.testimonials.*'],
            ['Menus', 'admin.navigation.index', 'admin.navigation.*'],
            ['Image library', 'admin.media.index', 'admin.media.*'],
            ['Booking cities', 'admin.cities.index', 'admin.cities.*'],
            ['Intercity routes', 'admin.routes.index', 'admin.routes.*'],
            ['My account', 'admin.account.edit', 'admin.account.*'],
        ],
    ];
    $pendingCount = \App\Models\Booking::where('status', 'pending')->count();
    $unreadCount = \App\Models\ContactMessage::whereNull('read_at')->count();
    $newCorporate = \App\Models\CorporateEnquiry::where('status', 'new')->count();
    $newPartners = \App\Models\PartnerEnquiry::where('status', 'new')->count();

    // Section label shown above the page title (the sidebar item of the current page) and its tab bar.
    $crumb = 'Overview';
    $tabs = [];
    foreach ($nav as $item) {
        if (request()->routeIs(...$item[3])) { $crumb = $item[0]; $tabs = $groupTabs[$item[0]] ?? []; break; }
    }
    $tabCounts = [
        'admin.bookings.index' => $pendingCount, 'admin.messages.index' => $unreadCount,
        'admin.corporate-enquiries.index' => $newCorporate, 'admin.partner-enquiries.index' => $newPartners,
    ];
    $adminUser = auth()->user();
    $alerts = array_filter([
        ['pending booking', $pendingCount, route('admin.bookings.index', ['status' => 'pending']), 'clipboard-check'],
        ['unread message', $unreadCount, route('admin.messages.index', ['filter' => 'unread']), 'inbox'],
        ['new corporate enquiry', $newCorporate, route('admin.corporate-enquiries.index'), 'building'],
        ['new partner registration', $newPartners, route('admin.partner-enquiries.index'), 'users'],
    ], fn ($alert) => $alert[1] > 0);
    $alertTotal = array_sum(array_column($alerts, 1));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="media-edit-url" content="{{ route('admin.media.edit', '__ID__') }}">
    <title>@yield('title', 'Admin') · {{ $settings->get('business_name') }} CMS</title>
    <link rel="icon" href="{{ asset(config('branding.favicon_32')) }}" type="image/png" sizes="32x32">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
</head>
<body class="admin">
    <a class="skip-link" href="#admin-main">Skip to content</a>
    <aside class="admin-sidebar" data-sidebar>
        <a href="{{ route('admin.dashboard') }}" class="admin-brand" aria-label="{{ $settings->get('business_name') }} Admin CMS — dashboard">
            <span class="admin-logo-plate"><img src="{{ asset(config('branding.logo.path')) }}" alt="" width="{{ config('branding.logo.width') }}" height="{{ config('branding.logo.height') }}" decoding="async"></span>
            <small>Admin CMS</small>
        </a>
        <nav aria-label="Admin">
            <ul class="admin-nav">
                @foreach ($nav as [$label, $routeName, $icon, $patterns])
                    @php($active = request()->routeIs(...$patterns))
                    @php($badge = $routeName === 'admin.bookings.index' ? $pendingCount + $unreadCount + $newCorporate + $newPartners : 0)
                    <li>
                        <a href="{{ route($routeName) }}" @class(['is-active' => $active]) @if ($active) aria-current="page" @endif>
                            <x-icon :name="$icon" :size="18" /> {{ $label }}
                            @if ($badge)<span class="admin-nav__count">{{ $badge }}</span>@endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </aside>
    <div class="admin-backdrop" data-sidebar-close aria-hidden="true"></div>

    <div class="admin-main">
        <header class="admin-topbar">
            <button type="button" class="admin-menu-btn" data-sidebar-toggle aria-label="Open menu" aria-expanded="false"><x-icon name="menu" :size="22" /></button>
            <span class="admin-topbar__crumb">{{ $crumb }}</span>
            <div class="admin-topbar__actions">
                <form class="topbar-search" method="GET" action="{{ route('admin.bookings.index') }}" role="search">
                    <label class="visually-hidden" for="topbar-search">Search bookings</label>
                    <x-icon name="search" :size="18" class="topbar-search__icon" />
                    <input id="topbar-search" type="search" name="q" placeholder="Search bookings…" value="{{ request()->routeIs('admin.bookings.index') ? request('q') : '' }}" maxlength="100">
                </form>
                <details class="topbar-menu">
                    <summary class="topbar-icon" aria-label="Notifications{{ $alertTotal ? ': '.$alertTotal.' new' : '' }}">
                        <x-icon name="bell" :size="20" />
                        @if ($alertTotal)<span class="topbar-icon__badge" aria-hidden="true">{{ $alertTotal > 99 ? '99+' : $alertTotal }}</span>@endif
                    </summary>
                    <div class="topbar-menu__panel">
                        <p class="topbar-menu__title">Notifications</p>
                        @forelse ($alerts as [$label, $count, $href, $icon])
                            <a href="{{ $href }}"><x-icon :name="$icon" :size="16" /> {{ number_format($count) }} {{ \Illuminate\Support\Str::plural($label, $count) }}</a>
                        @empty
                            <p class="topbar-menu__empty">You're all caught up.</p>
                        @endforelse
                    </div>
                </details>
                @if ($adminUser)
                    <details class="topbar-menu topbar-menu--profile">
                        <summary class="topbar-profile" aria-label="Account menu">
                            <span class="topbar-profile__avatar" aria-hidden="true">{{ \Illuminate\Support\Str::upper(mb_substr($adminUser->name ?: $adminUser->email, 0, 1)) }}</span>
                            <span class="topbar-profile__meta"><span class="topbar-profile__name">{{ $adminUser->name ?: $adminUser->email }}</span><span class="topbar-profile__role">Admin</span></span>
                            <x-icon name="chevron-down" :size="16" class="topbar-profile__chevron" />
                        </summary>
                        <div class="topbar-menu__panel">
                            <a href="{{ route('admin.account.edit') }}"><x-icon name="user" :size="16" /> My account</a>
                            <a href="{{ route('home') }}" target="_blank" rel="noopener"><x-icon name="external-link" :size="16" /> View site <span class="visually-hidden">(opens in a new tab)</span></a>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit"><x-icon name="log-out" :size="16" /> Sign out</button>
                            </form>
                        </div>
                    </details>
                @endif
            </div>
        </header>

        <main id="admin-main" class="admin-content">
            <div class="page-title">
                <h1 class="page-title__heading">@yield('title', 'Admin')</h1>
                @hasSection('subtitle')<p class="page-title__sub">@yield('subtitle')</p>@endif
            </div>
            @if (session('status'))
                <div class="aalert aalert--success" role="status">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="aalert aalert--error" role="alert">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="aalert aalert--error" role="alert">
                    <strong>Please fix the following:</strong>
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            @if (count($tabs) > 1)
                <nav class="group-tabs" aria-label="{{ $crumb }}">
                    @foreach ($tabs as [$tabLabel, $tabRoute, $tabPattern])
                        <a href="{{ route($tabRoute) }}" @class(['is-active' => request()->routeIs($tabPattern)]) @if (request()->routeIs($tabPattern)) aria-current="page" @endif>{{ $tabLabel }}@if (!empty($tabCounts[$tabRoute]))<span class="group-tabs__count">{{ $tabCounts[$tabRoute] }}</span>@endif</a>
                    @endforeach
                </nav>
            @endif

            @yield('content')
        </main>
    </div>

    @include('admin.partials.media-modal')
    <script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}" defer></script>
    @stack('scripts')
</body>
</html>
