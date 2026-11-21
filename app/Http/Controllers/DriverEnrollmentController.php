<?php

namespace App\Http\Controllers;

use App\Http\Requests\DriverEnrollmentRequest;
use App\Models\DriverEnrollment;
use App\Services\Fleet\DriverEnrollmentService;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** "Drive with us": driver + taxi enrollment, created in the fleet admin as a Deactive driver. */
class DriverEnrollmentController extends Controller
{
    private const SESSION_KEY = 'driver_enrollment_reference';

    /** Fleet admin field => website field, for showing the admin's validation messages on the right input. */
    private const ADMIN_FIELDS = [
        'firstname' => 'first_name', 'lastname' => 'last_name', 'dob' => 'dob', 'phone' => 'phone', 'email' => 'email',
        'password' => 'password', 'repassword' => 'password', 'national_id' => 'national_id',
        'driver_license_id' => 'licence_no', 'driver_license_expire_date' => 'licence_expiry', 'driver_fc_expire_date' => 'badge_expiry',
        'photo' => 'photo', 'driver_licence' => 'licence_front', 'driver_licence_back_side' => 'licence_back', 'driver_fc' => 'badge_document',
        'taxi_model' => 'fleet_model_id', 'taxi_no' => 'plate_number', 'state_list' => 'state_value', 'plate_prefix_list' => 'prefix_value',
        'taxi_plate_number' => 'plate_number', 'manufacturer_list' => 'manufacturer_value', 'taxi_manufacturing_year' => 'manufacturing_year',
        'taxi_colour' => 'colour', 'taxi_owner_name' => 'owner_name', 'taxi_image' => 'taxi_image', 'vehicle_registration_license' => 'rc_document',
    ];

    public function __construct(private readonly DriverEnrollmentService $service) {}

    public function create(): View
    {
        $available = $this->service->available();
        $options = ['models' => [], 'states' => [], 'prefixes' => [], 'manufacturers' => [], 'colours' => []];
        if ($available) {
            try {
                $options = $this->service->options();
            } catch (\Throwable $e) {
                Log::error('Driver enrollment options failed', ['error' => $e->getMessage()]);
                $available = false;
            }
        }

        return view('drivers.enroll', [
            'available' => $available,
            'options' => $options,
            'passwordRequired' => $available && $this->service->passwordRequired(),
            'seo' => Seo::make('Drive With Us — Driver & Taxi Enrollment', 'Own a taxi? Enroll as a driver partner with your licence and vehicle details. Our team verifies and activates your account.'),
        ]);
    }

    public function store(DriverEnrollmentRequest $request): RedirectResponse
    {
        abort_unless($this->service->available(), 503);

        $data = $request->validated();
        $phone = $request->normalizedPhone();
        $taxiNo = DriverEnrollmentService::taxiNumber($data['state_value'], $data['prefix_value'], $data['plate_number']);

        $duplicates = $this->service->duplicates($phone, $data['licence_no'], $taxiNo, $data['email'] ?? null);
        if ($duplicates) {
            return back()->withErrors($duplicates)->withInput($request->except(['password', 'password_confirmation']));
        }

        $reference = DriverEnrollment::generateReference();
        $files = [];
        foreach (array_keys(DriverEnrollmentService::FILES) as $field) {
            if ($request->hasFile($field)) {
                $files[$field] = $request->file($field)->storeAs('driver-enrollments/'.$reference, $field.'.'.$request->file($field)->extension(), 'local');
            }
        }

        $model = collect($this->service->options()['models'])->firstWhere('value', (int) $data['fleet_model_id']);
        $enrollment = DriverEnrollment::create([
            'reference' => $reference,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'gender' => $data['gender'],
            'dob' => $data['dob'],
            'phone' => $phone,
            'email' => isset($data['email']) ? mb_strtolower($data['email']) : null,
            'password' => $data['password'] ?? null,
            'national_id' => $data['national_id'] ?? null,
            'address' => $data['address'] ?? null,
            'licence_no' => $data['licence_no'],
            'licence_expiry' => $data['licence_expiry'],
            'badge_expiry' => $data['badge_expiry'],
            'fleet_model_id' => (int) $data['fleet_model_id'],
            'model_name' => $model['label'] ?? null,
            'state_value' => $data['state_value'],
            'prefix_value' => $data['prefix_value'],
            'plate_number' => $data['plate_number'],
            'taxi_no' => $taxiNo,
            'manufacturer_value' => $data['manufacturer_value'],
            'manufacturing_year' => (int) $data['manufacturing_year'],
            'colour' => $data['colour'] ?? null,
            'owner_name' => $data['owner_name'] ?? null,
            'files' => $files,
            'status' => DriverEnrollment::PENDING,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
        ]);

        $result = $this->service->send($enrollment);

        // The fleet admin rejected a field: show it on the form and discard this attempt.
        $fieldErrors = array_intersect_key($result['errors'], self::ADMIN_FIELDS);
        if (! $result['ok'] && $fieldErrors) {
            $mapped = [];
            foreach ($fieldErrors as $adminField => $message) {
                $mapped[self::ADMIN_FIELDS[$adminField]] = $message;
            }
            Storage::disk('local')->deleteDirectory('driver-enrollments/'.$reference);
            $enrollment->delete();

            return back()->withErrors($mapped)->withInput($request->except(['password', 'password_confirmation']));
        }

        // Saved either way: if the fleet system was unreachable the team can resend it from Admin → Driver enrollments.
        $request->session()->put(self::SESSION_KEY, $reference);

        return redirect()->route('drivers.enroll.thanks');
    }

    public function thanks(Request $request): View
    {
        $reference = $request->session()->get(self::SESSION_KEY);
        abort_unless($reference, 404);

        return view('drivers.enroll-thanks', [
            'enrollment' => DriverEnrollment::where('reference', $reference)->firstOrFail(),
            'seo' => Seo::make('Enrollment Received')->noindex(),
        ]);
    }
}
