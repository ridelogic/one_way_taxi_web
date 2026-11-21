@extends('admin.auth.layout')

@section('title', 'Forgot Password')
@section('heading', 'Forgot Password')
@section('lead', 'Enter your admin email address and we will send you a link to set a new password.')

@section('card')
    @error('mail')<div class="aalert aalert--error" role="alert">{{ $message }}</div>@enderror

    <form method="POST" action="{{ route('admin.password.email') }}" class="login-form" novalidate>
        @csrf

        <div @class(['login-field', 'has-error' => $errors->has('email')])>
            <label for="email" class="visually-hidden">Email address</label>
            <span class="login-field__icon" aria-hidden="true"><x-icon name="mail" :size="20" /></span>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required aria-required="true" autocomplete="username" autofocus
                   placeholder="Admin Email Address" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
        </div>
        @error('email')<p class="login-error" id="email-error">{{ $message }}</p>@enderror

        <button type="submit" class="login-submit">Send Reset Link <x-icon name="send" :size="20" :stroke="2.2" /></button>
    </form>

    <p class="login-help" style="margin:16px 0 0">The link works once and expires after {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} minutes. Remembered your password? <a href="{{ route('admin.login') }}" style="font-weight:700;color:inherit">Back to login</a></p>
@endsection
