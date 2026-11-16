@extends('admin.layout')

@section('title', 'My account')

@section('content')
    <form method="POST" action="{{ route('admin.account.update') }}" class="acard" style="max-width:640px">
        @csrf
        @method('PUT')
        <x-admin.input name="name" label="Name" :value="$user->name" required autocomplete="name" />
        <x-admin.input name="email" label="Email" type="email" :value="$user->email" required autocomplete="username" />
        <hr style="border:0;border-top:1px solid #e3e8f0;margin:8px 0 18px">
        <div class="afield @error('password') has-error @enderror">
            <label for="password">New password</label>
            <input id="password" type="password" name="password" autocomplete="new-password">
            <p class="afield__help">Leave blank to keep the current password. At least 10 characters with letters and numbers.</p>
            @error('password')<p class="afield__error">{{ $message }}</p>@enderror
        </div>
        <div class="afield">
            <label for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">
        </div>
        <div class="afield @error('current_password') has-error @enderror">
            <label for="current_password">Current password <span class="req">*</span></label>
            <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
            @error('current_password')<p class="afield__error">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="abtn abtn--primary">Save account</button>
        <p class="muted" style="font-size:.85rem;margin-top:14px">Last sign-in: {{ $user->last_login_at?->format('d M Y, H:i') ?? '—' }}</p>
    </form>
@endsection
