<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Admin "Forgot Password": emails a single-use reset link (Laravel password broker; it expires after
 * auth.passwords.users.expire minutes) to administrator accounts only. The old password is never sent or shown.
 */
class PasswordResetController extends Controller
{
    /** Same answer whether or not the email belongs to an admin, so the form cannot be used to find accounts. */
    private const SENT = 'If that email belongs to an admin account, a password reset link has been sent to it. Please check your inbox.';

    public function request(): View
    {
        return view('admin.auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'string', 'email', 'max:190']]);

        try {
            // is_admin narrows the lookup to administrators; everyone else gets the same neutral answer.
            Password::sendResetLink(['email' => mb_strtolower(trim($request->string('email'))), 'is_admin' => true]);
        } catch (Throwable $e) {
            Log::error('Admin password reset email could not be sent: '.$e->getMessage());

            return back()->withInput()->withErrors(['mail' => 'The reset email could not be sent right now. Please try again later or contact your site administrator.']);
        }

        return back()->with('status', self::SENT);
    }

    public function edit(Request $request, string $token): View
    {
        return view('admin.auth.reset-password', ['token' => $token, 'email' => (string) $request->query('email', '')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:190'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->mixedCase()->numbers()],
        ]);

        $status = Password::reset(
            [
                'email' => mb_strtolower(trim($request->string('email'))),
                'password' => $request->input('password'),
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $request->input('token'),
                'is_admin' => true,
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60), // signs out "remember me" sessions using the old password
                ])->save();
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => $status === Password::INVALID_TOKEN
                    ? 'This reset link is invalid or has expired. Please request a new one.'
                    : 'We could not reset the password for that email. Please request a new reset link.',
            ]);
        }

        return redirect()->route('admin.login')->with('status', 'Your password has been changed. Please sign in with your new password.');
    }
}
