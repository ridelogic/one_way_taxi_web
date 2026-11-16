<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\City;
use App\Models\Faq;
use App\Models\MediaAsset;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\TaxiRoute;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\VehicleCategory;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User
    {
        return User::where('is_admin', true)->firstOrFail();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/admin/bookings')->assertRedirect(route('admin.login'));
        $this->post('/admin/settings', ['_method' => 'PUT'])->assertRedirect(route('admin.login'));
    }

    public function test_non_admin_users_are_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/admin/bookings')->assertForbidden();
    }

    public function test_admin_can_log_in_and_out(): void
    {
        $user = User::factory()->create(['email' => 'boss@example.com', 'password' => 'Secret-pass-123', 'is_admin' => true]);

        $this->post('/admin/login', ['email' => 'boss@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/admin/login', ['email' => 'boss@example.com', 'password' => 'Secret-pass-123'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->post('/admin/logout')->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    /** Laravel skips CSRF checks under PHPUnit; switch them back on to exercise the real logout form. */
    private function enforceCsrf(): void
    {
        $this->app->bind(ValidateCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
    }

    public function test_logout_is_post_only_and_requires_a_csrf_token(): void
    {
        $this->enforceCsrf();
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/logout')->assertStatus(405);
        $this->assertAuthenticatedAs($admin);

        // A forged POST without the session's token is still rejected and signs no one out.
        $this->actingAs($admin)->post('/admin/logout')->assertStatus(419);
        $this->assertAuthenticatedAs($admin);

        $this->actingAs($admin)->withSession(['_token' => 'valid-token'])
            ->post('/admin/logout', ['_token' => 'valid-token'])
            ->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_signing_out_after_the_session_expired_returns_to_login_instead_of_419(): void
    {
        $this->enforceCsrf();

        // The open admin tab still holds the old session's token, but that session is gone.
        $this->withSession(['_token' => 'fresh-token'])
            ->post('/admin/logout', ['_token' => 'token-from-expired-session'])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('status', 'Your session had already ended. Please sign in again.');
        $this->assertGuest();
    }

    public function test_non_admin_credentials_cannot_log_in(): void
    {
        User::factory()->create(['email' => 'staff@example.com', 'password' => 'Secret-pass-123', 'is_admin' => false]);

        $this->post('/admin/login', ['email' => 'staff@example.com', 'password' => 'Secret-pass-123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_dashboard_shows_real_statistics(): void
    {
        Booking::create([
            'reference' => 'OWT-261010-TEST1', 'customer_name' => 'Meera', 'customer_phone' => '9840012345',
            'travel_date' => now()->addDay(), 'travel_time' => '08:00', 'status' => 'pending',
        ]);

        $this->actingAs($this->admin())->get('/admin')
            ->assertOk()
            ->assertSee('Total Bookings')
            ->assertSee('OWT-261010-TEST1')
            ->assertSee('Active Routes');
    }

    public function test_every_admin_screen_renders(): void
    {
        $this->actingAs($this->admin());

        $urls = [
            route('admin.dashboard'), route('admin.homepage.index'), route('admin.pages.index'), route('admin.pages.create'),
            route('admin.pages.edit', Page::where('slug', 'about')->first()), route('admin.pages.preview', Page::where('slug', 'about')->first()),
            route('admin.media.index'), route('admin.media.edit', MediaAsset::first()), route('admin.media.picker'),
            route('admin.cities.index'), route('admin.cities.create'), route('admin.cities.edit', City::first()),
            route('admin.routes.index'), route('admin.routes.create'), route('admin.routes.edit', TaxiRoute::first()),
            route('admin.vehicles.index'), route('admin.vehicles.create'), route('admin.vehicles.edit', VehicleCategory::first()),
            route('admin.fares.index'), route('admin.fares.edit', VehicleCategory::first()),
            route('admin.bookings.index'), route('admin.messages.index'),
            route('admin.services.index'), route('admin.services.create'), route('admin.services.edit', Service::first()),
            route('admin.faqs.index'), route('admin.faqs.create'), route('admin.faqs.edit', Faq::first()),
            route('admin.testimonials.index'), route('admin.testimonials.create'), route('admin.testimonials.edit', Testimonial::first()),
            route('admin.navigation.index'), route('admin.navigation.create'), route('admin.navigation.edit', NavigationItem::first()),
            route('admin.settings.edit'), route('admin.account.edit'),
        ];

        foreach (PageSection::all() as $section) {
            $urls[] = route('admin.homepage.edit', $section);
        }

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_admin_forms_require_csrf_tokens_in_production_middleware(): void
    {
        // Laravel's CSRF middleware is skipped in tests; make sure every admin form still renders a token.
        $this->actingAs($this->admin())->get(route('admin.settings.edit'))->assertSee('name="_token"', false);
        $this->get('/book')->assertSee('name="_token"', false);
    }

    public function test_admin_pages_send_security_headers_and_public_pages_do_not(): void
    {
        foreach ([$this->get(route('admin.login')), $this->actingAs($this->admin())->get(route('admin.dashboard'))] as $response) {
            $response->assertOk()
                ->assertHeader('X-Frame-Options', 'DENY')
                ->assertHeader('Content-Security-Policy', "frame-ancestors 'none'")
                ->assertHeader('X-Content-Type-Options', 'nosniff');
            $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        }

        // The public website keeps its existing headers.
        $this->get('/')->assertOk()->assertHeaderMissing('X-Frame-Options')->assertHeaderMissing('Content-Security-Policy');
    }

    public function test_filtered_lists_say_nothing_matched_instead_of_nothing_exists(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.routes.index', ['q' => 'no-such-route-zz']))->assertOk()
            ->assertSee('No routes match your search.')->assertDontSee('No routes yet.');
        $this->get(route('admin.cities.index', ['q' => 'no-such-city-zz']))->assertOk()->assertSee('No cities match your search.');
        $this->get(route('admin.media.index', ['q' => 'no-such-image-zz']))->assertOk()->assertSee('No images match your search.');
        $this->get(route('admin.routes.index'))->assertOk()->assertDontSee('No routes match your search.');
    }

    public function test_admin_validation_messages_use_readable_field_names(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.routes.store'), [])
            ->assertSessionHasErrors([
                'origin_city_id' => 'The origin city field is required.',
                'distance_km' => 'The distance (km) field is required.',
                'base_fare' => 'The starting fare field is required.',
                'slug' => 'Choose the origin and destination cities, and the URL slug will be generated automatically.',
            ]);
    }

    public function test_dashboard_marks_the_current_page_and_shows_the_signed_in_admin(): void
    {
        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('aria-current="page"', false)
            ->assertSee('class="topbar-profile"', false)
            ->assertSee('Booking Status')
            ->assertSee('Recent Bookings')
            ->assertSee('Search bookings')
            ->assertSee('action="'.route('admin.logout').'"', false);
    }

    public function test_dashboard_charts_use_real_bookings_and_estimated_fares(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 15)->setTime(10, 0));
        $booking = function (string $reference, string $createdAt, ?string $fare, string $status = 'pending') {
            Booking::create([
                'reference' => $reference, 'customer_name' => 'Test', 'customer_phone' => '9840012345',
                'travel_date' => now()->addDay(), 'travel_time' => '08:00', 'status' => $status, 'estimated_fare' => $fare,
            ])->forceFill(['created_at' => $createdAt])->saveQuietly();
        };
        $booking('OWT-CHART-OLD', '2026-03-10 09:00', '50000.00');              // before the six-month window
        $booking('OWT-CHART-JUN', '2026-06-05 09:00', '1000.00');               // previous quarter (May-Jul)
        $booking('OWT-CHART-SEP', '2026-09-12 09:00', '2000.00');               // last quarter (Aug-Oct)
        $booking('OWT-CHART-OCT', '2026-10-02 09:00', '1500.00');
        $booking('OWT-CHART-CAN', '2026-10-03 09:00', '9999.00', 'cancelled'); // counted as an enquiry, not as revenue

        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['data-chart="months"', '<strong>4</strong>', 'bookings · last 6 months', 'May', 'Oct'], false)
            ->assertSeeInOrder(['Revenue Growth', '+250%', 'From last quarter', '₹3,500', 'May', '₹1.5K', 'Oct'])
            ->assertSee('June 2026: ₹1,000 estimated')
            // Donut: pending, contacted and confirmed count as Pending; the cancelled enquiry is still a booking.
            ->assertSeeInOrder(['Booking Status', '5', 'Total bookings', 'Completed', '0', '0%', 'Pending', '4', '80%', 'Cancelled', '1', '20%'])
            ->assertSeeInOrder(['Recent Bookings', 'OWT-CHART-CAN', '₹9,999'])
            // Chart colours are rendered onto the SVG elements: blue line, then the pending (orange) and cancelled (red) segments.
            ->assertSee('class="dash-line__path" fill="none" stroke="#1677FF"', false)
            ->assertSeeInOrder(['class="donut__part" fill="none" stroke="#FA8C16"', 'class="donut__part" fill="none" stroke="#F04438"'], false)
            ->assertDontSee('class="donut__part" fill="none" stroke="#1677FF"', false); // no completed bookings, so no blue segment
    }
}
