<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Creates the first administrator if none exists. Credentials come from ADMIN_EMAIL / ADMIN_PASSWORD
 * in .env when present; otherwise a random password is generated and printed once.
 * Use `php artisan admin:create` to add administrators or reset a password later.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('is_admin', true)->exists()) {
            return;
        }

        $email = env('ADMIN_EMAIL', 'admin@onewaytaxi.test');
        $password = env('ADMIN_PASSWORD') ?: Str::password(16, symbols: false);

        User::updateOrCreate(['email' => $email], [
            'name' => 'Administrator',
            'password' => $password,
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->command?->warn("Admin user created: {$email}");
        if (! env('ADMIN_PASSWORD')) {
            $this->command?->warn("Generated password (shown once — change it after logging in): {$password}");
        }
    }
}
