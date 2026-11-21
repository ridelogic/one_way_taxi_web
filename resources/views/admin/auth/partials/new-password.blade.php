{{-- New password + confirmation (first-time setup and password reset) --}}
<div @class(['login-field', 'has-error' => $errors->has('password')])>
    <label for="password" class="visually-hidden">New password</label>
    <span class="login-field__icon" aria-hidden="true"><x-icon name="lock" :size="20" /></span>
    <input id="password" type="password" name="password" required aria-required="true" autocomplete="new-password" minlength="10"
           placeholder="New Password" aria-describedby="password-rules @error('password') password-error @enderror" @error('password') aria-invalid="true" @enderror>
    <button type="button" class="login-field__toggle" data-password-toggle aria-controls="password" aria-pressed="false" aria-label="Show password" hidden>
        <x-icon name="eye" :size="20" class="login-eye" /><x-icon name="eye-off" :size="20" class="login-eye-off" />
    </button>
</div>
@error('password')<p class="login-error" id="password-error">{{ $message }}</p>@enderror

<div class="login-field">
    <label for="password_confirmation" class="visually-hidden">Confirm new password</label>
    <span class="login-field__icon" aria-hidden="true"><x-icon name="lock" :size="20" /></span>
    <input id="password_confirmation" type="password" name="password_confirmation" required aria-required="true" autocomplete="new-password"
           placeholder="Confirm New Password">
</div>
<p class="login-help" id="password-rules" style="margin:-4px 0 16px">At least 10 characters, with upper- and lower-case letters and a number.</p>
