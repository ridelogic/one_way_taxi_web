<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminSetup;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * First-time admin account setup: the website owner creates their own administrator account.
 * Only available while AdminSetup::isOpen() (setup key configured, no owner account yet / re-opened once).
 */
class SetupController extends Controller
{
    public function create(): View
    {
        abort_unless(AdminSetup::isOpen(), 404);

        return view('admin.auth.setup');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(AdminSetup::isOpen(), 404);

        $data = $request->validate([
            'setup_key' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:190'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()],
        ]);

        if (! AdminSetup::tokenMatches($data['setup_key'])) {
            throw ValidationException::withMessages(['setup_key' => 'The setup key is not correct.']);
        }

        $email = mb_strtolower(trim($data['email']));
        // Never take over or change an existing account (or its password) from this screen.
        if (User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            throw ValidationException::withMessages(['email' => 'An account with this email already exists. Sign in, or use Forgot Password.']);
        }

        $user = DB::transaction(function () use ($data, $email) {
            // Re-check inside the transaction so two simultaneous submissions cannot both create an owner.
            abort_unless(AdminSetup::isOpen(), 404);

            $user = User::create([
                'name' => trim($data['name']),
                'email' => $email,
                'password' => Hash::make($data['password']),
                'is_admin' => true,
            ]);
            AdminSetup::complete();

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->route('admin.dashboard')->with('status', 'Your admin account is ready. Setup is now closed.');
    }
}
