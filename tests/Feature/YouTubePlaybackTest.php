<?php

namespace Tests\Feature;

use App\Models\DistrictPage;
use App\Models\PromoVideo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** YouTube playback: embed check on save, live admin check, and how the website shows each kind of video. */
class YouTubePlaybackTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::where('is_admin', true)->firstOrFail());
    }

    /** @param array<string, mixed> $overrides */
    private function save(array $overrides = [])
    {
        return $this->post(route('admin.videos.store'), array_merge([
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'title' => 'Airport run', 'category' => 'promotional',
            'placement' => 'home', 'home_section' => 'featured', 'is_active' => '1',
        ], $overrides));
    }

    public function test_every_supported_link_format_gives_the_same_embed(): void
    {
        foreach (['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'https://youtu.be/dQw4w9WgXcQ', 'https://www.youtube.com/shorts/dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ'] as $url) {
            $id = PromoVideo::parseYoutubeId($url);
            $this->assertSame('dQw4w9WgXcQ', $id, $url);
            $this->assertStringStartsWith('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', (new PromoVideo(['youtube_id' => $id]))->embedUrl());
        }
    }

    public function test_public_embeddable_video_is_saved_and_verified(): void
    {
        $this->save()->assertSessionHasNoErrors();
        $video = PromoVideo::firstOrFail();
        $this->assertSame('ok', $video->embed_status);
        $this->assertNotNull($video->embed_checked_at);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'youtube.com/oembed') && str_contains(urldecode($request->url()), 'watch?v=dQw4w9WgXcQ'));
    }

    public function test_video_with_embedding_disabled_is_rejected(): void
    {
        $this->youtubeOembedStatus = 401;
        $this->save()->assertSessionHasErrors(['youtube_id' => 'The video owner has turned off embedding, so it cannot be played on other websites. Please choose a public video that allows embedding.']);
        $this->assertSame(0, PromoVideo::count());
    }

    public function test_private_or_deleted_video_is_rejected(): void
    {
        $this->youtubeOembedStatus = 404;
        $this->save()->assertSessionHasErrors('youtube_id');
        $this->assertSame(0, PromoVideo::count());
    }

    public function test_video_is_saved_as_unverified_when_youtube_cannot_be_reached(): void
    {
        $this->youtubeOembedStatus = 0;
        $this->save()->assertSessionHasNoErrors()->assertSessionHas('status', fn ($s) => str_contains($s, 'could not be reached'));
        $this->assertSame('unknown', PromoVideo::firstOrFail()->embed_status);
    }

    public function test_admin_live_check_reports_status_and_preview(): void
    {
        $this->getJson(route('admin.videos.check', ['url' => 'https://youtu.be/dQw4w9WgXcQ']))->assertOk()
            ->assertJson(['id' => 'dQw4w9WgXcQ', 'status' => 'ok', 'title' => 'Test video'])
            ->assertJsonPath('embed', fn ($embed) => str_starts_with($embed, 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'));

        $this->youtubeOembedStatus = 401;
        $this->getJson(route('admin.videos.check', ['url' => 'https://youtu.be/dQw4w9WgXcQ']))->assertJson(['status' => 'blocked', 'embed' => null]);

        $this->getJson(route('admin.videos.check', ['url' => 'https://vimeo.com/123']))->assertJson(['id' => null, 'status' => 'invalid']);
    }

    public function test_admin_preview_player_sends_a_referrer_youtube_accepts(): void
    {
        $this->save();
        $this->get(route('admin.videos.edit', PromoVideo::firstOrFail()))->assertOk()
            ->assertSee('referrerpolicy="strict-origin-when-cross-origin"', false)
            ->assertSee('Public video — it can be played on the website.');
    }

    public function test_website_plays_embeddable_videos_and_links_blocked_ones_to_youtube(): void
    {
        $chennai = DistrictPage::where('slug', 'chennai')->firstOrFail();
        $ok = PromoVideo::create(['title' => 'Plays here', 'youtube_url' => 'x', 'youtube_id' => 'aaaaaaaaaaa', 'embed_status' => 'ok', 'category' => 'brand', 'show_on_home' => true]);
        $blocked = PromoVideo::create(['title' => 'Owner blocked embedding', 'youtube_url' => 'x', 'youtube_id' => 'bbbbbbbbbbb', 'embed_status' => 'blocked', 'category' => 'brand', 'show_on_home' => true]);
        PromoVideo::create(['title' => 'Deleted on YouTube', 'youtube_url' => 'x', 'youtube_id' => 'ccccccccccc', 'embed_status' => 'unavailable', 'category' => 'brand', 'show_on_home' => true]);
        $ok->districts()->attach($chennai);
        $blocked->districts()->attach($chennai);

        foreach (['/', '/tamil-nadu/chennai'] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('data-video-id="aaaaaaaaaaa"', false)               // opens in the on-site player
                ->assertSee('Owner blocked embedding')->assertDontSee('data-video-id="bbbbbbbbbbb"', false)
                ->assertSee('Watch on YouTube')->assertSee('https://www.youtube.com/watch?v=bbbbbbbbbbb', false)
                ->assertDontSee('Deleted on YouTube')                           // never shown as a broken player
                ->assertSee('data-video-fallback', false)                       // graceful fallback inside the player
                ->assertDontSee('<iframe', false);                              // no player until a visitor clicks
        }
    }
}
