<?php

namespace Tests\Feature;

use App\Models\PromoAnnouncement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Admin → Promotional Announcements and the homepage offers ticker: shown only when the admin enables an announcement. */
class PromoAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User
    {
        return User::where('is_admin', true)->firstOrFail();
    }

    /** @param array<string, mixed> $attributes */
    private function announcement(array $attributes = []): PromoAnnouncement
    {
        return PromoAnnouncement::create(array_merge(['title' => 'Pongal Special', 'message' => 'Book your festival trip early.'], $attributes));
    }

    private function home(): string
    {
        return $this->get('/')->assertOk()->getContent();
    }

    private function assertBannerHidden(): void
    {
        $html = $this->home();
        $this->assertStringNotContainsString('offer-ticker', $html, 'the banner (and its space) must not render');
    }

    public function test_1_no_announcements_means_no_banner(): void
    {
        $this->assertSame(0, PromoAnnouncement::count());
        $this->assertBannerHidden();
    }

    public function test_2_new_announcement_is_disabled_by_default_and_hidden(): void
    {
        $this->actingAs($this->admin())->post(route('admin.announcements.store'), [
            'title' => 'Diwali Special', 'message' => 'Book your ride for the festival.', 'sort_order' => 1,
        ])->assertRedirect(route('admin.announcements.index'))->assertSessionHas('status', fn ($s) => str_contains($s, 'disabled'));

        $this->assertFalse(PromoAnnouncement::firstOrFail()->is_enabled);
        $this->assertFalse($this->announcement()->is_enabled); // model default too
        auth()->logout();
        $this->assertBannerHidden();
    }

    public function test_3_enabled_announcement_within_its_dates_is_visible_below_the_header_above_the_hero(): void
    {
        $this->announcement(['is_enabled' => true, 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(), 'link_url' => '/book']);

        $html = $this->home();
        $ticker = strpos($html, '<section class="offer-ticker"');
        $this->assertNotFalse($ticker);
        $this->assertTrue(strpos($html, '</header>') < $ticker && $ticker < strpos($html, 'id="book-ride"'));
        $this->assertStringContainsString('Pongal Special', $html);
        $this->assertStringContainsString('Book your festival trip early.', $html);
        $this->assertStringContainsString('href="'.url('/book').'" class="offer-ticker__link"', $html);
    }

    public function test_4_disabling_hides_the_banner_immediately(): void
    {
        $a = $this->announcement(['is_enabled' => true]);
        $this->assertStringContainsString('offer-ticker', $this->home());

        $this->actingAs($this->admin())->patch(route('admin.announcements.toggle', $a))->assertRedirect();
        $this->assertFalse($a->fresh()->is_enabled);
        auth()->logout();
        $this->assertBannerHidden();

        // ...and enabling it again brings it back.
        $this->actingAs($this->admin())->patch(route('admin.announcements.toggle', $a));
        auth()->logout();
        $this->assertStringContainsString('Pongal Special', $this->home());
    }

    public function test_5_expired_and_not_yet_started_announcements_are_hidden(): void
    {
        $this->announcement(['is_enabled' => true, 'starts_at' => now()->subDays(5), 'ends_at' => now()->subMinute()]);
        $this->announcement(['title' => 'Future', 'is_enabled' => true, 'starts_at' => now()->addDays(3)]);
        $this->assertBannerHidden();

        // Expiry is automatic: a live announcement disappears once its end time passes.
        $live = $this->announcement(['title' => 'Ends soon', 'is_enabled' => true, 'ends_at' => now()->addHour()]);
        $this->assertStringContainsString('Ends soon', $this->home());
        $this->travel(2)->hours();
        $this->assertBannerHidden();
        $this->assertSame('Expired', $live->fresh()->displayStatus());
    }

    public function test_6_multiple_active_announcements_scroll_together_in_display_order(): void
    {
        $this->announcement(['title' => 'Second', 'sort_order' => 2, 'is_enabled' => true]);
        $this->announcement(['title' => 'First', 'sort_order' => 1, 'is_enabled' => true]);
        $this->announcement(['title' => 'Draft', 'sort_order' => 0, 'is_enabled' => false]);

        $html = $this->home();
        $this->assertSame(1, substr_count($html, '<section class="offer-ticker"'));
        $this->assertSame(2, substr_count($html, '>First<')); // the seamless loop renders the list twice
        $this->assertSame(2, substr_count($html, '>Second<'));
        $this->assertStringNotContainsString('Draft', $html);
        $this->assertLessThan(strpos($html, '>Second<'), strpos($html, '>First<'));
        $this->assertSame(1, preg_match_all('/<ul class="offer-ticker__list"\s+aria-hidden="true"\s*>/', $html));
    }

    public function test_7_all_disabled_means_no_banner(): void
    {
        $this->announcement(['is_enabled' => false]);
        $this->announcement(['title' => 'Other', 'is_enabled' => false]);
        $this->assertBannerHidden();
    }

    public function test_admin_can_list_add_edit_preview_and_delete(): void
    {
        $this->actingAs($this->admin());
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Promotional Announcements')->assertSee(route('admin.announcements.index'), false);
        $this->get(route('admin.announcements.index'))->assertOk()->assertSee('No announcements yet');
        $this->get(route('admin.announcements.create'))->assertOk()
            ->assertSee('name="title"', false)->assertSee('name="message"', false)->assertSee('name="link_url"', false)
            ->assertSee('name="starts_at"', false)->assertSee('name="ends_at"', false)->assertSee('name="sort_order"', false)
            ->assertSee('name="is_enabled" value="1"  >', false); // unticked by default

        $this->post(route('admin.announcements.store'), [
            'title' => 'School Holiday Special', 'message' => 'Plan your family trip.', 'link_url' => 'https://example.com/offer',
            'starts_at' => now()->subHour()->format('Y-m-d\TH:i'), 'ends_at' => now()->addWeek()->format('Y-m-d\TH:i'), 'sort_order' => 3, 'is_enabled' => '1',
        ])->assertSessionHasNoErrors()->assertSessionHas('status', fn ($s) => str_contains($s, 'now showing'));
        $a = PromoAnnouncement::firstOrFail();
        $this->assertTrue($a->isLive());

        $this->get(route('admin.announcements.index'))->assertOk()->assertSee('School Holiday Special')->assertSee('Live')->assertSee('1 announcement showing now.');
        $this->get(route('admin.announcements.edit', $a))->assertOk()->assertSee('value="School Holiday Special"', false);
        $this->get(route('admin.announcements.preview', $a))->assertOk()
            ->assertSee('<section class="offer-ticker"', false)->assertSee('only administrators can see this page')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('target="_blank" rel="noopener noreferrer"', false);

        $this->put(route('admin.announcements.update', $a), ['title' => 'Edited', 'message' => 'Changed.', 'sort_order' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame('Edited', $a->fresh()->title);
        $this->assertFalse($a->fresh()->is_enabled); // unticked checkbox saves as disabled

        $this->get(route('admin.announcements.index'))->assertSee('data-confirm="Delete this announcement? This cannot be undone."', false);
        $this->delete(route('admin.announcements.destroy', $a))->assertRedirect(route('admin.announcements.index'));
        $this->assertModelMissing($a);
    }

    public function test_inputs_are_validated(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.announcements.store'), ['title' => '', 'message' => ''])->assertSessionHasErrors(['title', 'message']);
        foreach (['javascript:alert(1)', 'data:text/html,hi', '//evil.example', 'ftp://example.com', 'book now'] as $bad) {
            $this->post(route('admin.announcements.store'), ['title' => 'T', 'message' => 'M', 'link_url' => $bad])->assertSessionHasErrors('link_url');
        }
        $this->post(route('admin.announcements.store'), ['title' => 'T', 'message' => 'M', 'starts_at' => '2026-12-10T10:00', 'ends_at' => '2026-12-09T10:00'])
            ->assertSessionHasErrors('ends_at');
        $this->post(route('admin.announcements.store'), ['title' => str_repeat('x', 121), 'message' => 'M'])->assertSessionHasErrors('title');
        $this->post(route('admin.announcements.store'), ['title' => ['x'], 'message' => 'M'])->assertSessionHasErrors('title');
        $this->assertSame(0, PromoAnnouncement::count());
    }

    public function test_announcement_text_is_escaped_on_the_website(): void
    {
        $this->announcement(['title' => '<script>alert(1)</script>Offer', 'message' => '<img src=x onerror=alert(1)>', 'is_enabled' => true]);

        $html = $this->home();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<img src=x onerror', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;Offer', $html);
    }

    public function test_visitors_cannot_manage_announcements(): void
    {
        $a = $this->announcement();

        $this->get(route('admin.announcements.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.announcements.preview', $a))->assertRedirect(route('admin.login'));
        $this->post(route('admin.announcements.store'), ['title' => 'Hack', 'message' => 'x', 'is_enabled' => '1'])->assertRedirect(route('admin.login'));
        $this->patch(route('admin.announcements.toggle', $a))->assertRedirect(route('admin.login'));
        $this->delete(route('admin.announcements.destroy', $a))->assertRedirect(route('admin.login'));

        $this->assertSame(1, PromoAnnouncement::count());
        $this->assertFalse($a->fresh()->is_enabled);

        // A signed-in non-admin user is refused too.
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user)->get(route('admin.announcements.index'))->assertStatus(403);
    }
}
