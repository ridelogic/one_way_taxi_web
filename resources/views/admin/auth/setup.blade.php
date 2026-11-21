@extends('admin.auth.layout')

@section('title', 'Admin Account Setup')
@section('heading', 'Admin Account Setup')
@section('lead', 'Create the owner administrator account. You will sign in with this email and password.')

@section('card')
    <form method="POST" action="{{ route('admin.setup.store') }}" class="login-form" novalidate>
        @csrf

        <div @class(['login-field', 'has-error' => $errors->has('setup_key')])>
            <label for="setup_key" class="visually-hidden">Setup key</label>
            <span class="login-field__icon" aria-hidden="true"><x-icon name="shield-check" :size="20" /></span>
            <input id="setup_key" type="password" name="setup_key" required aria-required="true" autocomplete="off" autofocus
                   placeholder="Setup Key (from your developer)" @error('setup_key') aria-invalid="true" aria-describedby="setup-key-error" @enderror>
        </div>
        @error('setup_key')<p class="login-error" id="setup-key-error">{{ $message }}</p>@enderror

        <div @class(['login-field', 'has-error' => $errors->has('name')])>
            <label for="name" class="visually-hidden">Your name</label>
            <span class="login-field__icon" aria-hidden="true"><x-icon name="user" :size="20" /></span>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required aria-required="true" autocomplete="name" maxlength="100"
                   placeholder="Your Name" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
        </div>
        @error('name')<p class="login-error" id="name-error">{{ $message }}</p>@enderror

        <div @class(['login-field', 'has-error' => $errors->has('email')])>
            <label for="email" class="visually-hidden">Email address</label>
            <span class="login-field__icon" aria-hidden="true"><x-icon name="mail" :size="20" /></span>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required aria-required="true" autocomplete="username" maxlength="190"
                   placeholder="Your Email Address" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
        </div>
        @error('email')<p class="login-error" id="email-error">{{ $message }}</p>@enderror

        @include('admin.auth.partials.new-password')

        <button type="submit" class="login-submit">Create Admin Account <x-icon name="arrow-right" :size="20" :stroke="2.2" /></button>
    </form>
@endsection
