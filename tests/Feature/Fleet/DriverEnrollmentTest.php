<?php

namespace Tests\Feature\Fleet;

use App\Models\DriverEnrollment;
use App\Models\User;
use App\Services\Fleet\FleetMongo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** "Drive with us": website driver + taxi enrollment, created in the fleet admin as a Deactive driver. */
class DriverEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private FakeFleetMongo $mongo;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Storage::fake('local');
        config(['fleet.enabled' => true, 'fleet.admin_url' => 'https://fleet.test', 'fleet.enroll_key' => 'secret-key']);

        $this->mongo = new FakeFleetMongo([
            'siteinfo' => [['_id' => 1, 'passenger_otp_signup' => 0]],
            'motor_model' => [['_id' => 1, 'model_name' => 'SUV', 'model_status' => 'A'], ['_id' => 3, 'model_name' => 'Sedan', 'model_status' => 'A']],
            'vehicle_plate_prefix' => [['_id' => 1, 'plate_prefix' => 'TN', 'status' => 'A']],
            'vehicle_color' => [['_id' => 2, 'color' => '#000000', 'status' => 'A']],
            'people' => [['_id' => 5, 'user_type' => 'D', 'phone' => '9876500000', 'driver_license_id' => 'TN0120200000001']],
            'taxi' => [['_id' => 18, 'taxi_no' => 'TamilnaduTN/TN43001']],
        ], [
            'csc' => [['id' => 1, 'name' => 'Tamilnadu']],
            'vehicle_info' => [['id' => 1, 'name' => 'Tata'], ['id' => 3, 'name' => 'Honda']],
        ]);
        $this->app->instance(FleetMongo::class, $this->mongo);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ravi', 'last_name' => 'Kumar', 'gender' => 'Male', 'dob' => now()->subYears(30)->toDateString(),
            'phone' => '98400 12345', 'email' => 'ravi@example.com', 'password' => 'secret12', 'password_confirmation' => 'secret12',
            'licence_no' => 'tn01 2021 0000099', 'licence_expiry' => now()->addYears(5)->toDateString(), 'badge_expiry' => now()->addYear()->toDateString(),
            'photo' => UploadedFile::fake()->image('me.jpg'), 'licence_front' => UploadedFile::fake()->image('lf.jpg'),
            'licence_back' => UploadedFile::fake()->image('lb.png'), 'badge_document' => UploadedFile::fake()->create('fc.pdf', 100, 'application/pdf'),
            'fleet_model_id' => 3, 'state_value' => '1:Tamilnadu', 'prefix_value' => '1:TN', 'plate_number' => 'tn 43 ab 1234',
            'manufacturer_value' => '3:Honda', 'manufacturing_year' => 2021, 'colour' => '#000000', 'owner_name' => 'Ravi Kumar',
            'taxi_image' => UploadedFile::fake()->image('car.jpg'), 'rc_document' => UploadedFile::fake()->image('rc.jpg'),
            'consent' => '1',
        ], $overrides);
    }

    public function test_form_lists_the_fleet_admin_options(): void
    {
        $this->get('/drive-with-us')->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('<option value="3">Sedan</option>', false)
            ->assertSee('<option value="1:Tamilnadu">Tamilnadu</option>', false)
            ->assertSee('<option value="3:Honda">Honda</option>', false)
            ->assertSee('name="password"', false);
    }

    public function test_enrollment_is_sent_to_the_fleet_admin_and_saved(): void
    {
        Http::fake(['fleet.test/driverenroll/submit' => Http::response(['status' => 1, 'driver_id' => 70, 'taxi_id' => 19])]);

        $this->post('/drive-with-us', $this->payload())->assertRedirect(route('drivers.enroll.thanks'));

        Http::assertSent(function (HttpRequest $r) {
            $fields = collect($r->data())->mapWithKeys(fn ($p) => [$p['name'] => $p['contents']]);

            return $r->url() === 'https://fleet.test/driverenroll/submit'
                && $r->hasHeader('X-Enroll-Key', 'secret-key')
                && $fields['firstname'] === 'Ravi' && $fields['phone'] === '9840012345'
                && $fields['driver_license_id'] === 'TN01 2021 0000099'
                && $fields['state_list'] === '1:Tamilnadu' && $fields['taxi_plate_number'] === 'TN43AB1234'
                && $fields['taxi_model'] == 3 && $fields['password'] === 'secret12'
                && $fields->has('driver_licence') && $fields->has('driver_licence_back_side') && $fields->has('driver_fc')
                && $fields->has('photo') && $fields->has('taxi_image') && $fields->has('vehicle_registration_license');
        });

        $e = DriverEnrollment::first();
        $this->assertSame('sent', $e->status);
        $this->assertSame(70, (int) $e->fleet_driver_id);
        $this->assertSame('TamilnaduTN/TN43AB1234', $e->taxi_no);
        $this->assertNull($e->password); // not kept once the fleet account exists

        $this->get(route('drivers.enroll.thanks'))->assertOk()->assertSee($e->reference)->assertSee('Inactive — awaiting verification');
    }

    public function test_already_registered_phone_licence_and_vehicle_are_rejected_before_sending(): void
    {
        Http::fake();

        $this->post('/drive-with-us', $this->payload(['phone' => '9876500000', 'licence_no' => 'TN0120200000001', 'plate_number' => 'TN43001']))
            ->assertSessionHasErrors(['phone', 'licence_no', 'plate_number']);

        Http::assertNothingSent();
        $this->assertSame(0, DriverEnrollment::count());
    }

    public function test_fleet_admin_validation_errors_are_shown_on_the_form(): void
    {
        Http::fake(['fleet.test/*' => Http::response(['status' => 0, 'errors' => ['driver_license_id' => 'Licence number already exists']], 422)]);

        $this->post('/drive-with-us', $this->payload())->assertSessionHasErrors(['licence_no' => 'Licence number already exists']);
        $this->assertSame(0, DriverEnrollment::count()); // the attempt is discarded so the driver can correct and resubmit
    }

    public function test_unreachable_fleet_admin_keeps_the_enrollment_for_resending(): void
    {
        Http::fake(['fleet.test/*' => Http::sequence()
            ->push('Bad gateway', 502)
            ->push(['status' => 1, 'driver_id' => 71, 'taxi_id' => 20])]);

        $this->post('/drive-with-us', $this->payload())->assertRedirect(route('drivers.enroll.thanks'));
        $e = DriverEnrollment::first();
        $this->assertSame('failed', $e->status);
        $this->assertSame('secret12', $e->password); // kept (encrypted) so the resend can create the account

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->post(route('admin.driver-enrollments.resend', $e))->assertRedirect();

        $this->assertSame('sent', $e->fresh()->status);
        $this->assertSame(71, (int) $e->fresh()->fleet_driver_id);
    }

    public function test_drivers_must_be_adults_with_valid_documents(): void
    {
        Http::fake();

        $this->post('/drive-with-us', $this->payload([
            'dob' => now()->subYears(17)->toDateString(),
            'first_name' => 'Ravi Kumar',
            'licence_front' => UploadedFile::fake()->create('lf.exe', 10),
            'state_value' => '9:Kerala',
        ]))->assertSessionHasErrors(['dob', 'first_name', 'licence_front', 'state_value']);

        Http::assertNothingSent();
    }
}
