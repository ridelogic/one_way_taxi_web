<?php

/*
 * Fleet backend (the OnewayTaxi admin / dispatcher, driver app and passenger app).
 *
 * Fare configuration and bookings live in that system's MongoDB. When enabled, the website
 * calculates fares with the same rules as the admin "Add Booking" screen and also creates the
 * booking in MongoDB so it appears in the dispatcher's Manage Booking list.
 */
return [
    'enabled' => (bool) env('FLEET_MONGO_ENABLED', false),

    'mongo' => [
        'host' => env('FLEET_MONGO_HOST', '127.0.0.1:27017'),
        'database' => env('FLEET_MONGO_DATABASE', ''),
        'username' => env('FLEET_MONGO_USERNAME', ''),
        'password' => env('FLEET_MONGO_PASSWORD', ''),
        'auth_source' => env('FLEET_MONGO_AUTH_SOURCE', 'admin'),
        'auth_mechanism' => env('FLEET_MONGO_AUTH_MECHANISM', 'SCRAM-SHA-1'),
        'timeout_ms' => (int) env('FLEET_MONGO_TIMEOUT_MS', 5000),
    ],

    'node_server' => env('FLEET_NODE_SERVER', ''),
    'subdomain_name' => env('FLEET_SUBDOMAIN_NAME', ''),
    'timezone' => env('FLEET_TIMEZONE', 'Asia/Kolkata'),

    // Company that website bookings belong to (passengers_logs.company_id). 1 = the main company.
    'company_id' => (int) env('FLEET_COMPANY_ID', 1),

    // Overrides the Google key stored in the fleet siteinfo (web_google_map_key). The key must allow this site's domain.
    'google_maps_key' => env('FLEET_GOOGLE_MAPS_KEY', ''),

    // Night / evening / peak surcharges: false = evaluated at the current time (what the admin Add Booking
    // preview does), true = evaluated at the customer's pickup time.
    'surcharge_at_pickup' => (bool) env('FLEET_SURCHARGE_AT_PICKUP', false),

    // Seconds to cache siteinfo settings and the vehicle model list.
    'cache_seconds' => (int) env('FLEET_CACHE_SECONDS', 300),

    // Driver enrollment: the website sends the form to the fleet admin's /driverenroll/submit endpoint, which creates
    // the driver + taxi with the admin's own code, Deactive (status D). The key must match the admin's
    // DRIVER_ENROLL_API_KEY (or WEB_API_KEY when that is not defined).
    'admin_url' => rtrim((string) env('FLEET_ADMIN_URL', ''), '/'),
    'enroll_key' => env('FLEET_ENROLL_KEY', ''),

    // Also create website bookings in MongoDB (dispatcher Manage Booking). The MySQL copy is always kept.
    'push_bookings' => (bool) env('FLEET_PUSH_BOOKINGS', true),
];
