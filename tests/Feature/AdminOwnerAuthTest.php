<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\AdminSetup;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/** Client-owned admin access: first-time setup, login, forgot / reset password, and protection of the admin area. */
class AdminOwnerAuthTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'setup-key-for-tests-0123456789abcdef';

    private const PASSWORD = 'OwnerPass2026';

    protected function setUp(): void
    {
        parent::setUp();
        config(['admin.setup_token' => self::TOKEN]);
        RateLimiter::clear('login');
        // Start from a site with no accounts (other test classes may have seeded an admin; rolled back after each test).
        User::query()->delete();
        SiteSetting::whereIn('key', ['admin_setup_completed', 'admin_setup_reopened'])->delete();
    }

    /** @param array<string, string> $overrides */
    private function submitSetup(array $overrides = [])
    {
        return $this->post(route('admin.setup.store'), array_merge([
            'setup_key' => self::TOKEN, 'name' => 'Site Owner', 'email' => 'Owner@Example.com',
            'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD,
        ], $overrides));
    }

    private function owner(): User
    {
        return User::factory()->create(['email' => 'owner@example.com', 'password' => Hash::make(self::PASSWORD), 'is_admin' => true]);
    }

    public function test_1_owner_creates_the_first_admin_account_and_setup_then_closes(): void
    {
        $this->assertTrue(AdminSetup::isOpen());
        $this->get(route('admin.login'))->assertOk()->assertSee(route('admin.setup'), false); // link only while open
        $this->get(route('admin.setup'))->assertOk()->assertSee('Admin Account Setup')->assertSee('name="setup_key"', false);

        $this->submitSetup()->assertRedirect(route('admin.dashboard'));

        $user = User::firstOrFail();
        $this->assertSame('owner@example.com', $user->email);
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check(self::PASSWORD, $user->password));
        $this->assertNotSame(self::PASSWORD, $user->password); // stored hashed
        $this->assertAuthenticatedAs($user);

        // Setup is now permanently closed: no screen, no submissions, no link.
        auth()->logout();
        $this->assertFalse(AdminSetup::isOpen());
        $this->get(route('admin.setup'))->assertNotFound();
        $this->submitSetup(['email' => 'intruder@example.com'])->assertNotFound();
        $this->get(route('admin.login'))->assertDontSee(route('admin.setup'), false);
        $this->assertSame(1, User::count());
    }

    public function test_setup_needs_the_correct_key_strong_password_and_a_new_email(): void
    {
        $this->submitSetup(['setup_key' => 'wrong-key'])->assertSessionHasErrors('setup_key');
        $this->submitSetup(['password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors('password');
        $this->submitSetup(['password' => 'alllowercase123', 'password_confirmation' => 'alllowercase123'])->assertSessionHasErrors('password');
        $this->submitSetup(['password_confirmation' => 'Different2026x'])->assertSessionHasErrors('password');
        $this->assertSame(0, User::count());

        // An existing (non-admin) account is never taken over or given a new password.
        $existing = User::factory()->create(['email' => 'owner@example.com', 'is_admin' => false]);
        $hash = $existing->password;
        $this->submitSetup()->assertSessionHasErrors('email');
        $this->assertSame($hash, $existing->fresh()->password);
        $this->assertFalse($existing->fresh()->is_admin);
    }

    public function test_setup_is_disabled_without_a_configured_key_and_when_an_admin_already_exists(): void
    {
        config(['admin.setup_token' => null]);
        $this->get(route('admin.setup'))->assertNotFound();
        config(['admin.setup_token' => 'too-short']);
        $this->get(route('admin.setup'))->assertNotFound();

        config(['admin.setup_token' => self::TOKEN]);
        $this->owner();
        $this->get(route('admin.setup'))->assertNotFound();
    }

    public function test_server_side_reset_reopens_setup_once_without_touching_existing_admins(): void
    {
        $owner = $this->owner();
        $hash = $owner->password;

        $this->artisan('admin:setup-reset', ['--force' => true])->assertSuccessful();
        $this->assertTrue(AdminSetup::isOpen());

        $this->submitSetup(['email' => 'new-owner@example.com'])->assertRedirect(route('admin.dashboard'));
        $this->assertSame(2, User::where('is_admin', true)->count());
        $this->assertSame($hash, $owner->fresh()->password); // existing admin untouched
        $this->assertFalse(AdminSetup::isOpen());            // closed again after one account

        config(['admin.setup_token' => null]);
        $this->artisan('admin:setup-reset', ['--force' => true])->assertFailed(); // no key → cannot reopen
    }

    public function test_2_and_3_owner_logs_in_and_wrong_passwords_are_rejected_and_rate_limited(): void
    {
        $owner = $this->owner();

        $this->post(route('admin.login.store'), ['email' => 'owner@example.com', 'password' => 'WrongPass2026'])
            ->assertSessionHasErrors(['email' => 'These credentials do not match an administrator account.']);
        $this->assertGuest();

        $this->post(route('admin.login.store'), ['email' => 'owner@example.com', 'password' => self::PASSWORD])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($owner);
        $this->get(route('admin.dashboard'))->assertOk();
        auth()->logout();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.login.store'), ['email' => 'owner@example.com', 'password' => 'nope']);
        }
        $this->post(route('admin.login.store'), ['email' => 'owner@example.com', 'password' => self::PASSWORD])->assertStatus(429);
    }

    public function test_4_5_6_forgot_password_emails_an_expiring_link_and_the_new_password_works(): void
    {
        Notification::fake();
        $owner = $this->owner();
        $oldHash = $owner->password;

        $this->get(route('admin.login'))->assertSee('href="'.route('admin.password.request').'"', false);
        $this->get(route('admin.password.request'))->assertOk()->assertSee('Forgot Password')->assertSee('expires after 60 minutes');

        $this->post(route('admin.password.email'), ['email' => 'owner@example.com'])
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'reset link has been sent'));

        $token = null;
        Notification::assertSentTo($owner, ResetPassword::class, function (ResetPassword $n) use ($owner, &$token) {
            $token = $n->token;
            $url = $n->toMail($owner)->actionUrl;

            // Link points to the admin reset screen and the mail never contains a password.
            return str_starts_with($url, route('admin.password.reset', ['token' => $n->token]))
                && ! str_contains(json_encode($n->toMail($owner)->toArray()), self::PASSWORD);
        });

        $this->get(route('admin.password.reset', ['token' => $token, 'email' => 'owner@example.com']))->assertOk()->assertSee('Set a New Password');

        $new = 'BrandNew2026x';
        $this->post(route('admin.password.update'), ['token' => $token, 'email' => 'owner@example.com', 'password' => $new, 'password_confirmation' => $new])
            ->assertRedirect(route('admin.login'))->assertSessionHas('status');
        $this->assertNotSame($oldHash, $owner->fresh()->password);
        $this->assertTrue(Hash::check($new, $owner->fresh()->password));

        // 6: the new password signs in, the old one no longer does; the link cannot be used twice.
        $this->post(route('admin.login.store'), ['email' => 'owner@example.com', 'password' => self::PASSWORD])->assertSessionHasErrors('email');
        $this->post(route('admin.login.store'), ['email' => 'owner@example.com', 'password' => $new])->assertRedirect(route('admin.dashboard'));
        auth()->logout();
        $this->post(route('admin.password.update'), ['token' => $token, 'email' => 'owner@example.com', 'password' => 'Another2026x', 'password_confirmation' => 'Another2026x'])
            ->assertSessionHasErrors('email');
    }

    public function test_reset_links_expire_and_bad_tokens_are_rejected(): void
    {
        Notification::fake();
        $owner = $this->owner();
        $this->post(route('admin.password.email'), ['email' => 'owner@example.com']);
        $token = null;
        Notification::assertSentTo($owner, ResetPassword::class, function ($n) use (&$token) { $token = $n->token; return true; });

        $this->post(route('admin.password.update'), ['token' => 'not-the-token', 'email' => 'owner@example.com', 'password' => 'BrandNew2026x', 'password_confirmation' => 'BrandNew2026x'])
            ->assertSessionHasErrors(['email' => 'This reset link is invalid or has expired. Please request a new one.']);

        $this->travel(61)->minutes();
        $this->post(route('admin.password.update'), ['token' => $token, 'email' => 'owner@example.com', 'password' => 'BrandNew2026x', 'password_confirmation' => 'BrandNew2026x'])
            ->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check(self::PASSWORD, $owner->fresh()->password)); // unchanged
    }

    public function test_reset_emails_go_only_to_admins_with_the_same_answer_for_unknown_addresses(): void
    {
        Notification::fake();
        $customer = User::factory()->create(['email' => 'customer@example.com', 'is_admin' => false]);

        foreach (['customer@example.com', 'nobody@example.com'] as $email) {
            $this->post(route('admin.password.email'), ['email' => $email])
                ->assertSessionHas('status', fn ($s) => str_contains($s, 'If that email belongs to an admin account'));
        }
        Notification::assertNothingSent();
        Notification::assertNotSentTo($customer, ResetPassword::class);
    }

    public function test_password_reset_requests_are_rate_limited(): void
    {
        Notification::fake();
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.password.email'), ['email' => "x{$i}@example.com"]);
        }
        $this->post(route('admin.password.email'), ['email' => 'x9@example.com'])->assertStatus(429);
    }

    public function test_7_unauthorized_users_cannot_reach_the_admin_panel(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.settings.edit'))->assertRedirect(route('admin.login'));

        $customer = User::factory()->create(['is_admin' => false, 'password' => Hash::make(self::PASSWORD)]);
        $this->post(route('admin.login.store'), ['email' => $customer->email, 'password' => self::PASSWORD])->assertSessionHasErrors('email');
        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_seeder_never_creates_a_default_admin_outside_tests_or_changes_existing_users(): void
    {
        $this->owner();
        $hash = User::first()->password;
        $this->seed(\Database\Seeders\AdminUserSeeder::class);
        $this->assertSame(1, User::count());
        $this->assertSame($hash, User::first()->password);
        $this->assertNull(SiteSetting::where('key', 'admin_setup_completed')->value('value'));
    }
}
