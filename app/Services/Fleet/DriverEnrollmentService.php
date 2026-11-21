<?php

namespace App\Services\Fleet;

use App\Models\DriverEnrollment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Driver + taxi enrollment from the website.
 *
 * Option lists and duplicate checks are read from the fleet MongoDB (same lists as the admin "Add Driver" screen).
 * The enrollment itself is created by the fleet admin (/driverenroll/submit, Model_Driverenroll) with the admin's
 * own add_driver / addtaxi / addassigntaxi code, so documents and uploaded images land exactly where the admin
 * expects them. The driver and taxi stay Deactive (status D) until the admin / dispatcher activates them.
 */
class DriverEnrollmentService
{
    /** Website upload field => admin field name. */
    public const FILES = [
        'photo' => 'photo',
        'licence_front' => 'driver_licence',
        'licence_back' => 'driver_licence_back_side',
        'badge_document' => 'driver_fc',
        'taxi_image' => 'taxi_image',
        'rc_document' => 'vehicle_registration_license',
    ];

    public function __construct(private readonly FleetMongo $mongo, private readonly FleetSettings $settings) {}

    public function available(): bool
    {
        return $this->mongo->available() && config('fleet.admin_url') !== '' && (string) config('fleet.enroll_key') !== '';
    }

    /** Passwords are asked for unless the fleet uses OTP sign-in (siteinfo passenger_otp_signup = 1). */
    public function passwordRequired(): bool
    {
        return (int) $this->settings->get('passenger_otp_signup', 0) !== 1;
    }

    /**
     * Lists for the form, with option values in the admin's "id:label" format.
     *
     * @return array{models: array, states: array, prefixes: array, manufacturers: array, colours: array}
     */
    public function options(): array
    {
        return Cache::remember('fleet.driver_enrollment_options', config('fleet.cache_seconds', 300), function () {
            $models = array_map(fn ($m) => ['value' => $m['_id'], 'label' => $m['model_name']],
                $this->mongo->find('motor_model', ['model_status' => 'A'], ['projection' => ['model_name' => 1], 'sort' => ['_id' => 1]]));

            $states = [];
            foreach ($this->mongo->aggregate('csc', [
                ['$unwind' => '$stateinfo'],
                ['$match' => ['stateinfo.state_status' => ['$ne' => 'T']]],
                ['$project' => ['_id' => 0, 'id' => '$stateinfo.state_id', 'name' => '$stateinfo.state_name']],
            ]) as $s) {
                $states[] = ['value' => $s['id'].':'.$s['name'], 'label' => $s['name']];
            }

            $prefixes = array_map(fn ($p) => ['value' => $p['_id'].':'.$p['plate_prefix'], 'label' => $p['plate_prefix']],
                $this->mongo->find('vehicle_plate_prefix', ['status' => 'A'], ['sort' => ['_id' => 1]]));

            $manufacturers = [];
            foreach ($this->mongo->aggregate('vehicle_info', [
                ['$unwind' => '$vehicle_manufacturer'],
                ['$match' => ['vehicle_manufacturer.status' => 'A']],
                ['$project' => ['_id' => 0, 'id' => '$vehicle_manufacturer._id', 'name' => '$vehicle_manufacturer.name']],
            ]) as $m) {
                $manufacturers[] = ['value' => $m['id'].':'.$m['name'], 'label' => $m['name']];
            }

            $colours = array_map(fn ($c) => ['value' => $c['color'], 'label' => $c['color']],
                $this->mongo->find('vehicle_color', ['status' => 'A'], ['sort' => ['_id' => 1]]));

            return compact('models', 'states', 'prefixes', 'manufacturers', 'colours');
        });
    }

    /** taxi_no exactly as the admin builds it: state name + plate prefix + "/" + plate number. */
    public static function taxiNumber(string $stateValue, string $prefixValue, string $plate): string
    {
        $part = fn (string $v) => explode(':', $v, 2)[1] ?? '';

        return $part($stateValue).$part($prefixValue).'/'.$plate;
    }

    /**
     * Fields already used by another driver / taxi (the admin rejects them too).
     *
     * @return array<string, string> field => message
     */
    public function duplicates(string $phone, string $licenceNo, string $taxiNo, ?string $email): array
    {
        $errors = [];
        if ($this->mongo->count('people', ['phone' => $phone]) > 0) {
            $errors['phone'] = 'This mobile number is already registered. Please call us if you need help with your account.';
        }
        if ($this->mongo->count('people', ['driver_license_id' => $licenceNo]) > 0) {
            $errors['licence_no'] = 'This driving licence number is already registered.';
        }
        if ($this->mongo->count('taxi', ['taxi_no' => $taxiNo]) > 0) {
            $errors['plate_number'] = 'This vehicle number is already registered.';
        }
        if ($email && $this->mongo->count('people', ['email' => ['$regex' => '^'.preg_quote($email).'$', '$options' => 'i']]) > 0) {
            $errors['email'] = 'This email address is already registered.';
        }

        return $errors;
    }

    /**
     * Send a saved enrollment to the fleet admin. Updates the record with the result.
     *
     * @return array{ok: bool, errors: array<string, string>}
     */
    public function send(DriverEnrollment $e): array
    {
        try {
            $request = Http::timeout(60)->acceptJson()->withHeaders(['X-Enroll-Key' => (string) config('fleet.enroll_key')]);
            foreach (self::FILES as $ours => $theirs) {
                $path = $e->files[$ours] ?? null;
                if ($path && Storage::disk('local')->exists($path)) {
                    $request = $request->attach($theirs, Storage::disk('local')->get($path), basename($path));
                }
            }

            $response = $request->post(config('fleet.admin_url').'/driverenroll/submit', array_filter([
                'firstname' => $e->first_name,
                'lastname' => $e->last_name,
                'gender' => $e->gender,
                'dob' => $e->dob?->format('Y-m-d'),
                'phone' => $e->phone,
                'email' => $e->email,
                'password' => $e->password,
                'repassword' => $e->password,
                'national_id' => $e->national_id,
                'address' => $e->address,
                'driver_license_id' => $e->licence_no,
                'driver_license_expire_date' => $e->licence_expiry?->format('Y-m-d'),
                'driver_fc_expire_date' => $e->badge_expiry?->format('Y-m-d'),
                'taxi_model' => $e->fleet_model_id,
                'state_list' => $e->state_value,
                'plate_prefix_list' => $e->prefix_value,
                'taxi_plate_number' => $e->plate_number,
                'manufacturer_list' => $e->manufacturer_value,
                'taxi_manufacturing_year' => $e->manufacturing_year,
                'taxi_colour' => $e->colour,
                'taxi_owner_name' => $e->owner_name,
            ], fn ($v) => $v !== null && $v !== ''));

            $body = $response->json() ?? [];
            if ($response->successful() && ($body['status'] ?? 0) == 1) {
                $e->forceFill([
                    'status' => DriverEnrollment::SENT,
                    'fleet_driver_id' => $body['driver_id'] ?? null,
                    'fleet_taxi_id' => $body['taxi_id'] ?? null,
                    'sync_error' => null,
                    'password' => null, // only needed until the fleet account exists
                    'sent_at' => now(),
                ])->save();

                return ['ok' => true, 'errors' => []];
            }

            $errors = array_map(fn ($m) => is_array($m) ? implode(' ', $m) : (string) $m, (array) ($body['errors'] ?? ['server' => 'Unexpected response ('.$response->status().').']));
            $e->forceFill(['status' => DriverEnrollment::FAILED, 'sync_error' => mb_substr(implode(' | ', $errors), 0, 490)])->save();

            return ['ok' => false, 'errors' => $errors];
        } catch (\Throwable $ex) {
            Log::error('Driver enrollment send failed', ['id' => $e->id, 'error' => $ex->getMessage()]);
            $e->forceFill(['status' => DriverEnrollment::FAILED, 'sync_error' => mb_substr($ex->getMessage(), 0, 490)])->save();

            return ['ok' => false, 'errors' => ['server' => 'The fleet system could not be reached.']];
        }
    }
}
