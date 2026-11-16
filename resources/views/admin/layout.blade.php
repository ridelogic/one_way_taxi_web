@php
    $nav = [
        ['Dashboard', 'admin.dashboard', 'grid', 'admin.dashboard'],
        'Booking Management',
        ['Bookings', 'admin.bookings.index', 'clipboard-check', 'admin.bookings.*'],
        ['Corporate Enquiries', 'admin.corporate-enquiries.index', 'building', 'admin.corporate-enquiries.*'],
        ['Partner Registrations', 'admin.partner-enquiries.index', 'users', 'admin.partner-enquiries.*'],
        ['Messages', 'admin.messages.index', 'inbox', 'admin.messages.*'],
        'Website CMS',
        ['Hero Banner', 'admin.slides.index', 'image', 'admin.slides.*'],
        ['Homepage Sections', 'admin.homepage.index', 'home', 'admin.homepage.*'],
        ['Pages', 'admin.pages.index', 'file-text', 'admin.pages.*'],
        ['Services', 'admin.services.index', 'briefcase', 'admin.services.*'],
        ['FAQs', 'admin.faqs.index', 'help-circle', 'admin.faqs.*'],
        ['Testimonials', 'admin.testimonials.index', 'message', 'admin.testimonials.*'],
        ['Media Library', 'admin.media.index', 'image', 'admin.media.*'],
        ['Navigation Menu', 'admin.navigation.index', 'list', 'admin.navigation.*'],
        'Fleet & Pricing',
        ['Routes', 'admin.routes.index', 'route', 'admin.routes.*'],
        ['Cities', 'admin.cities.index', 'map-pin', 'admin.cities.*'],
        ['Fleet & Vehicles', 'admin.vehicles.index', 'car', 'admin.vehicles.*'],
        ['Fare Rules', 'admin.fares.index', 'rupee', 'admin.fares.*'],
        'System',
        ['Settings', 'admin.settings.edit', 'sliders', 'admin.settings.*'],
        ['My Account', 'admin.account.edit', 'user', 'admin.account.*'],
    ];
    $pendingCount = \App\Models\Booking::where('status', 'pending')->count();
    $unreadCount = \App\Models\ContactMessage::whereNull('read_at')->count();
    $newCorporate = \App\Models\CorporateEnquiry::where('status', 'new')->count();
    $newPartners = \App\Models\PartnerEnquiry::where('status', 'new')->count();

    // Section label shown above the page title (the sidebar group of the current page).
    $crumb = 'Overview';
    $group = 'Overview';
    foreach ($nav as $item) {
        if (is_string($item)) { $group = $item; continue; }
        if (request()->routeIs($item[3])) { $crumb = $group; break; }
    }
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
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
</head>
<body class="admin">
    <a class="skip-link" href="#admin-main">Skip to content</a>
    <aside class="admin-sidebar" data-sidebar>
        @php
            $brandWords = preg_split('/\s+/', \Illuminate\Support\Str::upper(trim($settings->get('business_name'))));
            $brandLast = count($brandWords) > 1 ? array_pop($brandWords) : null;
        @endphp
        <a href="{{ route('admin.dashboard') }}" class="admin-brand">
            <svg class="admin-brand__mark" viewBox="0 0 80 68" aria-hidden="true" focusable="false"><path fill="#FBB70A" fill-rule="evenodd" d="M30 2h20a2 2 0 0 1 2 2v6h6.6c2.6 0 4.9 1.6 5.8 4l4.5 12.1 4.4 1.6A5 5 0 0 1 77 32.4V54a4 4 0 0 1-4 4h-1v6a3 3 0 0 1-3 3h-7a3 3 0 0 1-3-3v-6H21v6a3 3 0 0 1-3 3h-7a3 3 0 0 1-3-3v-6H7a4 4 0 0 1-4-4V32.4a5 5 0 0 1 3.7-4.7l4.4-1.6 4.5-12.1c.9-2.4 3.2-4 5.8-4H28V4a2 2 0 0 1 2-2Zm-8.3 14a2 2 0 0 0-1.9 1.3l-3.3 9h47l-3.3-9a2 2 0 0 0-1.9-1.3H21.7ZM14 35a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm52 0a5 5 0 1 0 0 10 5 5 0 0 0 0-10ZM27 41a2 2 0 0 0 0 4h26a2 2 0 0 0 0-4H27Z"/></svg>
            <span><span class="admin-brand__name">{{ implode(' ', $brandWords) }}@if ($brandLast) <span>{{ $brandLast }}</span>@endif</span><small>Admin CMS</small></span>
        </a>
        <nav aria-label="Admin">
            <ul class="admin-nav">
                @foreach ($nav as $item)
                    @if (is_string($item))
                        <li class="admin-nav__heading">{{ $item }}</li>
                    @else
                        @php([$label, $routeName, $icon, $pattern] = $item)
                        <li>
                            <a href="{{ route($routeName) }}" @class(['is-active' => request()->routeIs($pattern)]) @if (request()->routeIs($pattern)) aria-current="page" @endif>
                                <x-icon :name="$icon" :size="18" /> {{ $label }}
                                @if ($routeName === 'admin.bookings.index' && $pendingCount)
                                    <span class="admin-nav__count">{{ $pendingCount }}</span>
                                @elseif ($routeName === 'admin.messages.index' && $unreadCount)
                                    <span class="admin-nav__count">{{ $unreadCount }}</span>
                                @elseif ($routeName === 'admin.corporate-enquiries.index' && $newCorporate)
                                    <span class="admin-nav__count">{{ $newCorporate }}</span>
                                @elseif ($routeName === 'admin.partner-enquiries.index' && $newPartners)
                                    <span class="admin-nav__count">{{ $newPartners }}</span>
                                @endif
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </nav>
    </aside>
    <div class="admin-backdrop" data-sidebar-close aria-hidden="true"></div>

    <div class="admin-main">
        <header class="admin-topbar">
            <button type="button" class="admin-menu-btn" data-sidebar-toggle aria-label="Open menu" aria-expanded="false"><x-icon name="menu" :size="22" /></button>
            <div class="admin-topbar__heading">
                <span class="admin-topbar__crumb">{{ $crumb }}</span>
                <h1 class="admin-topbar__title">@yield('title', 'Admin')</h1>
            </div>
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

            @yield('content')
        </main>
    </div>

    @include('admin.partials.media-modal')
    <script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}" defer></script>
    @stack('scripts')
</body>
</html>
