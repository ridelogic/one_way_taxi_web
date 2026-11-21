<?php

namespace App\Services\Fleet;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use MongoDB\BSON\UTCDateTime;

/**
 * Saves a website booking in the fleet MongoDB as an enquiry (collection enquiry_logs), shaped exactly like a
 * passengers_logs trip — a port of Model_FleeteraTaxidispatch::addbooking() for a single "Book Later" booking,
 * with booking_from = 0 (website) and bookby = 1 (passenger). The dispatcher reviews it under
 * Dispatcher → Manage Enquiry and moves it to Manage Trips (passengers_logs) — see Model_Enquiry in the admin.
 */
class FleetBookingService
{
    public const COLLECTION = 'enquiry_logs';

    public function __construct(
        private readonly FleetMongo $mongo,
        private readonly FleetSettings $settings,
        private readonly FleetFareService $fares,
        private readonly ZoneResolver $zones,
    ) {}

    /**
     * @param  array{name: ?string, phone: string, email: ?string, pickup_location: string, drop_location: ?string, pickup_lat: float, pickup_lng: float, drop_lat: ?float, drop_lng: ?float, pickup_at: CarbonImmutable, passengers: ?int, notes: ?string, city_name: ?string, reference: string}  $b
     * @param  array  $fleet  the 'fleet' part of FleetFareService::estimate()
     * @return int the enquiry_logs _id (enquiry id)
     */
    public function create(array $b, array $fleet): int
    {
        $tz = config('fleet.timezone');
        $now = CarbonImmutable::now($tz);
        $companyId = (int) config('fleet.company_id', 1);
        $tripType = (int) $fleet['trip_type'];
        $modelId = (int) $fleet['model_id'];

        $passengerId = $this->passenger($b, $now);

        $model = $this->mongo->findOne('motor_model', ['_id' => $modelId], ['projection' => [
            'model_commission' => 1, 'local_model_admin_commission' => 1, 'rental_model_admin_commission' => 1,
            'outstation_model_admin_commission' => 1, 'daily_rental_model_admin_commission' => 1,
        ]]) ?? [];
        $hasModelCommission = ! empty($model['local_model_admin_commission']) || ! empty($model['rental_model_admin_commission'])
            || ! empty($model['outstation_model_admin_commission']) || ! empty($model['daily_rental_model_admin_commission']);
        $commission = fn ($key) => (float) ($hasModelCommission ? ($model[$key] ?? 0) : $this->settings->adminCommission());

        $distanceKm = (float) $fleet['distance_km'];
        $durationSec = (int) $fleet['duration_sec'];
        $isOutstation = $tripType === FleetFareService::OUTSTATION;
        $isRoundTrip = $isOutstation && (int) $fleet['os_trip_type'] === 2;

        $logId = $this->mongo->nextId(self::COLLECTION);
        $doc = [
            '_id' => $logId,
            'enquiry_status' => 'P',      // P pending → M moved to Manage Trips / C cancelled (admin Manage Enquiry)
            'enquiry_source' => 'website',
            'booking_key' => strtoupper(Str::random(15)),
            'booking_otp' => (int) $this->settings->get('enable_odometer', 0) === 1 ? (string) random_int(1000, 9999) : '',
            'passengers_id' => $passengerId,
            'driver_id' => 0,
            'taxi_id' => 0,
            'company_id' => $companyId,
            'corporate_company_id' => 0,
            'current_location' => $b['pickup_location'],
            'pickup_latitude' => $b['pickup_lat'],
            'pickup_longitude' => $b['pickup_lng'],
            'loc' => ['type' => 'Point', 'coordinates' => [(float) $b['pickup_lng'], (float) $b['pickup_lat']]],
            'drop_location' => (string) ($b['drop_location'] ?? ''),
            'drop_latitude' => $b['drop_lat'] ?? '',
            'drop_longitude' => $b['drop_lng'] ?? '',
            'org_drop_location' => (string) ($b['drop_location'] ?? ''),
            'org_drop_latitude' => (string) ($b['drop_lat'] ?? ''),
            'org_drop_longitude' => (string) ($b['drop_lng'] ?? ''),
            'pickup_time' => $this->date($b['pickup_at']),
            'no_passengers' => $b['passengers'],
            // The admin doubles approx distance/duration for every Outstation booking.
            'approx_distance' => $isOutstation ? 2 * $distanceKm : $distanceKm,
            'approx_duration' => $isOutstation ? 2 * ($durationSec / 60) : $durationSec / 60,
            'approx_fare' => in_array($tripType, [FleetFareService::RENTAL, FleetFareService::OUTSTATION], true) ? (float) $fleet['subtotal'] : (float) $fleet['total'],
            'search_city' => $this->defaultCityId(),
            'notes_driver' => (string) ($b['notes'] ?? ''),
            'notes_passenger' => '',
            'alternate_phone' => '',
            'faretype' => 0,
            'fixedprice' => 0.0,
            'bookingtype' => 2,           // later booking — the dispatcher assigns the driver
            'luggage' => 0,
            'bookby' => 1,                // booked by the passenger
            'booking_from' => 0,          // website
            'operator_id' => 0,
            'travel_status' => 0,
            'rating' => 0,
            'taxi_modelid' => $modelId,
            'recurrent_type' => 1,
            'company_tax' => (int) $fleet['tax_rate'],
            'account_id' => 0,
            'accgroup_id' => 0,
            'createdate' => $this->date($now),
            'logs' => [[
                'log_userid' => 0,
                'log_message' => "Enquiry {$logId} is received from the website.",
                'log_booking' => "Enquiry {$logId} is received from the website ({$b['reference']}).",
                'log_createdate' => $this->date($now),
            ]],
            'distance_unit' => $this->settings->unitName(),
            'approx_duration_sec' => $isRoundTrip ? $durationSec * 2 : $durationSec,
            'city_name' => (string) ($b['city_name'] ?? ''),
            'promocode' => '',
            'promo_type' => '',
            'promo_discount' => '',
            'rental_outstation' => '0',
            'trip_type' => (string) $tripType,
            'local_model_admin_commission' => $commission('local_model_admin_commission'),
            'rental_model_admin_commission' => $commission('rental_model_admin_commission'),
            'outstation_model_admin_commission' => $commission('outstation_model_admin_commission'),
            'daily_rental_model_admin_commission' => $commission('daily_rental_model_admin_commission'),
            'model_commission_enable' => $hasModelCommission ? ($model['model_commission'] ?? 0) : 0,
            'fixed_fare_select' => 0,
            'fare_info' => $this->fares->commonModelFare($modelId, (float) $b['pickup_lat'], (float) $b['pickup_lng']) ?? [],
            'now_after' => 1,
            'taxi_submodel' => 0,
            'taxi_submodelname' => '0',
            'driver_noshow_time' => (string) $this->settings->get('driver_noshow_time_mts', 0),
            'passenger_grace_time' => (string) $this->settings->get('passenger_grace_time_mts', 0),
            'baby_seater1' => 0, 'baby_seater_name1' => '', 'baby_seatercount1' => 0,
            'baby_seater2' => 0, 'baby_seater_name2' => '', 'baby_seatercount2' => 0,
            'baby_seater3' => 0, 'baby_seater_name3' => '', 'baby_seatercount3' => 0,
            'babyfare' => 0,
            'website_reference' => $b['reference'],
        ];

        if ($tripType !== FleetFareService::LOCAL) {
            $plan = $fleet['plan'] ?? [];
            $minutes = $durationSec / 60;
            $doc['fare_splitup_details'] = [
                'distance' => $distanceKm,
                'trip_fare' => 0, 'nightfare_applicable' => 0, 'nightfare' => 0, 'eveningfare_applicable' => 0, 'eveningfare' => 0,
                'waiting_time' => 0, 'waiting_cost' => 0,
                'tax_amount' => (float) $fleet['tax_amount'],
                'subtotal_fare' => (float) $fleet['subtotal'],
                'company_tax' => (float) $fleet['tax_rate'],
                'waiting_per_minute' => 0, 'roundtrip' => 0,
                'minutes_traveled' => round($minutes),
                'minutes_fare' => 0,
                'base_fare' => (float) ($plan['base_fare'] ?? 0),
                'street_pickup' => 0, 'fare_calculation_type' => 0, 'model_fare_type' => 0,
                'trip_type' => (string) $tripType,
                'os_plan_fare' => (float) ($plan['base_fare'] ?? 0),
                'os_plan_distance' => (float) ($plan['distance'] ?? 0),
                'os_plan_duration' => $plan['plan_duration'] ?? 0,
                'os_additional_fare_per_distance' => (float) ($plan['additional_fare_per_distance'] ?? 0),
                'os_additional_fare_per_hour' => (float) ($plan['additional_fare_per_hour'] ?? 0),
                'os_driver_beta' => (float) ($plan['driver_beta'] ?? 0),
                'os_duration' => $minutes,
                'os_distance_unit' => $this->settings->unitName(),
                'distance_fare' => max(0, ($distanceKm - (float) ($plan['distance'] ?? 0)) * (float) ($plan['additional_fare_per_distance'] ?? 0)),
            ];
            $doc += [
                'rent_out_tour_id' => (int) ($plan['id'] ?? 0),
                'rent_out_source' => (string) ($plan['source'] ?? 'common'),
                'base_fare' => (float) ($plan['base_fare'] ?? 0),
                'plan_distance' => (float) ($plan['distance'] ?? 0),
                'plan_duration' => $plan['plan_duration'] ?? 0,
                'plan_distance_unit' => (string) ($plan['distance_unit'] ?? $this->settings->unitName()),
                'additional_fare_per_distance' => (float) ($plan['additional_fare_per_distance'] ?? 0),
                'additional_fare_per_hour' => (float) ($plan['additional_fare_per_hour'] ?? 0),
                'driver_beta' => (float) ($plan['driver_beta'] ?? 0),
            ];
            $doc['rental_outstation'] = $tripType === FleetFareService::RENTAL ? '1' : '2';
            if ($isOutstation) {
                $doc['os_trip_type'] = (string) $fleet['os_trip_type'];
                $doc['os_day_count'] = (string) ($fleet['days'] ?? 1);
            }
        }

        $doc['booking_zone'] = $this->zones->snapshot((float) $b['pickup_lat'], (float) $b['pickup_lng'], $b['drop_lat'] ?? null, $b['drop_lng'] ?? null)
            + ['checked_at' => $now->format('Y-m-d H:i:s')];

        // Denormalised info the dispatcher screens read (Model_Commonmodel::get_trip_location()).
        $doc += $this->tripLocationInfo($passengerId, $companyId);

        // Trip count and split details are added when the dispatcher moves the enquiry to Manage Trips.
        $this->mongo->collection(self::COLLECTION)->insertOne($doc);

        return $logId;
    }

    /** Existing passenger by phone (name/email refreshed), or a new one — as addbooking() does. */
    private function passenger(array $b, CarbonImmutable $now): int
    {
        $phone = preg_replace('/\D/', '', $b['phone']);
        $phone = strlen($phone) > 10 ? substr($phone, -10) : $phone;
        $name = trim((string) ($b['name'] ?? '')) ?: 'Website customer';

        $existing = $this->mongo->findOne('passengers', ['phone' => $phone], ['projection' => ['_id' => 1, 'name' => 1]]);
        if ($existing) {
            $set = ['country_code' => '+91'];
            if (trim((string) ($b['name'] ?? '')) !== '') {
                $set['name'] = $name;
            }
            if (! empty($b['email'])) {
                $set['email'] = $b['email'];
            }
            $this->mongo->collection('passengers')->updateOne(['_id' => (int) $existing['_id']], ['$set' => $set]);

            return (int) $existing['_id'];
        }

        $referralOn = (string) $this->settings->get('referral_settings', '1') !== '2';
        $id = $this->mongo->nextId('passengers');
        $this->mongo->collection('passengers')->insertOne([
            '_id' => $id,
            'name' => $name,
            'email' => (string) ($b['email'] ?? ''),
            'country_code' => '+91',
            'phone' => $phone,
            'password' => md5(Str::random(12)), // the passenger signs in to the app with OTP / forgot password
            'created_date' => $this->date($now),
            'activation_key' => Str::random(10),
            'user_status' => 'A',
            'activation_status' => 1,
            'referral_code' => $referralOn ? 'RP'.strtoupper(Str::random(6)) : '',
            'referral_code_amount' => $referralOn ? (float) $this->settings->get('referral_amount', 0) : 0.0,
            'referral_code_limit' => 1,
            'total_trip_count' => 0,
            'registered_from' => 'website',
        ]);

        return $id;
    }

    /** The default city (csc stateinfo.cityinfo.default = 1), used as search_city like the admin does without a city name. */
    private function defaultCityId(): int
    {
        $res = $this->mongo->aggregate('csc', [
            ['$unwind' => '$stateinfo'],
            ['$unwind' => '$stateinfo.cityinfo'],
            ['$match' => ['stateinfo.cityinfo.default' => 1]],
            ['$project' => ['_id' => 0, 'city_id' => '$stateinfo.cityinfo.city_id']],
            ['$limit' => 1],
        ]);

        return (int) ($res[0]['city_id'] ?? 0);
    }

    private function tripLocationInfo(int $passengerId, int $companyId): array
    {
        $p = $this->mongo->findOne('passengers', ['_id' => $passengerId]) ?? [];
        $c = $this->mongo->findOne('company', ['_id' => $companyId]) ?? [];

        return [
            'taxi_info' => ['taxi_id' => '', 'taxi_no' => '', 'taxi_model_id' => '', 'taxi_model_name' => '', 'taxi_model_cancellation_fare' => 0],
            'driver_info' => [],
            'company_info' => [
                'company_name' => (string) ($c['companydetails']['company_name'] ?? ''),
                'company_type' => (string) ($c['company_type'] ?? ''),
                'default_unit' => (string) ($c['companyinfo']['default_unit'] ?? ''),
                'fare_calculation_type' => (string) ($c['companyinfo']['fare_calculation_type'] ?? ''),
                'company_tax' => (string) ($c['companyinfo']['company_tax'] ?? ''),
                'userid' => (int) ($c['companydetails']['userid'] ?? 0),
                'company_brand_type' => (string) ($c['companyinfo']['company_brand_type'] ?? ''),
                'company_id' => $companyId,
                'company_cancellation_fare' => (float) ($c['companyinfo']['cancellation_fare'] ?? 0),
            ],
            'passenger_info' => [
                'passenger_name' => (string) ($p['name'] ?? ''),
                'current_language' => (string) ($p['current_language'] ?? ''),
                'passenger_phone' => (string) ($p['phone'] ?? ''),
                'passenger_email' => (string) ($p['email'] ?? ''),
                'department' => '',
                'passenger_country_code' => (string) ($p['country_code'] ?? '+91'),
                'passenger_profile_picture' => (string) ($p['profile_image'] ?? ''),
            ],
            'transaction_info' => (object) [],
            'single_db' => 1,
        ];
    }

    private function date(\DateTimeInterface $at): UTCDateTime
    {
        return new UTCDateTime($at->getTimestamp() * 1000);
    }
}
