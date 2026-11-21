<?php

namespace Tests\Feature;

use App\Models\DistrictFaq;
use App\Models\DistrictPage;
use App\Models\DistrictVehicle;
use App\Models\GalleryPhoto;
use App\Models\PromoVideo;
use App\Models\User;
use App\Models\VehicleCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** District-wise CMS and Homepage Content: admin changes appear on the matching public page only. */
class DistrictCmsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('uploads');
        $this->actingAs(User::where('is_admin', true)->firstOrFail());
    }

    /** A real 1×1 PNG (the GD extension is not needed). */
    private function png(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
    }

    private function district(string $slug): DistrictPage
    {
        return DistrictPage::where('slug', $slug)->firstOrFail();
    }

    /** @param array<string, mixed> $overrides */
    private function addVideo(array $overrides): void
    {
        $this->post(route('admin.videos.store'), array_merge([
            'youtube_url' => 'https://youtu.be/aaaaaaaaaaa', 'title' => 'Video', 'category' => 'promotional',
            'is_active' => '1', 'sort_order' => 0, 'district_ids' => [],
        ], $overrides))->assertSessionHasNoErrors();
    }

    public function test_sidebar_is_simple_and_hero_slide_management_is_gone(): void
    {
        $html = $this->get(route('admin.dashboard'))->assertOk()->getContent();
        preg_match('#<ul class="admin-nav">(.*?)</ul>#s', $html, $m);
        $this->assertSame(7, substr_count($m[1], '<li>')); // + Promotional Announcements
        foreach (['Dashboard', 'Bookings', 'District Management', 'Homepage Content', 'Promotional Announcements', 'Vehicle Master', 'Website Settings'] as $label) {
            $this->assertStringContainsString($label, $m[1]);
        }
        $this->assertStringNotContainsString('Hero Banner', $html);
        $this->get('/admin/slides')->assertNotFound();
        $this->get('/')->assertOk()->assertSee('Find Your Perfect Taxi'); // the approved hero still renders
    }

    public function test_every_district_screen_and_tab_renders(): void
    {
        $coimbatore = $this->district('coimbatore');
        $this->get(route('admin.districts.index'))->assertOk()->assertSee('Coimbatore')->assertSee('Tiruppur');
        $this->get(route('admin.districts.edit', $coimbatore))->assertOk()->assertSee('District Details');
        foreach (['videos', 'photos', 'seo', 'cars', 'services', 'routes', 'faqs'] as $tab) {
            $this->get(route('admin.districts.tab', [$coimbatore, $tab]))->assertOk()->assertSee('Coimbatore');
        }
        foreach (['admin.videos.index', 'admin.photos.index', 'admin.homepage.index', 'admin.vehicles.index', 'admin.settings.edit'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_coimbatore_video_appears_only_on_coimbatore(): void
    {
        $coimbatore = $this->district('coimbatore');
        $tab = route('admin.districts.tab', [$coimbatore, 'videos']);
        $this->post(route('admin.videos.store'), [
            'youtube_url' => 'https://youtu.be/bbbbbbbbbbb', 'title' => 'Coimbatore airport run', 'category' => 'travel',
            'placement' => 'districts', 'district_ids' => [$coimbatore->id], 'is_active' => '1', 'return_to' => $tab,
        ])->assertRedirect($tab);

        $this->get($tab)->assertSee('Coimbatore airport run');
        $this->get('/tamil-nadu/coimbatore')->assertSee('Taxi Experiences in Coimbatore')->assertSee('Coimbatore airport run');
        $this->get('/tamil-nadu/chennai')->assertDontSee('Coimbatore airport run');
        $this->get('/')->assertDontSee('Coimbatore airport run');
    }

    public function test_homepage_only_and_shared_videos(): void
    {
        $chennai = $this->district('chennai');
        $this->addVideo(['title' => 'OneWayTrip brand film', 'youtube_url' => 'https://youtu.be/ccccccccccc', 'placement' => 'home', 'home_section' => 'featured']);
        $this->addVideo(['title' => 'Monsoon offer', 'youtube_url' => 'https://youtu.be/ddddddddddd', 'placement' => 'home', 'home_section' => 'promotional']);
        $this->addVideo(['title' => 'Chennai and homepage story', 'youtube_url' => 'https://youtu.be/eeeeeeeeeee', 'placement' => 'both', 'home_section' => 'featured', 'district_ids' => [$chennai->id]]);
        $this->assertSame(3, PromoVideo::count()); // one record each — the shared video is not duplicated

        $this->get('/')->assertOk()
            ->assertSeeInOrder(['Explore Our Taxi Experiences', 'OneWayTrip brand film', 'Taxi Promotional Videos', 'Monsoon offer'])
            ->assertSee('Chennai and homepage story');
        $this->get('/tamil-nadu/chennai')->assertSee('Chennai and homepage story')->assertDontSee('OneWayTrip brand film');
        $this->get('/tamil-nadu/coimbatore')->assertDontSee('OneWayTrip brand film')->assertDontSee('Chennai and homepage story');

        // Disabling a video removes it from the website.
        $brand = PromoVideo::where('title', 'OneWayTrip brand film')->firstOrFail();
        $this->patch(route('admin.videos.toggle', $brand))->assertSessionHasNoErrors();
        $this->get('/')->assertDontSee('OneWayTrip brand film');
    }

    public function test_removing_a_video_from_one_district_keeps_it_elsewhere(): void
    {
        $chennai = $this->district('chennai');
        $madurai = $this->district('madurai');
        $this->addVideo(['title' => 'Shared route video', 'placement' => 'districts', 'district_ids' => [$chennai->id, $madurai->id]]);
        $video = PromoVideo::firstOrFail();

        $this->delete(route('admin.districts.videos.detach', [$chennai, $video]))->assertRedirect(route('admin.districts.tab', [$chennai, 'videos']));
        $this->get('/tamil-nadu/chennai')->assertDontSee('Shared route video');
        $this->get('/tamil-nadu/madurai')->assertSee('Shared route video');
    }

    public function test_chennai_gallery_appears_only_on_chennai(): void
    {
        $chennai = $this->district('chennai');
        $tab = route('admin.districts.tab', [$chennai, 'photos']);
        $this->post(route('admin.photos.store'), [
            'files' => [$this->png('marina.png'), $this->png('central.png')],
            'title' => 'Chennai pickup point', 'placement' => 'districts', 'district_ids' => [$chennai->id], 'is_active' => '1', 'return_to' => $tab,
        ])->assertRedirect($tab)->assertSessionHasNoErrors();

        $this->assertSame(2, GalleryPhoto::count());
        $this->get('/tamil-nadu/chennai')->assertSee('Chennai Photo Gallery')->assertSee('Chennai pickup point');
        $this->get('/tamil-nadu/coimbatore')->assertDontSee('Chennai pickup point')->assertDontSee('Coimbatore Photo Gallery');
        $this->get('/')->assertDontSee('Chennai pickup point');
    }

    public function test_homepage_and_tiruppur_galleries_are_separate(): void
    {
        $tiruppur = $this->district('tiruppur');
        $this->post(route('admin.photos.store'), ['files' => [$this->png('fleet.png')], 'title' => 'Our fleet at night', 'placement' => 'home', 'is_active' => '1'])
            ->assertSessionHasNoErrors();
        $this->post(route('admin.photos.store'), ['files' => [$this->png('knit.png')], 'title' => 'Tiruppur knitwear hub', 'placement' => 'districts', 'district_ids' => [$tiruppur->id], 'is_active' => '1'])
            ->assertSessionHasNoErrors();

        $this->get('/')->assertSee('Photo Gallery')->assertSee('Our fleet at night')->assertDontSee('Tiruppur knitwear hub');
        $this->get('/tamil-nadu/tiruppur')->assertSee('Tiruppur knitwear hub')->assertDontSee('Our fleet at night');
        $this->get('/tamil-nadu/erode')->assertDontSee('Tiruppur knitwear hub');

        // Hiding and editing a photo updates the website.
        $photo = GalleryPhoto::where('title', 'Tiruppur knitwear hub')->firstOrFail();
        $this->put(route('admin.photos.update', $photo), ['title' => 'Tiruppur textile market', 'placement' => 'both', 'district_ids' => [$tiruppur->id], 'is_active' => '1'])
            ->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Tiruppur textile market');
        $this->patch(route('admin.photos.toggle', $photo));
        $this->get('/tamil-nadu/tiruppur')->assertDontSee('Tiruppur textile market');

        // A photo needs a district unless it is homepage-only.
        $this->post(route('admin.photos.store'), ['files' => [$this->png('x.png')], 'placement' => 'districts', 'is_active' => '1'])
            ->assertSessionHasErrors('district_ids');
    }

    public function test_cars_are_assigned_per_district(): void
    {
        $tiruppur = $this->district('tiruppur');
        $coimbatore = $this->district('coimbatore');
        $sedan = VehicleCategory::where('is_active', true)->orderBy('sort_order')->firstOrFail();

        $this->post(route('admin.districts.cars.store', $tiruppur), [
            'vehicle_mode' => 'existing', 'vehicle_category_id' => $sedan->id, 'name' => 'Swift Dzire', 'seats' => 4,
            'is_ac' => '1', 'description' => 'Comfortable sedan for knitwear business trips.', 'is_active' => '1',
        ])->assertRedirect(route('admin.districts.tab', [$tiruppur, 'cars']))->assertSessionHasNoErrors();

        $this->get('/tamil-nadu/tiruppur')->assertSee('Cars Available in Tiruppur')->assertSee('Swift Dzire')->assertSee('Comfortable sedan for knitwear business trips.')
            ->assertSee('vehicle_category_id='.$sedan->id, false);
        $this->get('/tamil-nadu/coimbatore')->assertDontSee('Swift Dzire');

        // A new master vehicle created from Coimbatore is selectable everywhere but only assigned to Coimbatore.
        $masters = VehicleCategory::count();
        $this->post(route('admin.districts.cars.store', $coimbatore), [
            'vehicle_mode' => 'new', 'new_vehicle_name' => 'Toyota Etios', 'new_vehicle_category' => 'Sedan', 'seats' => 4, 'is_ac' => '1', 'is_active' => '1',
        ])->assertSessionHasNoErrors();
        $this->assertSame($masters + 1, VehicleCategory::count());
        $etios = VehicleCategory::where('name', 'Toyota Etios')->firstOrFail();
        $this->assertSame([$coimbatore->id], DistrictVehicle::where('vehicle_category_id', $etios->id)->pluck('district_page_id')->all());
        $this->get(route('admin.districts.tab', [$tiruppur, 'cars']))->assertSee('Toyota Etios'); // in the vehicle picker
        $this->get('/tamil-nadu/coimbatore')->assertSee('Toyota Etios');
        $this->get('/tamil-nadu/tiruppur')->assertDontSee('Toyota Etios');

        // Hide, edit and remove a car — the master vehicle stays.
        $car = DistrictVehicle::where('name', 'Swift Dzire')->firstOrFail();
        $this->get(route('admin.district-cars.edit', $car))->assertOk();
        $this->put(route('admin.district-cars.update', $car), ['vehicle_category_id' => $sedan->id, 'name' => 'Dzire Tour', 'is_ac' => '1', 'is_active' => '1'])->assertSessionHasNoErrors();
        $this->get('/tamil-nadu/tiruppur')->assertSee('Dzire Tour');
        $this->patch(route('admin.district-cars.toggle', $car));
        $this->get('/tamil-nadu/tiruppur')->assertDontSee('Dzire Tour');
        $this->delete(route('admin.district-cars.destroy', $car))->assertRedirect(route('admin.districts.tab', [$tiruppur, 'cars']));
        $this->assertTrue(VehicleCategory::whereKey($sedan->id)->exists());
    }

    public function test_coimbatore_seo_title_updates_its_metadata(): void
    {
        settings()->setMany(['structured_data_enabled' => true]);
        $coimbatore = $this->district('coimbatore');
        $this->put(route('admin.districts.tab.update', [$coimbatore, 'seo']), [
            'seo_title' => 'Coimbatore Cabs 24x7', 'seo_description' => 'Book Coimbatore taxis for airport, outstation and local trips.',
        ])->assertRedirect(route('admin.districts.tab', [$coimbatore, 'seo']))->assertSessionHasNoErrors();

        $this->get('/tamil-nadu/coimbatore')
            ->assertSee('<title>Coimbatore Cabs 24x7 | One Way Taxi</title>', false)
            ->assertSee('<meta name="description" content="Book Coimbatore taxis for airport, outstation and local trips.">', false)
            ->assertSee('"@type":"TaxiService"', false)->assertSee('"name":"Coimbatore, Tamil Nadu"', false);
        $this->get('/tamil-nadu/chennai')->assertDontSee('Coimbatore Cabs 24x7');
    }

    public function test_district_faqs_and_routes_are_managed_per_district(): void
    {
        $madurai = $this->district('madurai');
        $this->post(route('admin.districts.faqs.store', $madurai), ['question' => 'Do you pick up from Mattuthavani?', 'answer' => 'Yes, share the bay number when booking.', 'is_active' => '1'])
            ->assertSessionHasNoErrors();
        $faq = DistrictFaq::where('question', 'Do you pick up from Mattuthavani?')->firstOrFail();
        $this->get('/tamil-nadu/madurai')->assertSee('Do you pick up from Mattuthavani?');
        $this->get('/tamil-nadu/salem')->assertDontSee('Do you pick up from Mattuthavani?');

        $this->put(route('admin.district-faqs.update', $faq), ['question' => $faq->question, 'answer' => $faq->answer, 'is_active' => '0'])->assertSessionHasNoErrors();
        $this->get('/tamil-nadu/madurai')->assertDontSee('Do you pick up from Mattuthavani?');
        $this->delete(route('admin.district-faqs.destroy', $faq))->assertRedirect(route('admin.districts.tab', [$madurai, 'faqs']));
        $this->assertModelMissing($faq);

        $this->post(route('admin.districts.routes.store', $madurai), ['from_location' => 'Madurai', 'to_location' => 'Madurai', 'is_active' => '1'])
            ->assertSessionHasErrors('to_location'); // pickup and destination must differ
    }

    public function test_all_38_district_pages_still_load(): void
    {
        $this->assertSame(39, DistrictPage::count()); // 38 Tamil Nadu districts + Puducherry
        foreach (DistrictPage::tamilNadu()->pluck('slug') as $slug) {
            $this->get('/tamil-nadu/'.$slug)->assertOk();
        }
        $this->get('/puducherry')->assertOk();
    }
}
