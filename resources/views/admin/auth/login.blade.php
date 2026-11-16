@php
    $business = $settings->get('business_name');
    $words = preg_split('/\s+/', \Illuminate\Support\Str::upper(trim($business)));
    $lastWord = count($words) > 1 ? array_pop($words) : null;
    $brandTitle = implode(' ', $words);
    $features = [
        ['calendar', 'Bookings', 'Manage rides'],
        ['users', 'Drivers', 'Track & support'],
        ['route', 'Routes', 'Plan & pricing'],
        ['bar-chart', 'Reports', 'Real-time insights'],
    ];
@endphp
@php
    // Yellow taxi mark: the windscreen, grille and lamps are cut out so the background shows through.
    $taxiMark = '<svg class="auth-logo__mark" viewBox="0 0 80 68" aria-hidden="true" focusable="false"><path fill="#FBB70A" fill-rule="evenodd" d="M30 2h20a2 2 0 0 1 2 2v6h6.6c2.6 0 4.9 1.6 5.8 4l4.5 12.1 4.4 1.6A5 5 0 0 1 77 32.4V54a4 4 0 0 1-4 4h-1v6a3 3 0 0 1-3 3h-7a3 3 0 0 1-3-3v-6H21v6a3 3 0 0 1-3 3h-7a3 3 0 0 1-3-3v-6H7a4 4 0 0 1-4-4V32.4a5 5 0 0 1 3.7-4.7l4.4-1.6 4.5-12.1c.9-2.4 3.2-4 5.8-4H28V4a2 2 0 0 1 2-2Zm-8.3 14a2 2 0 0 0-1.9 1.3l-3.3 9h47l-3.3-9a2 2 0 0 0-1.9-1.3H21.7ZM14 35a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm52 0a5 5 0 1 0 0 10 5 5 0 0 0 0-10ZM27 41a2 2 0 0 0 0 4h26a2 2 0 0 0 0-4H27Z"/></svg>';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sign in · {{ $business }} Admin CMS</title>
    <link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
</head>
<body class="admin admin-auth">
    <a class="skip-link" href="#signin">Skip to sign in</a>
    <main class="auth">
        {{-- Background: navy promo panel, blue diagonal band and the curved edge of the light panel (desktop) --}}
        {{-- Drawn bottom-aligned at the stage scale (never stretched); the shapes continue above y 0 so taller screens get more of the same panel. --}}
        <svg class="auth-bg" viewBox="0 0 1672 941" preserveAspectRatio="xMinYMax meet" aria-hidden="true" focusable="false">
            <defs>
                <linearGradient id="authNavy" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1000 0 0 941 0 0)" x1="0" y1="0" x2="1" y2="0.55">
                    <stop offset="0" stop-color="#061B35"/><stop offset="1" stop-color="#0B3567"/>
                </linearGradient>
                <radialGradient id="authGlow" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1000 0 0 941 0 0)" cx="0.42" cy="0.34" r="0.5">
                    <stop offset="0" stop-color="#0E4C93" stop-opacity=".55"/><stop offset="1" stop-color="#0E4C93" stop-opacity="0"/>
                </radialGradient>
                {{-- City glow behind the skyline and the dark road along the bottom, sampled from the design --}}
                <radialGradient id="authCity" gradientUnits="userSpaceOnUse" cx="790" cy="455" r="270">
                    <stop offset="0" stop-color="#235487"/><stop offset=".55" stop-color="#14467D" stop-opacity=".7"/><stop offset="1" stop-color="#0E3D72" stop-opacity="0"/>
                </radialGradient>
                <linearGradient id="authFloor" gradientUnits="userSpaceOnUse" x1="0" y1="760" x2="0" y2="941">
                    <stop offset="0" stop-color="#08131F" stop-opacity="0"/><stop offset=".55" stop-color="#08131F" stop-opacity=".92"/><stop offset="1" stop-color="#08131F"/>
                </linearGradient>
                <linearGradient id="authBand" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#1A8FFB"/><stop offset="1" stop-color="#1683F5"/>
                </linearGradient>
            </defs>
            <rect y="-2000" width="1520" height="2941" fill="url(#authNavy)"/>
            <rect y="-2000" width="1520" height="2941" fill="url(#authGlow)"/>
            <rect width="1000" height="941" fill="url(#authCity)"/>
            <rect y="760" width="1000" height="181" fill="url(#authFloor)"/>
            <path fill="#EEF5FD" d="M1514.2 -2000H1672V941H838L922 642C930 618 928 598 921 585C910 566 899 556 903 532C907 507 906 482 894 463C881 444 864 437 865 418C866 400 872 388 876 376Z"/>
            <path fill="#FFFFFF" fill-opacity=".07" d="M922 642L838 941H790L884 652Z"/>
            <path fill="url(#authBand)" d="M1466.5 -2000H1514.2L876 376C866 412 840 417 836 386Z"/>
        </svg>

        <div class="auth-stage">
            {{-- Promotional panel --}}
            <aside class="auth-promo" aria-label="About the {{ $business }} Admin CMS">
                <div class="auth-logo auth-logo--light">
                    {!! $taxiMark !!}
                    <span class="auth-logo__text"><span class="auth-logo__name">{{ $brandTitle }}@if ($lastWord) <span>{{ $lastWord }}</span>@endif</span><span class="auth-logo__sub">Admin CMS</span></span>
                </div>
                <p class="auth-tabs" aria-hidden="true">Manage <span>|</span> Monitor <span>|</span> Grow</p>

                <h2 class="auth-headline">Manage Every<br>Ride in <span>One Place</span></h2>
                <p class="auth-lead">Control bookings, drivers, routes, fares and more<br>with a powerful and easy to use admin panel.</p>

                <ul class="auth-features">
                    @foreach ($features as [$icon, $title, $text])
                        <li>
                            <span class="auth-features__icon" aria-hidden="true"><x-icon :name="$icon" :size="28" :stroke="1.9" /></span>
                            <strong>{{ $title }}</strong>
                            <small>{{ $text }}</small>
                        </li>
                    @endforeach
                </ul>

                <img class="auth-scene" src="{{ asset('images/demo/admin-login-scene.webp') }}" alt="" width="930" height="561" decoding="async" fetchpriority="low">

                <div class="auth-footer">
                    <span class="auth-footer__line" aria-hidden="true"></span>
                    <p>Safe Rides <span aria-hidden="true">|</span> Happy Customers <span aria-hidden="true">|</span> Higher Business</p>
                </div>
            </aside>

            {{-- Sign-in card --}}
            <section class="auth-card" id="signin" aria-labelledby="signin-title">
                <div class="auth-logo auth-logo--card">
                    {!! $taxiMark !!}
                    <span class="auth-logo__text"><span class="auth-logo__name">{{ $brandTitle }}@if ($lastWord) <span>{{ $lastWord }}</span>@endif</span><span class="auth-logo__sub">Admin CMS</span></span>
                </div>

                <h1 id="signin-title" class="auth-card__title">Sign in to Admin Panel</h1>
                <p class="auth-card__lead">Enter your credentials to access the dashboard</p>

                @if (session('status'))
                    <div class="aalert aalert--success auth-alert" role="status">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('admin.login.store') }}" class="auth-form" novalidate>
                    @csrf

                    <div @class(['afield', 'auth-field', 'has-error' => $errors->has('email')])>
                        <label for="email">Email address <span class="auth-req" aria-hidden="true">*</span></label>
                        <div class="auth-input">
                            <span class="auth-input__icon" aria-hidden="true"><x-icon name="mail" :size="24" :stroke="1.7" /></span>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required aria-required="true" autocomplete="username" autofocus
                                   placeholder="Enter your email" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                        </div>
                        @error('email')<p class="afield__error" id="email-error">{{ $message }}</p>@enderror
                    </div>

                    <div @class(['afield', 'auth-field', 'has-error' => $errors->has('password')])>
                        <label for="password">Password <span class="auth-req" aria-hidden="true">*</span></label>
                        <div class="auth-input">
                            <span class="auth-input__icon" aria-hidden="true"><x-icon name="lock" :size="24" :stroke="1.7" /></span>
                            <input id="password" type="password" name="password" required aria-required="true" autocomplete="current-password"
                                   placeholder="Enter your password" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                            <button type="button" class="auth-input__toggle" data-password-toggle aria-controls="password" aria-pressed="false" aria-label="Show password" hidden>
                                <x-icon name="eye-off" :size="24" :stroke="1.7" class="auth-eye-off" /><x-icon name="eye" :size="24" :stroke="1.7" class="auth-eye" />
                            </button>
                        </div>
                        @error('password')<p class="afield__error" id="password-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="auth-row">
                        <label class="auth-check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> <span>Keep me signed in</span></label>
                        {{-- No self-service reset exists for admin accounts, so this explains how to get one instead of linking nowhere. --}}
                        <button type="button" class="auth-forgot" data-forgot-toggle aria-expanded="false" aria-controls="forgot-help">Forgot password?</button>
                    </div>
                    <p class="auth-help" id="forgot-help" hidden>Admin passwords are reset by your site administrator or developer — ask them to set a new one for <strong>your admin email</strong>. For security, there is no email reset link.</p>

                    <button type="submit" class="auth-submit">Sign in <x-icon name="arrow-right" :size="26" :stroke="2" /></button>
                </form>

                <div class="auth-or" aria-hidden="true"><span>or</span></div>
                <a class="auth-back" href="{{ route('home') }}"><x-icon name="arrow-right" :size="22" :stroke="2" class="flip" /> Back to website</a>
            </section>
        </div>
    </main>

    <script>
        (function () {
            // Show/hide password (progressive enhancement: the button only appears when JavaScript runs).
            var btn = document.querySelector('[data-password-toggle]');
            var input = document.getElementById('password');
            if (btn && input) {
                btn.hidden = false;
                btn.addEventListener('click', function () {
                    var show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    btn.setAttribute('aria-pressed', show ? 'true' : 'false');
                    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                    input.focus();
                });
            }
            // "Forgot password?" reveals how admin passwords are reset.
            var forgot = document.querySelector('[data-forgot-toggle]');
            var help = document.getElementById('forgot-help');
            if (forgot && help) {
                forgot.addEventListener('click', function () {
                    var open = help.hidden;
                    help.hidden = !open;
                    forgot.setAttribute('aria-expanded', open ? 'true' : 'false');
                });
            }
        })();
    </script>
</body>
</html>
