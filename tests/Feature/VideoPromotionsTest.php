<?php

namespace Tests\Feature;

use App\Models\DistrictPage;
use App\Models\PromoVideo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoPromotionsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User
    {
        return User::where('is_admin', true)->firstOrFail();
    }

    private function district(string $slug): DistrictPage
    {
        return DistrictPage::where('slug', $slug)->firstOrFail();
    }

    /** @param  array<string, mixed>  $overrides */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Chennai airport drop',
            'description' => 'A customer shares their early-morning airport drop.',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'category' => 'testimonial',
            'is_active' => '1',
            'show_on_districts' => '1',
            'sort_order' => 0,
            'district_ids' => [],
        ], $overrides);
    }

    public function test_youtube_ids_are_extracted_from_every_supported_link_format(): void
    {
        foreach ([
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://youtube.com/watch?feature=share&v=dQw4w9WgXcQ&t=42',
            'https://m.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://youtu.be/dQw4w9WgXcQ',
            'https://youtu.be/dQw4w9WgXcQ?si=abc123',
            'youtu.be/dQw4w9WgXcQ',
            'https://www.youtube.com/shorts/dQw4w9WgXcQ',
            'https://youtube.com/shorts/dQw4w9WgXcQ?feature=share',
            'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
            'https://www.youtube.com/live/dQw4w9WgXcQ',
        ] as $url) {
            $this->assertSame('dQw4w9WgXcQ', PromoVideo::parseYoutubeId($url), $url);
        }

        foreach ([
            '', 'not a link', 'https://vimeo.com/123456789', 'https://www.youtube.com/channel/UC1234567890',
            'https://www.youtube.com/watch?v=short', 'https://evil.example/watch?v=dQw4w9WgXcQ', 'https://youtube.com.evil.example/shorts/dQw4w9WgXcQ',
        ] as $url) {
            $this->assertNull(PromoVideo::parseYoutubeId($url), $url);
        }
    }

    public function test_admin_adds_a_video_with_extracted_id_and_youtube_thumbnail(): void
    {
        $chennai = $this->district('chennai');
        $this->actingAs($this->admin())
            ->post(route('admin.videos.store'), $this->payload(['youtube_url' => 'https://youtube.com/shorts/dQw4w9WgXcQ', 'district_ids' => [$chennai->id]]))
            ->assertRedirect(route('admin.videos.index', ['placement' => 'all']))->assertSessionHasNoErrors();

        $video = PromoVideo::firstOrFail();
        $this->assertSame('dQw4w9WgXcQ', $video->youtube_id);
        $this->assertSame('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg', $video->thumbnailUrl());
        $this->assertSame([$chennai->id], $video->districts->pluck('id')->all());

        $this->get(route('admin.videos.index', ['placement' => 'all']))->assertOk()->assertSee('Chennai airport drop')->assertSee('Chennai');
        $this->get(route('admin.videos.edit', $video))->assertOk()->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false);
    }

    public function test_invalid_youtube_links_are_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.videos.store'), $this->payload(['youtube_url' => 'https://vimeo.com/123456']))
            ->assertSessionHasErrors('youtube_id');
        $this->assertSame(0, PromoVideo::count());
    }

    public function test_a_video_needs_somewhere_to_appear(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.videos.store'), $this->payload(['show_on_districts' => '0']))
            ->assertSessionHasErrors('show_on_home');
    }

    public function test_homepage_shows_only_active_homepage_videos(): void
    {
        $home = PromoVideo::create(['title' => 'Brand film', 'youtube_url' => 'https://youtu.be/aaaaaaaaaaa', 'youtube_id' => 'aaaaaaaaaaa', 'category' => 'brand', 'show_on_home' => true]);
        PromoVideo::create(['title' => 'Hidden film', 'youtube_url' => 'https://youtu.be/bbbbbbbbbbb', 'youtube_id' => 'bbbbbbbbbbb', 'category' => 'brand', 'show_on_home' => true, 'is_active' => false]);
        $districtOnly = PromoVideo::create(['title' => 'Madurai trip', 'youtube_url' => 'https://youtu.be/ccccccccccc', 'youtube_id' => 'ccccccccccc', 'category' => 'travel']);
        $districtOnly->districts()->attach($this->district('madurai'));

        $this->get('/')->assertOk()
            ->assertSeeInOrder(['Explore Our Taxi Experiences', 'Real journeys, real customers and reliable taxi services.', 'Brand film'])
            ->assertSee('data-video-id="aaaaaaaaaaa"', false)
            ->assertSee('https://i.ytimg.com/vi/aaaaaaaaaaa/hqdefault.jpg', false)
            ->assertSee('data-video-modal', false)
            ->assertDontSee('Hidden film')->assertDontSee('Madurai trip')
            // No player is created on page load — only thumbnails.
            ->assertDontSee('<iframe', false);
        $this->assertTrue($home->fresh()->is_active); // active by default
    }

    public function test_homepage_video_section_is_hidden_without_videos(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Explore Our Taxi Experiences')->assertDontSee('data-video-modal', false);
    }

    public function test_one_video_shows_on_the_homepage_and_several_districts_without_duplicates(): void
    {
        $chennai = $this->district('chennai');
        $coimbatore = $this->district('coimbatore');

        $this->actingAs($this->admin())->post(route('admin.videos.store'), $this->payload([
            'title' => 'OneWayTrip across Tamil Nadu', 'show_on_home' => '1', 'district_ids' => [$chennai->id, $coimbatore->id],
        ]))->assertSessionHasNoErrors();
        $this->assertSame(1, PromoVideo::count());

        $this->get('/')->assertSee('OneWayTrip across Tamil Nadu');
        $this->get('/tamil-nadu/chennai')->assertOk()->assertSee('Taxi Experiences in Chennai')->assertSee('OneWayTrip across Tamil Nadu');
        $this->get('/tamil-nadu/coimbatore')->assertOk()->assertSee('Taxi Experiences in Coimbatore')->assertSee('OneWayTrip across Tamil Nadu');
        $this->get('/tamil-nadu/madurai')->assertOk()->assertDontSee('Taxi Experiences in Madurai')->assertDontSee('OneWayTrip across Tamil Nadu');
    }

    public function test_district_page_visibility_switch_hides_video_on_district_pages(): void
    {
        $video = PromoVideo::create(['title' => 'Salem drive', 'youtube_url' => 'https://youtu.be/ddddddddddd', 'youtube_id' => 'ddddddddddd', 'category' => 'travel', 'show_on_districts' => false, 'show_on_home' => true]);
        $video->districts()->attach($this->district('salem'));

        $this->get('/tamil-nadu/salem')->assertOk()->assertDontSee('Salem drive');
    }

    public function test_active_video_limits_per_placement(): void
    {
        $madurai = $this->district('madurai');
        foreach (range(1, PromoVideo::MAX_ACTIVE_PER_PLACEMENT) as $i) {
            $v = PromoVideo::create(['title' => "Video {$i}", 'youtube_url' => 'x', 'youtube_id' => str_pad((string) $i, 11, 'a'), 'category' => 'brand', 'show_on_home' => true]);
            $v->districts()->attach($madurai);
        }

        $admin = $this->actingAs($this->admin());
        $admin->post(route('admin.videos.store'), $this->payload(['show_on_home' => '1', 'show_on_districts' => '0']))->assertSessionHasErrors('show_on_home');
        $admin->post(route('admin.videos.store'), $this->payload(['district_ids' => [$madurai->id]]))->assertSessionHasErrors('district_ids');
        // Inactive videos do not count against the limit.
        $admin->post(route('admin.videos.store'), $this->payload(['is_active' => '0', 'show_on_home' => '1', 'district_ids' => [$madurai->id]]))->assertSessionHasNoErrors();

        $inactive = PromoVideo::where('is_active', false)->firstOrFail();
        $admin->patch(route('admin.videos.toggle', $inactive))->assertSessionHasErrors('video');
        $this->assertFalse($inactive->fresh()->is_active);

        $this->assertCount(PromoVideo::MAX_ACTIVE_PER_PLACEMENT, PromoVideo::forDistrict($madurai)->get());
    }

    public function test_admin_edits_toggles_and_deletes_a_video(): void
    {
        $chennai = $this->district('chennai');
        $madurai = $this->district('madurai');
        $admin = $this->actingAs($this->admin());
        $admin->post(route('admin.videos.store'), $this->payload(['district_ids' => [$chennai->id]]));
        $video = PromoVideo::firstOrFail();

        $admin->put(route('admin.videos.update', $video), $this->payload(['title' => 'Renamed', 'youtube_url' => 'https://youtu.be/eeeeeeeeeee', 'district_ids' => [$madurai->id]]))
            ->assertSessionHasNoErrors();
        $video->refresh();
        $this->assertSame(['Renamed', 'eeeeeeeeeee'], [$video->title, $video->youtube_id]);
        $this->assertSame([$madurai->id], $video->districts()->pluck('district_pages.id')->all());

        $admin->patch(route('admin.videos.toggle', $video))->assertSessionHasNoErrors();
        $this->assertFalse($video->fresh()->is_active);
        $this->get('/tamil-nadu/madurai')->assertDontSee('Renamed');

        $admin->delete(route('admin.videos.destroy', $video))->assertRedirect(route('admin.videos.index', ['placement' => 'all']));
        $this->assertSame(0, PromoVideo::count());
        $this->assertDatabaseCount('district_page_promo_video', 0);
        $this->assertTrue(DistrictPage::whereKey($madurai->id)->exists()); // districts are never deleted with a video
    }

    public function test_guests_cannot_manage_videos(): void
    {
        $this->get(route('admin.videos.index'))->assertRedirect();
        $this->post(route('admin.videos.store'), $this->payload())->assertRedirect();
        $this->assertSame(0, PromoVideo::count());
    }

    public function test_admin_manages_district_seo_routes_and_faqs(): void
    {
        $nilgiris = $this->district('nilgiris');
        $admin = $this->actingAs($this->admin());
        foreach (['seo', 'services', 'routes', 'faqs'] as $tab) {
            $admin->get(route('admin.districts.tab', [$nilgiris, $tab]))->assertOk();
        }

        $admin->put(route('admin.districts.tab.update', [$nilgiris, 'routes']), ['drop_locations' => "Ooty Lake\nDoddabetta Peak"])->assertSessionHasNoErrors();
        $admin->post(route('admin.districts.routes.store', $nilgiris), ['from_location' => 'Ooty', 'to_location' => 'Mysuru', 'description' => 'Via the Gundlupet ghat road', 'is_active' => '1'])->assertSessionHasNoErrors();
        $admin->post(route('admin.districts.faqs.store', $nilgiris), ['question' => 'Is the ghat road open at night?', 'answer' => 'Our team confirms timings for your date when they call.', 'is_active' => '1', 'sort_order' => 0])->assertSessionHasNoErrors();
        $admin->put(route('admin.districts.tab.update', [$nilgiris, 'services']), [
            'visible' => ['one_way', 'round_trip', 'local'], // rental switched off
            'service_texts' => ['local' => 'Sightseeing loops around Ooty and Coonoor.', 'rental' => ''],
        ])->assertSessionHasNoErrors();
        $admin->put(route('admin.districts.tab.update', [$nilgiris, 'seo']), [
            'seo_title' => 'Ooty Taxi Service', 'seo_description' => 'Hill taxis in the Nilgiris.', 'seo_keywords' => 'ooty taxi, nilgiris cab',
            'og_title' => 'Nilgiris taxis', 'og_description' => 'Book hill-station taxis.', 'image_alt' => 'Tea gardens near Ooty',
        ])->assertSessionHasNoErrors();

        $nilgiris->refresh();
        $this->assertSame(['Ooty Lake', 'Doddabetta Peak'], $nilgiris->drop_locations);
        $this->assertSame(['local' => 'Sightseeing loops around Ooty and Coonoor.'], $nilgiris->service_texts);
        $this->assertSame(['rental', 'airport'], $nilgiris->hidden_services);

        $this->get('/tamil-nadu/nilgiris')->assertOk()
            ->assertSee('<title>Ooty Taxi Service', false)
            ->assertSee('<meta name="keywords" content="ooty taxi, nilgiris cab">', false)
            ->assertSee('<meta property="og:title" content="Nilgiris taxis">', false)
            ->assertSee('<meta property="og:description" content="Book hill-station taxis.">', false)
            ->assertSee('<link rel="canonical" href="'.route('districts.show', 'nilgiris').'">', false)
            ->assertSee('Ooty Lake')->assertSee('Via the Gundlupet ghat road')->assertSee('Is the ghat road open at night?')
            ->assertSee('Sightseeing loops around Ooty and Coonoor.')
            ->assertDontSee('Rental Taxi <span class="sr-only">', false)->assertDontSee('Airport transfers</strong>', false)
            ->assertSee('alt="Tea gardens near Ooty"', false);
    }

    public function test_district_canonical_urls_must_be_unique(): void
    {
        $url = 'https://example.com/ooty-taxi';
        $this->district('salem')->update(['canonical_url' => $url]);

        $this->actingAs($this->admin())->put(route('admin.districts.tab.update', [$this->district('nilgiris'), 'seo']), ['canonical_url' => $url])
            ->assertSessionHasErrors('canonical_url');
    }

    public function test_every_district_has_seeded_local_content_with_distinct_faqs(): void
    {
        $districts = DistrictPage::all();
        $this->assertCount(39, $districts); // 38 Tamil Nadu districts + Puducherry
        $answers = [];
        foreach ($districts as $d) {
            $this->assertNotEmpty($d->dropLocations(), $d->slug);
            $this->assertNotEmpty($d->curatedRoutes(), $d->slug);
            $this->assertCount(4, $d->faqItems(), $d->slug);
            $this->assertFalse($d->content_reviewed, $d->slug); // seeded text is a draft until reviewed
            // Curated routes never repeat the "nearby destinations" list (no duplicate sections).
            $this->assertSame([], array_intersect(array_column($d->curatedRoutes(), 'to'), $d->popularDestinations()), $d->slug);
            foreach ($d->faqItems() as $faq) {
                $answers[] = $faq['answer'];
            }
        }
        $this->assertSame(count($answers), count(array_unique($answers)), 'FAQ answers must not repeat across districts');
    }
}
