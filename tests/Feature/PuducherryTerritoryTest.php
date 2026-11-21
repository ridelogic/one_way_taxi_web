<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\DistrictPage;
use App\Models\User;
use Database\Seeders\TerritoryPageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Puducherry: the 39th location page — a separate Union Territory, never a Tamil Nadu district. */
class PuducherryTerritoryTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function puducherry(): DistrictPage
    {
        return DistrictPage::where('slug', 'puducherry')->firstOrFail();
    }

    public function test_puducherry_is_a_separate_union_territory_page_and_the_38_districts_are_unchanged(): void
    {
        $page = $this->puducherry();

        $this->assertSame('Puducherry', $page->state);
        $this->assertFalse($page->isTamilNadu());
        $this->assertSame('Union Territory', $page->typeLabel());
        $this->assertSame(url('/puducherry'), $page->url());
        $this->assertSame(1, DistrictPage::where('slug', 'puducherry')->count()); // no duplicates

        $this->assertSame(39, DistrictPage::count());
        $this->assertSame(38, DistrictPage::tamilNadu()->count());
        $this->assertSame(0, DistrictPage::tamilNadu()->where('slug', 'puducherry')->count());
        $this->assertSame(38, DistrictPage::where('state', 'Tamil Nadu')->count());
    }

    public function test_page_loads_at_its_own_url_without_a_tamil_nadu_breadcrumb(): void
    {
        $html = $this->get('/puducherry')->assertOk()
            ->assertSee('<title>Taxi Service in Puducherry | One Way Taxi</title>', false)
            ->assertSee('<link rel="canonical" href="'.url('/puducherry').'">', false)
            ->assertSee('Reliable Taxi Services in Puducherry')
            ->assertSee('Union Territory')
            ->getContent();

        // Breadcrumb: Home › Puducherry (no Tamil Nadu step), in the page and in the structured data.
        preg_match('#<nav aria-label="Breadcrumb".*?</nav>#s', $html, $crumbs);
        $this->assertStringNotContainsString('Tamil Nadu', $crumbs[0]);
        $this->assertStringNotContainsString('"name":"Tamil Nadu"', $html);
    }

    public function test_the_tamil_nadu_url_redirects_to_the_territory_page(): void
    {
        $this->get('/tamil-nadu/puducherry')->assertRedirect(url('/puducherry'))->assertStatus(301);
        // ...and a Tamil Nadu district is never served at the top level.
        $this->get('/tamil-nadu/madurai')->assertOk();
    }

    public function test_tamil_nadu_directory_still_lists_only_the_38_districts(): void
    {
        $html = $this->get('/tamil-nadu')->assertOk()->getContent();

        $this->assertStringContainsString('all 38 districts of Tamil Nadu', $html);
        $this->assertStringNotContainsString('<a href="'.url('/puducherry').'" class="district-card">', $html);
        $this->assertSame(38, substr_count($html, 'class="district-card"'));
    }

    public function test_puducherry_appears_as_the_39th_entry_in_the_homepage_grid_and_the_district_band(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(39, substr_count($html, 'class="district-card"'));
        $this->assertStringContainsString('<a href="'.url('/puducherry').'" class="district-card">', $html);
        $this->assertStringContainsString('<span class="district-card__tag">Union Territory</span>', $html);

        $start = strpos($html, '<nav class="tn-districts"');
        $band = substr($html, $start, strpos($html, '<footer class="site-footer">') - $start);
        $this->assertStringContainsString('All 38 districts + Puducherry (UT)', $band);
        $this->assertStringContainsString('Taxi Services Across Tamil Nadu &amp; Puducherry', $band);
        $this->assertStringContainsString('href="'.url('/puducherry').'"', $band);
        $this->assertSame(39, substr_count($band, 'class="tn-district"'));
        // Puducherry is listed after all the Tamil Nadu districts.
        $this->assertGreaterThan(strpos($band, 'Virudhunagar'), strpos($band, url('/puducherry')));
    }

    public function test_page_uses_existing_city_routes_and_invents_no_fares(): void
    {
        $page = $this->puducherry();
        $this->assertSame(['Puducherry'], $page->cities->pluck('name')->all());
        $this->assertSame(0, $page->districtVehicles()->count()); // no cars or rates created for Puducherry

        // The only Puducherry route is the existing Chennai → Puducherry route (sample fare from Admin → Routes).
        $this->assertSame(['Chennai → Puducherry'], $page->routes()->map(fn ($r) => $r->origin->name.' → '.$r->destination->name)->all());
        $this->assertSame('Puducherry', City::where('slug', 'puducherry')->value('state'));
    }

    public function test_sitemap_admin_and_pickers_include_puducherry(): void
    {
        $this->get('/sitemap.xml')->assertOk()
            ->assertSee('<loc>'.url('/puducherry').'</loc>', false)
            ->assertDontSee('<loc>'.url('/tamil-nadu/puducherry').'</loc>', false);

        $admin = User::where('is_admin', true)->firstOrFail();
        $this->actingAs($admin)->get(route('admin.districts.index'))->assertOk()->assertSee('Puducherry');
        $this->actingAs($admin)->get(route('admin.districts.tab', [$this->puducherry(), 'seo']))->assertOk()
            ->assertSee('href="'.url('/puducherry').'"', false);
        $this->actingAs($admin)->get(route('admin.videos.create'))->assertOk()->assertSee('Puducherry');
    }

    public function test_seeder_is_insert_only_and_keeps_admin_edits(): void
    {
        $page = $this->puducherry();
        $page->update(['heading' => 'Edited in admin']);

        $this->seed(TerritoryPageSeeder::class);

        $this->assertSame(1, DistrictPage::where('slug', 'puducherry')->count());
        $this->assertSame('Edited in admin', $page->fresh()->heading);
        $this->assertSame(39, DistrictPage::count());
    }
}
