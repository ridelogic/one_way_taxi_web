<?php

use App\Support\Money;
use App\Support\Settings;

if (! function_exists('settings')) {
    /** Read a global site setting, or get the Settings service when called without a key. */
    function settings(?string $key = null, mixed $default = null): mixed
    {
        $settings = app(Settings::class);

        return $key === null ? $settings : $settings->get($key, $default);
    }
}

if (! function_exists('inr')) {
    /** Format a decimal amount as Indian Rupees with Indian digit grouping. */
    function inr(string|int|float|null $amount): string
    {
        return Money::format($amount);
    }
}
