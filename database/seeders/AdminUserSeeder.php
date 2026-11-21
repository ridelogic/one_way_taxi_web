<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Optional first administrator. The normal way for the website owner to get an account is the first-time setup
 * screen (/admin/setup, enabled by ADMIN_SETUP_TOKEN). This seeder only creates an admin when ADMIN_EMAIL and
 * ADMIN_PASSWORD are both set in .env (or when running the automated tests), and only if no administrator exists.
 * It never changes an existing user or password. To re-open setup later: `php artisan admin:setup-reset`.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('is_admin', true)->exists()) {
            return;
        }

        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            if (! app()->environment('testing')) {
                $this->command?->warn('No administrator created. Set ADMIN_SETUP_TOKEN in .env and open /admin/setup to create the owner account.');

                return;
            }
            // Automated tests only: a throwaway admin with a random password.
            $email = 'admin@onewaytaxi.test';
            $password = Str::password(16, symbols: false);
        }

        if (User::where('email', $email)->exists()) {
            $this->command?->warn("A user with {$email} already exists — it was not changed. Use /admin/setup or Forgot Password instead.");

            return;
        }

        User::create([
            'name' => 'Administrator',
            'email' => $email,
            'password' => $password,
            'is_admin' => true,
        ])->forceFill(['email_verified_at' => now()])->save();

        $this->command?->warn("Admin user created: {$email}");
    }
}
