<?php

namespace Tests\Feature;

use App\Models\CorporateEnquiry;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\User;
use App\Models\VehicleCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regressions found in the admin CMS audit: malformed input must give a normal page or a validation
 * error (never a 500), menu links must follow a page when its URL changes, and status messages
 * must describe what actually happened.
 */
class AdminInputHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::where('is_admin', true)->firstOrFail());
    }

    public static function arrayQueryUrls(): array
    {
        return [
            'corporate search' => ['/admin/corporate-enquiries?q[]=x'],
            'corporate status' => ['/admin/corporate-enquiries?status[]=x'],
            'partner search' => ['/admin/partner-enquiries?q[]=x'],
            'partner status' => ['/admin/partner-enquiries?status[]=x'],
            'messages search' => ['/admin/messages?q[]=x'],
            'pages search' => ['/admin/pages?q[]=x'],
            'cities search' => ['/admin/cities?q[]=x'],
            'routes search' => ['/admin/routes?q[]=x'],
            'media search' => ['/admin/media?q[]=x'],
            'media picker search' => ['/admin/media/picker?q[]=x'],
            'navigation menu preset' => ['/admin/navigation/create?menu[]=x'],
        ];
    }

    /** @dataProvider arrayQueryUrls */
    public function test_array_query_parameters_do_not_break_admin_lists(string $url): void
    {
        $this->get($url)->assertOk();
    }

    public function test_enquiry_search_still_filters_and_keeps_the_search_term(): void
    {
        CorporateEnquiry::create(['reference' => 'CRP-TEST-1', 'company_name' => 'Acme Logistics', 'contact_name' => 'Ravi', 'phone' => '9000000001', 'email' => 'ravi@example.com', 'requirement' => 'airport', 'status' => 'new']);
        CorporateEnquiry::create(['reference' => 'CRP-TEST-2', 'company_name' => 'Zenith Foods', 'contact_name' => 'Asha', 'phone' => '9000000002', 'email' => 'asha@example.com', 'requirement' => 'airport', 'status' => 'new']);

        $this->get('/admin/corporate-enquiries?q=Acme')
            ->assertOk()->assertSee('Acme Logistics')->assertDontSee('Zenith Foods')->assertSee('value="Acme"', false);
    }

    public static function arrayFormFields(): array
    {
        return [
            'page title' => ['admin.pages.store', ['title' => ['x'], 'template' => 'default', 'status' => 'draft'], 'title'],
            'city name' => ['admin.cities.store', ['name' => ['x']], 'name'],
            'service title' => ['admin.services.store', ['title' => ['x'], 'icon' => 'car'], 'title'],
            'vehicle name' => ['admin.vehicles.store', ['name' => ['x'], 'passenger_capacity' => 4, 'luggage_capacity' => 2], 'name'],
            'route slug' => ['admin.routes.store', ['origin_city_id' => 1, 'destination_city_id' => 2, 'slug' => ['x']], 'distance_km'],
        ];
    }

    /** @dataProvider arrayFormFields */
    public function test_array_values_in_text_fields_are_validation_errors(string $route, array $data, string $errorField): void
    {
        $this->from(route('admin.dashboard'))->post(route($route), $data)
            ->assertRedirect(route('admin.dashboard'))->assertSessionHasErrors($errorField);
    }

    public function test_array_slug_falls_back_to_a_slug_made_from_the_name(): void
    {
        $this->post(route('admin.pages.store'), ['title' => 'Audit Page', 'slug' => ['x'], 'template' => 'default', 'status' => 'draft'])->assertSessionHasNoErrors();
        $this->post(route('admin.cities.store'), ['name' => 'Audit City', 'slug' => ['x']])->assertSessionHasNoErrors();
        $this->post(route('admin.services.store'), ['title' => 'Audit Service', 'slug' => ['x'], 'icon' => 'car'])->assertSessionHasNoErrors();
        $this->post(route('admin.vehicles.store'), ['name' => 'Audit Car', 'slug' => ['x'], 'passenger_capacity' => 4, 'luggage_capacity' => 2])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pages', ['slug' => 'audit-page']);
        $this->assertDatabaseHas('cities', ['slug' => 'audit-city']);
        $this->assertDatabaseHas('services', ['slug' => 'audit-service']);
        $this->assertDatabaseHas('vehicle_categories', ['slug' => 'audit-car']);
    }

    public function test_array_values_in_highlights_features_and_charges_do_not_cause_errors(): void
    {
        $this->post(route('admin.services.store'), ['title' => 'Audit Highlights', 'icon' => 'car', 'highlights' => ['a']])->assertRedirect();
        $this->post(route('admin.vehicles.store'), ['name' => 'Audit Features', 'passenger_capacity' => 4, 'luggage_capacity' => 2, 'features' => ['a']])->assertRedirect();

        $vehicle = VehicleCategory::firstOrFail();
        $this->put(route('admin.fares.update', $vehicle), [
            'base_fare' => '100', 'per_km_rate' => '12', 'minimum_km' => 0, 'minimum_fare' => '0', 'driver_allowance' => '0',
            'additional_charges' => [['label' => ['x'], 'amount' => '50', 'note' => ['y']]],
        ])->assertRedirect()->assertSessionHasErrors('additional_charges.0.label');
    }

    public function test_menu_link_follows_a_page_when_its_url_changes(): void
    {
        $payload = ['title' => 'Wedding Cars', 'template' => 'default', 'status' => 'published', 'show_in_nav' => '1', 'body' => '<p>Decorated cars.</p>'];
        $this->post(route('admin.pages.store'), $payload + ['slug' => 'wedding-cars'])->assertSessionHasNoErrors();
        $page = Page::where('slug', 'wedding-cars')->firstOrFail();
        NavigationItem::create(['menu' => 'footer_company', 'label' => 'Weddings', 'url' => '/wedding-cars', 'is_active' => true]);

        $this->put(route('admin.pages.update', $page), $payload + ['slug' => 'wedding-taxis'])->assertSessionHasNoErrors();

        $this->assertSame(0, NavigationItem::where('url', '/wedding-cars')->count());
        $this->assertSame(1, NavigationItem::where('menu', 'header')->where('url', '/wedding-taxis')->count());
        $this->assertSame(1, NavigationItem::where('menu', 'footer_company')->where('url', '/wedding-taxis')->count());
        $this->get('/')->assertSee('/wedding-taxis', false)->assertDontSee('/wedding-cars"', false);

        $this->delete(route('admin.pages.destroy', $page->fresh()));
        $this->assertSame(0, NavigationItem::where('url', 'like', '/wedding-%')->count());
    }

    public function test_hidden_slide_is_not_reported_as_live(): void
    {
        $this->post(route('admin.slides.store'), ['heading' => 'Hidden offer', 'overlay_strength' => 40, 'text_align' => 'left', 'is_active' => '0'])
            ->assertSessionHas('status', 'Slide added. It is hidden until you mark it visible.');

        $this->post(route('admin.slides.store'), ['heading' => 'Live offer', 'overlay_strength' => 40, 'text_align' => 'left', 'is_active' => '1'])
            ->assertSessionHas('status', 'Slide added. It is live on the homepage slider.');
    }
}
