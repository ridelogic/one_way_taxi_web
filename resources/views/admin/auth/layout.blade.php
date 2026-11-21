@php
    $business = $settings->get('business_name');
    $logo = asset(config('branding.logo.path'));
@endphp
{{-- Shared card shell for the admin sign-in screens (login, first-time setup, forgot / reset password). --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Admin Login') · {{ $business }}</title>
    <link rel="icon" href="{{ asset(config('branding.favicon_32')) }}" type="image/png" sizes="32x32">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
</head>
<body class="admin admin-login">
    <a class="skip-link" href="#signin">Skip to sign in</a>
    <main class="login">
        <section class="login-card" id="signin" aria-labelledby="signin-title">
            {{-- Red and yellow swoosh across the top-left corner (decorative) --}}
            <svg class="login-card__swoosh" viewBox="0 0 440 120" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                <path d="M0 0H440V8C300 14 150 40 0 112Z" fill="#FFC400"/>
                <path d="M0 0H440C300 4 150 24 0 92Z" fill="url(#loginRed)"/>
                <defs><linearGradient id="loginRed" x1="0" x2="1" y1="0" y2="0"><stop offset="0" stop-color="#A90000"/><stop offset="1" stop-color="#E60000"/></linearGradient></defs>
            </svg>

            <div class="login-card__body">
                <img class="login-card__logo" src="{{ $logo }}" alt="{{ $business }}" width="{{ config('branding.logo.width') }}" height="{{ config('branding.logo.height') }}" decoding="async">
                <h1 id="signin-title" class="login-card__title">@yield('heading', 'Admin Login')</h1>
                <p class="login-card__lead">@yield('lead', 'Sign in to manage '.$business)</p>

                @if (session('status'))
                    <div class="aalert aalert--success" role="status">{{ session('status') }}</div>
                @endif

                @yield('card')

                <a class="login-back" href="{{ route('home') }}"><x-icon name="arrow-right" :size="16" class="flip" /> Back to website</a>
            </div>

            {{-- City skyline, taxi and red/yellow road swooshes (decorative) --}}
            <svg class="login-card__scene" viewBox="0 0 440 170" preserveAspectRatio="xMidYMax slice" aria-hidden="true" focusable="false">
                <g fill="#FBD9D9">
                    <rect x="18" y="62" width="22" height="70"/><rect x="44" y="40" width="18" height="92"/><rect x="66" y="74" width="26" height="58"/>
                    <rect x="96" y="30" width="16" height="102"/><rect x="116" y="56" width="24" height="76"/><rect x="146" y="46" width="14" height="86"/>
                    <rect x="164" y="70" width="28" height="62"/><rect x="198" y="24" width="18" height="108"/><rect x="220" y="58" width="22" height="74"/>
                    <rect x="248" y="38" width="16" height="94"/><rect x="268" y="66" width="30" height="66"/><rect x="304" y="34" width="18" height="98"/>
                    <rect x="326" y="60" width="24" height="72"/><rect x="356" y="44" width="16" height="88"/><rect x="376" y="72" width="28" height="60"/>
                    <rect x="408" y="52" width="20" height="80"/>
                    <path d="M100 30l4-12 4 12Z"/><path d="M202 24l5-14 5 14Z"/><path d="M308 34l5-12 5 12Z"/>
                </g>
                <path d="M0 150C120 128 300 112 440 70V170H0Z" fill="#FFC400"/>
                <path d="M0 160C140 140 320 128 440 92V170H0Z" fill="url(#loginRoad)"/>
                <defs><linearGradient id="loginRoad" x1="0" x2="1" y1="0" y2="0"><stop offset="0" stop-color="#E60000"/><stop offset="1" stop-color="#8F0000"/></linearGradient></defs>
                {{-- Taxi --}}
                <g transform="translate(118 92)">
                    <ellipse cx="88" cy="50" rx="86" ry="6" fill="#000" opacity=".18"/>
                    <rect x="74" y="0" width="30" height="9" rx="2" fill="#FFC400" stroke="#C99600" stroke-width="1"/>
                    <path d="M8 36c2-9 8-13 20-15l26-3c8-8 18-12 34-12h22c14 0 24 5 34 14l18 3c10 2 14 7 14 14v6c0 3-2 5-5 5H12c-3 0-5-2-5-5Z" fill="#fff" stroke="#C8CDD3" stroke-width="1.5"/>
                    <path d="M60 19c7-6 15-9 27-9h14v12H56Z" fill="#2B3440"/><path d="M106 10h6c10 0 18 4 25 12h-31Z" fill="#2B3440"/>
                    <path d="M14 34h18M150 34h16" stroke="#E60000" stroke-width="3" stroke-linecap="round"/>
                    <rect x="8" y="30" width="6" height="4" rx="1" fill="#FFC400"/>
                    <circle cx="44" cy="45" r="11" fill="#1F2326"/><circle cx="44" cy="45" r="5" fill="#C8CDD3"/>
                    <circle cx="136" cy="45" r="11" fill="#1F2326"/><circle cx="136" cy="45" r="5" fill="#C8CDD3"/>
                </g>
            </svg>
        </section>
    </main>

    <script>
        (function () {
            // Show/hide password (progressive enhancement: the buttons only appear when JavaScript runs).
            document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
                var input = document.getElementById(btn.getAttribute('aria-controls'));
                if (!input) { return; }
                btn.hidden = false;
                btn.addEventListener('click', function () {
                    var show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    btn.setAttribute('aria-pressed', show ? 'true' : 'false');
                    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                    input.focus();
                });
            });
        })();
    </script>
</body>
</html>
