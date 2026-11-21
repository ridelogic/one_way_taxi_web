@extends('admin.auth.layout')

@section('title', 'Admin Login')

@section('card')
    <form method="POST" action="{{ route('admin.login.store') }}" class="login-form" novalidate>
        @csrf

        <div @class(['login-field', 'has-error' => $errors->has('email')])>
            <label for="email" class="visually-hidden">Email address</label>
            <span class="login-field__icon" aria-hidden="true"><x-icon name="mail" :size="20" /></span>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required aria-required="true" autocomplete="username" autofocus
                   placeholder="Email Address" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
        </div>
        @error('email')<p class="login-error" id="email-error">{{ $message }}</p>@enderror

        <div @class(['login-field', 'has-error' => $errors->has('password')])>
            <label for="password" class="visually-hidden">Password</label>
            <span class="login-field__icon" aria-hidden="true"><x-icon name="lock" :size="20" /></span>
            <input id="password" type="password" name="password" required aria-required="true" autocomplete="current-password"
                   placeholder="Password" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            <button type="button" class="login-field__toggle" data-password-toggle aria-controls="password" aria-pressed="false" aria-label="Show password" hidden>
                <x-icon name="eye" :size="20" class="login-eye" /><x-icon name="eye-off" :size="20" class="login-eye-off" />
            </button>
        </div>
        @error('password')<p class="login-error" id="password-error">{{ $message }}</p>@enderror

        <div class="login-row">
            <label class="login-check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> <span>Remember me</span></label>
            <a class="login-forgot" href="{{ route('admin.password.request') }}">Forgot Password?</a>
        </div>

        <button type="submit" class="login-submit">Login to Admin <x-icon name="arrow-right" :size="20" :stroke="2.2" /></button>
    </form>

    @if (\App\Support\AdminSetup::isOpen())
        {{-- Only while first-time setup is open (setup key configured and no owner account yet) --}}
        <p class="login-help" style="margin:16px 0 0">First time here? <a href="{{ route('admin.setup') }}" style="font-weight:700;color:inherit">Create the owner admin account</a></p>
    @endif
@endsection
