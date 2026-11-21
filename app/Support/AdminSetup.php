<?php

namespace App\Support;

use App\Models\SiteSetting;
use App\Models\User;

/**
 * First-time administrator setup (/admin/setup) — state and rules in one place.
 *
 * Setup is open only when:
 *  - a setup token is configured (ADMIN_SETUP_TOKEN, see config/admin.php), and
 *  - either no administrator exists yet and setup was never completed, or an authorized person with
 *    server access re-opened it once with `php artisan admin:setup-reset`.
 * Completing setup closes it again. Existing administrators and passwords are never changed here.
 */
class AdminSetup
{
    private const COMPLETED = 'admin_setup_completed';

    private const REOPENED = 'admin_setup_reopened';

    public static function tokenConfigured(): bool
    {
        $token = (string) config('admin.setup_token');

        return strlen($token) >= (int) config('admin.setup_token_min_length', 24);
    }

    public static function isOpen(): bool
    {
        if (! self::tokenConfigured()) {
            return false;
        }

        if (self::flag(self::REOPENED)) {
            return true;
        }

        return ! self::flag(self::COMPLETED) && ! User::where('is_admin', true)->exists();
    }

    /** Constant-time comparison of the key the owner typed with the configured token. */
    public static function tokenMatches(?string $given): bool
    {
        return self::tokenConfigured() && is_string($given) && hash_equals((string) config('admin.setup_token'), $given);
    }

    /** Close setup after an administrator has been created. */
    public static function complete(): void
    {
        self::setFlag(self::COMPLETED, true);
        self::setFlag(self::REOPENED, false);
    }

    /** Allow setup once more (used by `php artisan admin:setup-reset`). Does not touch any user. */
    public static function reopen(): void
    {
        self::setFlag(self::REOPENED, true);
    }

    private static function flag(string $key): bool
    {
        return SiteSetting::where('key', $key)->value('value') === '1';
    }

    private static function setFlag(string $key, bool $on): void
    {
        SiteSetting::updateOrCreate(['key' => $key], ['value' => $on ? '1' : '0', 'group' => 'system']);
    }
}
