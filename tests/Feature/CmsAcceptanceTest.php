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
        $this->get('/book')->assertSee('<title>Book a Taxi — Local, Rental, One Way or Round Trip | One Way Taxi</title>', false);
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

    public function test_booking_form_labels_are_editable_in_the_cms(): void
    {
        $booking = PageSection::where('key', 'booking')->firstOrFail();

        $this->put(route('admin.homepage.update', $booking), [
            'title' => 'Plan Your Drop', 'subtitle' => $booking->subtitle, 'is_active' => '1', 'sort_order' => $booking->sort_order,
            'content' => array_merge($booking->content, ['submit_label' => 'SEND REQUEST', 'note' => 'We call back within 15 minutes.']),
        ])->assertSessionHasNoErrors();

        $this->get('/')->assertSee('<h2 id="book-ride-title" class="booking-form__title">Plan Your Drop</h2>', false)
            ->assertSee('SEND REQUEST');
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
        // In the seeded order "About" (with Why choose us) sits directly above the services.
        $this->assertLessThan(strpos($html(), 'id="services-title"'), strpos($html(), 'id="about-title"'));

        $services = PageSection::where('key', 'services')->firstOrFail();
        $this->patch(route('admin.homepage.move', $services), ['direction' => 'up'])->assertRedirect();

        $this->assertLessThan(strpos($html(), 'id="about-title"'), strpos($html(), 'id="services-title"'));
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
