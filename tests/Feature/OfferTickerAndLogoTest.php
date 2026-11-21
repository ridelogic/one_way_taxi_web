<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The scrolling offers ticker (homepage only, between header and hero) and the official logo on every surface. */
class OfferTickerAndLogoTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_ticker_is_admin_controlled_and_never_shown_by_default(): void
    {
        // Nothing is hardcoded: a fresh site shows no banner at all.
        $this->get('/')->assertOk()->assertDontSee('offer-ticker', false)->assertDontSee('Diwali Special Offer');
        $this->assertFileDoesNotExist(config_path('promotions.php'));

        \App\Models\PromoAnnouncement::create(['title' => 'Festival Season', 'message' => 'Book your taxi today!', 'is_enabled' => true]);
        $this->get('/')->assertOk()->assertSee('<section class="offer-ticker" aria-label="Current offers"', false);
        $this->get('/contact')->assertOk()->assertDontSee('offer-ticker', false); // homepage only
    }

    public function test_official_logo_and_icons_are_used_on_the_website_and_admin(): void
    {
        $logo = asset('images/one_way_trip_taxi_logo.png');
        foreach (['/', '/contact', '/puducherry'] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('src="'.$logo.'"', false)
                ->assertSee('href="'.asset('images/owt-favicon-32.png').'"', false)
                ->assertSee('href="'.asset('images/owt-apple-touch-icon.png').'"', false)
                ->assertDontSee('one_way_taxi_logo_trimmed.png', false);
        }

        $this->get(route('admin.login'))->assertOk()->assertSee('src="'.$logo.'"', false);
        $this->actingAs(User::where('is_admin', true)->firstOrFail())
            ->get(route('admin.dashboard'))->assertOk()->assertSee('src="'.$logo.'"', false);

        foreach (['images/one_way_trip_taxi_logo.png', 'images/owt-favicon-32.png', 'images/owt-favicon-512.png', 'images/owt-apple-touch-icon.png', 'favicon.ico'] as $file) {
            $this->assertFileExists(public_path($file));
        }
    }
}
