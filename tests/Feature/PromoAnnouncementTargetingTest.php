<?php

namespace Tests\Feature;

use App\Models\DistrictPage;
use App\Models\PromoAnnouncement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** One announcement system for the homepage and all 39 location pages: each announcement shows only where targeted. */
class PromoAnnouncementTargetingTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User
    {
        return User::where('is_admin', true)->firstOrFail();
    }

    /** @param list<string> $slugs */
    private function make(string $title, string $target, array $slugs = [], array $extra = []): PromoAnnouncement
    {
        [, $home, $scope] = PromoAnnouncement::TARGETS[$target];
        $a = PromoAnnouncement::create(array_merge(['title' => $title, 'message' => $title.' message', 'show_on_home' => $home, 'location_scope' => $scope, 'is_enabled' => true], $extra));
        $a->districts()->sync(DistrictPage::whereIn('slug', $slugs)->pluck('id'));

        return $a;
    }

    private function html(string $url): string
    {
        return $this->get($url)->assertOk()->getContent();
    }

    private function tickerOf(string $html): string
    {
        $start = strpos($html, '<section class="offer-ticker"');

        return $start === false ? '' : substr($html, $start, strpos($html, '</section>', $start) - $start);
    }

    public function test_1_homepage_only_announcement_appears_only_on_the_homepage(): void
    {
        $this->make('Home Only Offer', 'home');

        $this->assertStringContainsString('Home Only Offer', $this->tickerOf($this->html('/')));
        foreach (['/tamil-nadu/coimbatore', '/tamil-nadu/madurai', '/puducherry'] as $url) {
            $this->assertStringNotContainsString('offer-ticker', $this->html($url), $url);
        }
    }

    public function test_2_all_locations_announcement_appears_on_all_39_location_pages_but_not_the_homepage(): void
    {
        $this->make('Every Location Offer', 'all_locations');

        $pages = DistrictPage::published()->get();
        $this->assertCount(39, $pages);
        foreach ($pages as $page) {
            $html = $this->html($page->url());
            $ticker = $this->tickerOf($html);
            $this->assertStringContainsString('Every Location Offer', $ticker, $page->slug);
            // Below the header, above the hero.
            $this->assertTrue(strpos($html, '</header>') < strpos($html, '<section class="offer-ticker"')
                && strpos($html, '<section class="offer-ticker"') < strpos($html, 'class="district-hero"'), $page->slug);
        }
        $this->assertStringNotContainsString('offer-ticker', $this->html('/'));
    }

    public function test_3_selected_locations_announcement_appears_only_on_those_pages(): void
    {
        $this->make('School Holiday Offer', 'selected_locations', ['coimbatore', 'salem']);

        $this->assertStringContainsString('School Holiday Offer', $this->html('/tamil-nadu/coimbatore'));
        $this->assertStringContainsString('School Holiday Offer', $this->html('/tamil-nadu/salem'));
        foreach (['/', '/tamil-nadu/madurai', '/tamil-nadu/chennai', '/puducherry'] as $url) {
            $this->assertStringNotContainsString('School Holiday Offer', $this->html($url), $url);
        }
    }

    public function test_4_different_locations_show_different_offers_and_combined_targets_work(): void
    {
        $this->make('Festival Offer', 'home_all_locations', [], ['sort_order' => 1]);
        $this->make('Coimbatore Special', 'selected_locations', ['coimbatore'], ['sort_order' => 2]);
        $this->make('Puducherry Festival', 'home_selected_locations', ['puducherry'], ['sort_order' => 3]);

        $coimbatore = $this->tickerOf($this->html('/tamil-nadu/coimbatore'));
        $this->assertStringContainsString('Festival Offer', $coimbatore);
        $this->assertStringContainsString('Coimbatore Special', $coimbatore);
        $this->assertStringNotContainsString('Puducherry Festival', $coimbatore);
        $this->assertLessThan(strpos($coimbatore, 'Coimbatore Special'), strpos($coimbatore, 'Festival Offer')); // display order

        $puducherry = $this->tickerOf($this->html('/puducherry'));
        $this->assertStringContainsString('Festival Offer', $puducherry);
        $this->assertStringContainsString('Puducherry Festival', $puducherry);
        $this->assertStringNotContainsString('Coimbatore Special', $puducherry);

        $home = $this->tickerOf($this->html('/'));
        $this->assertStringContainsString('Festival Offer', $home);
        $this->assertStringContainsString('Puducherry Festival', $home);
        $this->assertStringNotContainsString('Coimbatore Special', $home);

        $madurai = $this->tickerOf($this->html('/tamil-nadu/madurai'));
        $this->assertStringContainsString('Festival Offer', $madurai);
        $this->assertStringNotContainsString('Coimbatore Special', $madurai);
        $this->assertStringNotContainsString('Puducherry Festival', $madurai);
    }

    public function test_5_6_7_disabled_expired_and_future_announcements_are_hidden_on_location_pages(): void
    {
        $this->make('Disabled Offer', 'all_locations', [], ['is_enabled' => false]);
        $this->make('Expired Offer', 'all_locations', [], ['ends_at' => now()->subMinute()]);
        $this->make('Future Offer', 'selected_locations', ['coimbatore'], ['starts_at' => now()->addDays(2)]);

        foreach (['/tamil-nadu/coimbatore', '/puducherry'] as $url) {
            $this->assertStringNotContainsString('offer-ticker', $this->html($url), $url); // no banner, no blank space
        }

        $this->travel(3)->days();
        $this->assertStringContainsString('Future Offer', $this->html('/tamil-nadu/coimbatore'));
        $this->assertStringNotContainsString('Future Offer', $this->html('/tamil-nadu/madurai'));
    }

    public function test_8_multiple_announcements_share_one_seamless_ticker(): void
    {
        $this->make('Offer A', 'all_locations');
        $this->make('Offer B', 'selected_locations', ['madurai']);

        $html = $this->html('/tamil-nadu/madurai');
        $this->assertSame(1, substr_count($html, '<section class="offer-ticker"'));
        $this->assertSame(2, substr_count($html, '>Offer A<'));
        $this->assertSame(2, substr_count($html, '>Offer B<'));
    }

    public function test_9_no_applicable_announcement_means_no_banner_on_location_pages(): void
    {
        $this->make('Somewhere Else', 'selected_locations', ['salem']);

        $this->assertStringNotContainsString('offer-ticker', $this->html('/tamil-nadu/thanjavur'));
    }

    public function test_10_location_pages_keep_their_seo_and_content(): void
    {
        $this->make('Every Location Offer', 'all_locations');

        $this->get('/tamil-nadu/thanjavur')->assertOk()
            ->assertSee('<title>Taxi Service in Thanjavur | One Way Taxi</title>', false)
            ->assertSee('<link rel="canonical" href="'.route('districts.show', 'thanjavur').'">', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('Reliable Taxi Services in Thanjavur');
        $this->get('/tamil-nadu/puducherry')->assertRedirect(url('/puducherry'));
    }

    public function test_page_loads_announcements_with_a_single_query(): void
    {
        $this->make('Offer A', 'all_locations');
        $this->make('Offer B', 'selected_locations', ['madurai']);
        $madurai = DistrictPage::where('slug', 'madurai')->firstOrFail();
        PromoAnnouncement::tableReady(); // warm the once-per-request table check

        DB::enableQueryLog();
        $items = PromoAnnouncement::forTicker($madurai);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertSame(['Offer A', 'Offer B'], $items->pluck('title')->all());
        $this->assertCount(1, $queries);
    }

    public function test_admin_can_target_announcements_with_the_real_location_list(): void
    {
        $this->actingAs($this->admin());
        $districts = DistrictPage::ordered()->get();

        $form = $this->get(route('admin.announcements.create'))->assertOk()
            ->assertSee('Display locations')
            ->assertSee('name="target" value="home" checked', false); // default: homepage only
        foreach (PromoAnnouncement::TARGETS as [$label]) {
            $form->assertSee($label);
        }
        foreach ($districts as $d) {
            $form->assertSee('name="district_ids[]" value="'.$d->id.'"', false);
        }
        $this->assertCount(39, $districts);

        $ids = DistrictPage::whereIn('slug', ['coimbatore', 'puducherry'])->pluck('id')->all();
        $this->post(route('admin.announcements.store'), [
            'title' => 'Two Places', 'message' => 'Selected pages only.', 'target' => 'selected_locations', 'district_ids' => $ids, 'is_enabled' => '1',
        ])->assertSessionHasNoErrors()->assertSessionHas('status', fn ($s) => str_contains($s, '2 selected location pages'));

        $a = PromoAnnouncement::firstOrFail();
        $this->assertFalse($a->show_on_home);
        $this->assertSame('selected', $a->location_scope);
        $this->assertEqualsCanonicalizing($ids, $a->districts->pluck('id')->all());

        $this->get(route('admin.announcements.index'))->assertSee('2 selected location pages')->assertSee('Coimbatore');
        $this->get(route('admin.announcements.edit', $a))->assertSee('name="target" value="selected_locations" checked', false);

        // Switching to "all location pages" clears the selected list.
        $this->put(route('admin.announcements.update', $a), ['title' => 'Two Places', 'message' => 'All now.', 'target' => 'all_locations', 'district_ids' => $ids])
            ->assertSessionHasNoErrors();
        $this->assertSame('all', $a->fresh()->location_scope);
        $this->assertSame(0, $a->districts()->count());

        // Deleting removes its location links too.
        $a->districts()->sync($ids);
        $this->delete(route('admin.announcements.destroy', $a));
        $this->assertSame(0, DB::table('district_page_promo_announcement')->count());
    }

    public function test_before_the_targeting_update_pages_keep_working_with_homepage_only_behaviour(): void
    {
        $home = PromoAnnouncement::create(['title' => 'Old Homepage Offer', 'message' => 'm', 'is_enabled' => true]);

        // Simulate a database where only the first announcements migration has run.
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_11_19_000004_add_page_targeting_to_promo_announcements.php']);
        PromoAnnouncement::forgetSchemaState();
        $this->assertSame('legacy', PromoAnnouncement::schemaState());

        $this->assertStringContainsString('Old Homepage Offer', $this->html('/'));
        $this->assertStringNotContainsString('offer-ticker', $this->html('/tamil-nadu/coimbatore'));
        $this->assertStringNotContainsString('offer-ticker', $this->html('/puducherry'));

        $this->actingAs($this->admin());
        $this->get(route('admin.announcements.index'))->assertOk()->assertSee('Old Homepage Offer')->assertSee('waiting to be applied');
        $this->get(route('admin.announcements.create'))->assertRedirect(route('admin.announcements.index'));
        $this->patch(route('admin.announcements.toggle', $home))->assertRedirect();
        $this->assertFalse($home->fresh()->is_enabled);
        $this->get(route('admin.announcements.preview', $home))->assertOk();

        // Applying the update: existing announcements keep showing on the homepage (defaults), nothing is lost.
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_11_19_000004_add_page_targeting_to_promo_announcements.php']);
        PromoAnnouncement::forgetSchemaState();
        $fresh = $home->fresh();
        $this->assertTrue($fresh->show_on_home);
        $this->assertSame('none', $fresh->location_scope);
        $this->assertSame('Old Homepage Offer', $fresh->title);
    }

    public function test_targeting_input_is_validated(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.announcements.store'), ['title' => 'T', 'message' => 'M', 'target' => 'selected_locations'])
            ->assertSessionHasErrors('district_ids');
        $this->post(route('admin.announcements.store'), ['title' => 'T', 'message' => 'M', 'target' => 'everywhere'])
            ->assertSessionHasErrors('target');
        $this->post(route('admin.announcements.store'), ['title' => 'T', 'message' => 'M', 'target' => 'selected_locations', 'district_ids' => [999999]])
            ->assertSessionHasErrors('district_ids.0');
        $this->assertSame(0, PromoAnnouncement::count());
    }
}
