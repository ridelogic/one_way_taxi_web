<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\HeroSlide;
use App\Models\MediaAsset;
use App\Models\PageSection;
use App\Models\SiteSetting;
use App\Models\TaxiRoute;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\VehicleCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The CMS acceptance tests requested by the business owner: change content in /admin, save,
 * reload the public website and confirm the change is visible and persisted in the database.
 */
class CmsAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_saving_settings_unchanged_keeps_every_stored_value(): void
    {
        $stored = SiteSetting::pluck('value', 'key')->all();

        // What a browser actually submits for an untouched form: lowercase colour-picker values,
        // CRLF line breaks in textareas (the title suffix keeps its leading space).
        $submitted = $stored;
        foreach (['brand_gold', 'brand_green', 'brand_green_fresh'] as $key) {
            $submitted[$key] = strtolower((string) $stored[$key]);
        }
        foreach (['address', 'rental_packages'] as $key) {
            $submitted[$key] = str_replace("\n", "\r\n", (string) $stored[$key]);
        }

        $this->put(route('admin.settings.update'), $submitted)->assertSessionHasNoErrors();

        $after = SiteSetting::pluck('value', 'key')->all();
        foreach ($stored as $key => $value) {
            $this->assertSame($value, $after[$key], "Setting {$key} changed on an unchanged save");
        }
        $this->assertStringStartsWith(' ', (string) $after['seo_title_suffix']);
        $this->get('/book')->assertSee('<title>Book a Taxi — One Way, Outstation or Rental | One Way Taxi</title>', false);
    }

    public function test_non_image_upload_gets_one_clear_error(): void
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('notes.txt', 1, 'text/plain');

        $this->post(route('admin.media.store'), ['files' => [$file]])->assertSessionHasErrors('files.0');
        $this->assertSame(['Only JPG, PNG, WebP, GIF or ICO images can be uploaded.'], session('errors')->get('files.0'));
    }

    public function test_homepage_section_title_is_required(): void
    {
        $section = PageSection::where('key', 'faqs')->firstOrFail();

        $this->put(route('admin.homepage.update', $section), ['title' => '', 'is_active' => '0', 'sort_order' => $section->sort_order, 'content' => $section->content])
            ->assertSessionHasErrors('title');
        $this->assertSame('Frequently Asked Questions', $section->fresh()->title);
    }

    /** 1×1 transparent PNG — GD is not required to create it. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFBQIAX8jx0gAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::where('is_admin', true)->firstOrFail());
    }

    private function heroSlide(): HeroSlide
    {
        return HeroSlide::active()->ordered()->firstOrFail();
    }

    private function slidePayload(array $overrides = []): array
    {
        $slide = $this->heroSlide();

        return array_merge($slide->only([
            'eyebrow', 'heading', 'highlight', 'subtitle', 'media_id', 'foreground_media_id',
            'primary_label', 'primary_url', 'secondary_label', 'secondary_url', 'overlay_strength', 'text_align', 'sort_order',
        ]), ['is_active' => '1'], $overrides);
    }

    public function test_changing_the_homepage_heading(): void
    {
        $slide = $this->heroSlide();
        $this->get(route('admin.slides.edit', $slide))->assertOk()->assertSee('Your Journey.');

        // The seeded hero (v5 mockup layout) has no buttons, so adding one needs both a label and a link.
        $this->put(route('admin.slides.update', $slide), $this->slidePayload([
            'heading' => 'Travel Smart.',
            'highlight' => 'Book One Way.',
            'primary_label' => 'Get Started',
            'primary_url' => '#book-ride',
        ]))->assertRedirect(route('admin.slides.edit', $slide))->assertSessionHasNoErrors();

        $fresh = $slide->fresh();
        $this->assertSame('Travel Smart.', $fresh->heading);
        $this->assertSame('Book One Way.', $fresh->highlight);
        $this->assertSame('#book-ride', $fresh->primary_url);
        $this->get('/')->assertSee('Travel Smart.')->assertSee('Book One Way.')->assertSee('Get Started')
            ->assertSee('href="#book-ride"', false)->assertDontSee('Your Way.');
    }

    public function test_hero_button_needs_a_link(): void
    {
        $slide = $this->heroSlide();

        $this->put(route('admin.slides.update', $slide), $this->slidePayload(['heading' => 'Changed', 'primary_label' => 'Get Started', 'primary_url' => null]))
            ->assertSessionHasErrors('primary_url');
        $this->assertSame('Comfortable Rides', $slide->fresh()->heading);
    }

    public function test_hero_slides_can_be_added_and_reordered(): void
    {
        $original = $this->heroSlide();

        $this->post(route('admin.slides.store'), $this->slidePayload(['heading' => 'Airport runs', 'highlight' => 'made easy', 'sort_order' => 99]))
            ->assertRedirect(route('admin.slides.index'))->assertSessionHasNoErrors();
        $new = HeroSlide::where('heading', 'Airport runs')->firstOrFail();

        // Hidden slides also occupy positions, so move it up past all of them.
        for ($i = 0; $i < HeroSlide::count(); $i++) {
            $this->patch(route('admin.slides.move', $new), ['direction' => 'up'])->assertRedirect();
        }

        $this->assertSame($new->id, HeroSlide::active()->ordered()->first()->id);
        $this->get('/')->assertSee('Airport runs')->assertSee('data-slider', false);

        $this->put(route('admin.slides.update', $original), $this->slidePayload(['is_active' => '0']));
        $this->assertFalse($original->fresh()->is_active);
    }

    public function test_booking_form_labels_are_editable_in_the_cms(): void
    {
        $booking = PageSection::where('key', 'booking')->firstOrFail();

        $this->put(route('admin.homepage.update', $booking), [
            'title' => 'Plan Your Drop', 'subtitle' => $booking->subtitle, 'is_active' => '1', 'sort_order' => $booking->sort_order,
            'content' => array_merge($booking->content, ['submit_label' => 'SEND REQUEST', 'note' => 'We call back within 15 minutes.']),
        ])->assertSessionHasNoErrors();

        $this->get('/')->assertSee('<h2 id="book-ride-title" class="sr-only">Plan Your Drop</h2>', false)
            ->assertSee('SEND REQUEST');
    }

    public function test_hero_scene_vehicle_and_benefits_are_editable_in_the_cms(): void
    {
        Storage::fake('uploads');
        $slide = $this->heroSlide();
        $hero = PageSection::where('key', 'hero')->firstOrFail();

        $this->post(route('admin.media.store'), ['files' => [UploadedFile::fake()->createWithContent('suv.png', base64_decode(self::PNG))]]);
        $car = MediaAsset::where('original_name', 'suv.png')->firstOrFail();
        $scene = MediaAsset::where('path', 'city-chennai.jpg')->firstOrFail();

        $this->get(route('admin.slides.edit', $slide))->assertOk()->assertSee('name="foreground_media_id"', false);
        $this->put(route('admin.slides.update', $slide), $this->slidePayload(['media_id' => $scene->id, 'foreground_media_id' => $car->id]))
            ->assertSessionHasNoErrors();

        $this->put(route('admin.homepage.update', $hero), [
            'title' => $hero->title, 'subtitle' => $hero->subtitle, 'is_active' => '1', 'sort_order' => $hero->sort_order,
            'content' => array_merge($hero->content ?? [], ['items' => [
                ['icon' => 'star', 'title' => 'Top-rated drivers', 'text' => ''],
                ['icon' => 'rupee', 'title' => 'Fixed fares', 'text' => ''],
            ]]),
        ])->assertSessionHasNoErrors();

        // The homepage hero is car-free: a saved vehicle cut-out is kept in the CMS but never drawn over the scene.
        $html = $this->get('/')->assertDontSee('uploads/'.$car->path, false)->assertDontSee('home-hero__car')->assertSeeInOrder(['Top-rated drivers', 'Fixed fares'])
            ->assertDontSee('hero-airport-takeoff-golden-hour.jpg')->assertDontSee('hero-suv-black.webp')->assertDontSee('Safe &amp; Reliable Travel', false)
            ->getContent();
        $this->assertMatchesRegularExpression('#<img src="[^"]*city-chennai\.jpg"[^>]*class="home-hero__scene-img"#s', $html);
    }

    public function test_hero_slide_image_is_optional(): void
    {
        $this->post(route('admin.slides.store'), $this->slidePayload(['heading' => 'No photo slide', 'media_id' => null]))->assertSessionHasNoErrors();
    }

    public function test_changing_the_hero_image(): void
    {
        Storage::fake('uploads');
        $slide = $this->heroSlide();

        $this->post(route('admin.media.store'), [
            'files' => [UploadedFile::fake()->createWithContent('new-hero.png', base64_decode(self::PNG))],
            'alt_text' => 'New hero',
        ])->assertRedirect(route('admin.media.index'))->assertSessionHasNoErrors();

        $media = MediaAsset::where('original_name', 'new-hero.png')->firstOrFail();
        $this->assertSame(1, $media->width);
        Storage::disk('uploads')->assertExists($media->path);
        $this->assertMatchesRegularExpression('#^media/\d{4}/\d{2}/new-hero-[a-z0-9]{10}\.png$#', $media->path);

        $this->put(route('admin.slides.update', $slide), $this->slidePayload(['media_id' => $media->id]))->assertSessionHasNoErrors();

        $this->assertSame($media->id, $slide->fresh()->media_id);
        $this->get('/')->assertSee('uploads/'.$media->path, false);
    }

    public function test_changing_the_phone_number(): void
    {
        $values = SiteSetting::pluck('value', 'key')->all();
        $values['phone'] = '+91 91234 56789';
        foreach (['demo_mode', 'structured_data_enabled', 'search_engine_indexing'] as $flag) {
            $values[$flag] = $values[$flag] ?? '0';
        }

        $this->put(route('admin.settings.update'), $values)->assertRedirect(route('admin.settings.edit'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('site_settings', ['key' => 'phone', 'value' => '+91 91234 56789']);
        $this->get('/')->assertSee('+91 91234 56789')->assertSee('tel:+919123456789', false);
    }

    public function test_changing_a_route_fare(): void
    {
        $route = TaxiRoute::where('slug', 'chennai-to-coimbatore-taxi')->firstOrFail();

        $this->put(route('admin.routes.update', $route), [
            'origin_city_id' => $route->origin_city_id,
            'destination_city_id' => $route->destination_city_id,
            'slug' => $route->slug,
            'distance_km' => '505',
            'duration_minutes' => 510,
            'base_fare' => '5,499',
            'is_active' => '1',
            'is_popular' => '1',
            'is_sample_fare' => '0',
            'sort_order' => 1,
        ])->assertRedirect(route('admin.routes.edit', $route))->assertSessionHasNoErrors();

        $this->assertSame('5499.00', $route->fresh()->base_fare);
        $this->get(route('routes.show', $route->slug))->assertSee('₹5,499');
        $this->get('/routes')->assertSee('₹5,499');
    }

    public function test_route_specific_vehicle_fare_changes_the_estimate(): void
    {
        $route = TaxiRoute::where('slug', 'coimbatore-to-ooty-taxi')->firstOrFail();
        $suv = VehicleCategory::where('slug', 'suv')->firstOrFail();

        $this->put(route('admin.routes.update', $route), [
            'origin_city_id' => $route->origin_city_id,
            'destination_city_id' => $route->destination_city_id,
            'slug' => $route->slug,
            'distance_km' => '86',
            'duration_minutes' => 165,
            'base_fare' => '2090',
            'is_active' => '1',
            'fares' => [$suv->id => '3200'],
        ])->assertSessionHasNoErrors();

        $this->postJson('/fare-estimate', ['pickup_location' => 'Coimbatore', 'destination_location' => 'Ooty', 'vehicle_category_id' => $suv->id])
            ->assertJson(['total' => '3200.00', 'basis' => 'route_fare']);
    }

    public function test_hiding_and_showing_sections(): void
    {
        $testimonials = PageSection::where('key', 'testimonials')->firstOrFail();
        $faqs = PageSection::where('key', 'faqs')->firstOrFail();
        // The homepage prefers genuine reviews, so mark one seeded review as genuine for this test.
        Testimonial::query()->firstOrFail()->update(['is_sample' => false]);
        // FAQs are hidden in the v14 layout; testimonials are shown.
        $this->get('/')->assertSee('What Our Customers Say')->assertDontSee('Frequently Asked Questions');

        $this->patch(route('admin.homepage.toggle', $testimonials))->assertRedirect();
        $this->patch(route('admin.homepage.toggle', $faqs))->assertRedirect();

        $this->assertFalse($testimonials->fresh()->is_active);
        $this->assertTrue($faqs->fresh()->is_active);
        $this->get('/')->assertDontSee('What Our Customers Say')->assertSee('Frequently Asked Questions');

        $this->patch(route('admin.homepage.toggle', $faqs))->assertRedirect();
        $this->get('/')->assertDontSee('Frequently Asked Questions');
    }

    public function test_sections_can_be_reordered(): void
    {
        $html = fn () => $this->get('/')->getContent();
        $this->assertLessThan(strpos($html(), 'id="fleet"'), strpos($html(), 'id="how-it-works"'));

        $fleet = PageSection::where('key', 'fleet')->firstOrFail();
        $this->patch(route('admin.homepage.move', $fleet), ['direction' => 'up'])->assertRedirect();

        $this->assertLessThan(strpos($html(), 'id="how-it-works"'), strpos($html(), 'id="fleet"'));
    }

    public function test_brand_colours_are_configurable(): void
    {
        $values = SiteSetting::pluck('value', 'key')->all();
        $values['brand_gold'] = '#C9A227';

        $this->put(route('admin.settings.update'), $values)->assertSessionHasNoErrors();
        $this->get('/')->assertSee('--color-gold:#C9A227', false);

        $values['brand_gold'] = 'red;}body{display:none';
        $this->put(route('admin.settings.update'), $values)->assertSessionHasErrors('brand_gold');
    }

    public function test_booking_status_and_notes_can_be_updated(): void
    {
        $booking = Booking::create([
            'reference' => 'OWT-261010-TEST2', 'customer_name' => 'Meera', 'customer_phone' => '9840012345',
            'travel_date' => now()->addDay(), 'travel_time' => '08:00', 'status' => 'pending',
        ]);

        $this->get(route('admin.bookings.index', ['q' => 'Meera']))->assertSee('OWT-261010-TEST2');
        $this->get(route('admin.bookings.show', $booking))->assertSee('9840012345');

        $this->put(route('admin.bookings.update', $booking), [
            'status' => 'confirmed', 'admin_notes' => 'Driver Suresh assigned', 'estimated_fare' => '2,500',
        ])->assertSessionHasNoErrors();

        $booking->refresh();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame('Driver Suresh assigned', $booking->admin_notes);
        $this->assertSame('2500.00', $booking->estimated_fare);
        $this->assertNotNull($booking->confirmed_at);
    }

    public function test_pages_can_be_created_as_draft_previewed_and_published(): void
    {
        $this->post(route('admin.pages.store'), [
            'title' => 'Wedding Cars', 'slug' => '', 'template' => 'default', 'status' => 'draft',
            'body' => '<p>Decorated cars.</p><script>alert(1)</script>', 'show_in_nav' => '1',
        ])->assertSessionHasNoErrors();

        $page = \App\Models\Page::where('slug', 'wedding-cars')->firstOrFail();
        $this->assertStringNotContainsString('script', $page->body);
        $this->get('/wedding-cars')->assertNotFound();
        $this->get(route('admin.pages.preview', $page))->assertOk()->assertSee('Decorated cars.');

        $this->put(route('admin.pages.update', $page), [
            'title' => 'Wedding Cars', 'slug' => 'wedding-cars', 'template' => 'default', 'status' => 'published', 'show_in_nav' => '1', 'body' => $page->body,
        ])->assertSessionHasNoErrors();

        $this->get('/wedding-cars')->assertOk()->assertSee('Decorated cars.');
        $this->get('/')->assertSee('Wedding Cars');
    }

    public function test_reserved_page_slugs_are_rejected(): void
    {
        $this->post(route('admin.pages.store'), ['title' => 'Admin', 'slug' => 'admin', 'template' => 'default', 'status' => 'draft'])
            ->assertSessionHasErrors('slug');
    }

    public function test_upload_rejects_non_images(): void
    {
        Storage::fake('uploads');

        $this->post(route('admin.media.store'), ['files' => [UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;')]])
            ->assertSessionHasErrors('files.0');
        $this->post(route('admin.media.store'), ['files' => [UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')]])
            ->assertSessionHasErrors('files.0');

        $this->assertSame(0, MediaAsset::where('disk', 'uploads')->count());
    }

    public function test_media_focal_point_and_fit_are_applied_on_the_site(): void
    {
        // The hero scene photo on the homepage.
        $media = HeroSlide::query()->ordered()->firstOrFail()->media;

        $this->put(route('admin.media.update', $media), [
            'title' => 'Airport', 'alt_text' => 'Airport terminal at sunset', 'aspect_ratio' => '16:9', 'object_fit' => 'contain', 'focal_x' => 30, 'focal_y' => 70,
        ])->assertSessionHasNoErrors();

        $this->get('/')->assertSee('object-fit:contain;object-position:30% 70%', false);
    }

    public function test_media_in_use_cannot_be_deleted(): void
    {
        $media = $this->heroSlide()->media;

        $this->delete(route('admin.media.destroy', $media))->assertSessionHas('error');
        $this->assertModelExists($media);
    }
}
