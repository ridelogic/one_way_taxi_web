@extends('admin.auth.layout')

@section('title', 'Set a New Password')
@section('heading', 'Set a New Password')
@section('lead', 'Choose a new password for your admin account.')

@section('card')
    <form method="POST" action="{{ route('admin.password.update') }}" class="login-form" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div @class(['login-field', 'has-error' => $errors->has('email')])>
            <label for="email" class="visually-hidden">Email address</label>
            <span class="login-field__icon" aria-hidden="true"><x-icon name="mail" :size="20" /></span>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required aria-required="true" autocomplete="username"
                   placeholder="Admin Email Address" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
        </div>
        @error('email')<p class="login-error" id="email-error">{{ $message }}</p>@enderror

        @include('admin.auth.partials.new-password')

        <button type="submit" class="login-submit">Save New Password <x-icon name="check" :size="20" :stroke="2.2" /></button>
    </form>
@endsection
