<?php

/*
|--------------------------------------------------------------------------
| Global site settings schema
|--------------------------------------------------------------------------
| Drives both the admin settings form and default values. Values are stored
| in the `site_settings` table and read through App\Support\Settings.
| Field types: text, textarea, email, tel, url, number, boolean, media, select.
*/

return [
    'business' => [
        'label' => 'Business',
        'fields' => [
            'business_name' => ['label' => 'Business name', 'type' => 'text', 'default' => 'One Way Taxi', 'rules' => 'required|string|max:100'],
            'tagline' => ['label' => 'Tagline', 'type' => 'text', 'default' => 'Your Journey. Our Priority.', 'rules' => 'nullable|string|max:120'],
            'logo_media_id' => ['label' => 'Logo image (optional — the built-in taxi mark is used when empty)', 'type' => 'media'],
            'favicon_media_id' => ['label' => 'Favicon (PNG/ICO, square)', 'type' => 'media'],
            'brand_gold' => ['label' => 'Brand colour — primary blue (buttons & highlights)', 'type' => 'color', 'default' => '#0D6EFD', 'rules' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/']],
            'brand_green' => ['label' => 'Brand colour — dark navy (headings, hero, footer)', 'type' => 'color', 'default' => '#062B55', 'rules' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/']],
            'brand_green_fresh' => ['label' => 'Brand colour — link blue (links & icons on white)', 'type' => 'color', 'default' => '#0D6EFD', 'rules' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/']],
            'currency' => ['label' => 'Currency', 'type' => 'select', 'options' => ['INR' => 'INR — Indian Rupee (₹)'], 'default' => 'INR', 'rules' => 'required|in:INR'],
            'demo_mode' => ['label' => 'Show “sample data” labels on fares and statistics (turn off once the figures are verified)', 'type' => 'boolean', 'default' => '1'],
        ],
    ],
    'contact' => [
        'label' => 'Contact details',
        'fields' => [
            'phone' => ['label' => 'Primary phone (shown in header)', 'type' => 'tel', 'default' => '+91 00000 00000', 'rules' => 'nullable|string|max:30'],
            'phone_secondary' => ['label' => 'Secondary phone', 'type' => 'tel', 'rules' => 'nullable|string|max:30'],
            'whatsapp' => ['label' => 'WhatsApp number (digits with country code, e.g. 919000000000)', 'type' => 'text', 'rules' => 'nullable|regex:/^[0-9]{8,15}$/'],
            'email' => ['label' => 'Email', 'type' => 'email', 'default' => 'bookings@example.com', 'rules' => 'nullable|email|max:150'],
            'address' => ['label' => 'Address', 'type' => 'textarea', 'default' => "Update your registered office address\nin Admin → Settings → Contact details", 'rules' => 'nullable|string|max:500'],
            'business_hours' => ['label' => 'Business hours', 'type' => 'text', 'default' => 'Open 24 hours, 7 days a week', 'rules' => 'nullable|string|max:120'],
            'map_embed_url' => ['label' => 'Google Maps embed URL (optional)', 'type' => 'url', 'rules' => 'nullable|url|max:1000|starts_with:https://www.google.com/maps/embed'],
        ],
    ],
    'social' => [
        'label' => 'Social media',
        'fields' => [
            'social_facebook' => ['label' => 'Facebook URL', 'type' => 'url', 'rules' => 'nullable|url|max:255'],
            'social_instagram' => ['label' => 'Instagram URL', 'type' => 'url', 'rules' => 'nullable|url|max:255'],
            'social_x' => ['label' => 'X (Twitter) URL', 'type' => 'url', 'rules' => 'nullable|url|max:255'],
            'social_youtube' => ['label' => 'YouTube URL', 'type' => 'url', 'rules' => 'nullable|url|max:255'],
        ],
    ],
    'fares' => [
        'label' => 'Fare display',
        'fields' => [
            'fare_round_to' => ['label' => 'Round fare estimates up to the nearest (₹)', 'type' => 'number', 'default' => '10', 'rules' => 'required|integer|min:1|max:1000'],
            'fare_disclaimer' => ['label' => 'Fare disclaimer (shown with estimates)', 'type' => 'textarea', 'default' => 'Estimates are indicative and based on the configured route distance and fare rules. Tolls, state permits and parking are charged as applicable unless stated. Your final fare is confirmed by our team before the trip.', 'rules' => 'nullable|string|max:600'],
            'booking_notice' => ['label' => 'Message shown after a request is submitted', 'type' => 'textarea', 'default' => 'Our team will call you shortly to confirm vehicle availability and the final fare. No payment is taken online.', 'rules' => 'nullable|string|max:400'],
            'min_hours_before_pickup' => ['label' => 'Minimum notice before pickup (hours)', 'type' => 'number', 'default' => '2', 'rules' => 'required|integer|min:0|max:72'],
            'rental_packages' => ['label' => 'Rental packages offered in the booking widget (one per line)', 'type' => 'textarea', 'default' => "4 hrs / 40 km\n8 hrs / 80 km\n12 hrs / 120 km\nFull day (local)", 'rules' => 'required|string|max:1000'],
        ],
    ],
    'app' => [
        'label' => 'Mobile app',
        'fields' => [
            'app_play_store_url' => ['label' => 'Google Play Store URL (leave empty until the Android app is live)', 'type' => 'url', 'rules' => 'nullable|url|max:255|starts_with:https://play.google.com/'],
            'app_app_store_url' => ['label' => 'Apple App Store URL (leave empty until the iOS app is live)', 'type' => 'url', 'rules' => 'nullable|url|max:255|starts_with:https://apps.apple.com/'],
            'app_qr_media_id' => ['label' => 'QR code image (optional — when empty, a QR code is generated automatically from the store link)', 'type' => 'media'],
            'app_coming_soon_text' => ['label' => 'Message shown when no store links are configured', 'type' => 'text', 'default' => 'Our mobile app is coming soon. Until then, book online in under a minute or call us.', 'rules' => 'nullable|string|max:200'],
        ],
    ],
    'partners' => [
        'label' => 'Partners & corporate',
        'fields' => [
            'partner_programme_open' => ['label' => 'Accept new partner (driver / fleet owner) registrations', 'type' => 'boolean', 'default' => '1'],
            'corporate_email' => ['label' => 'Corporate desk email (shown on the Corporate page)', 'type' => 'email', 'rules' => 'nullable|email|max:150'],
        ],
    ],
    'seo' => [
        'label' => 'SEO defaults',
        'fields' => [
            'seo_title_suffix' => ['label' => 'Title suffix', 'type' => 'text', 'default' => ' | One Way Taxi', 'rules' => 'nullable|string|max:60'],
            'seo_default_title' => ['label' => 'Default meta title', 'type' => 'text', 'default' => 'One Way Taxi — Reliable One-Way Cabs Across India', 'rules' => 'nullable|string|max:120'],
            'seo_default_description' => ['label' => 'Default meta description', 'type' => 'textarea', 'default' => 'Book reliable one-way taxis with transparent fares. Outstation, airport and intercity cab services with verified drivers and 24/7 support.', 'rules' => 'nullable|string|max:300'],
            'default_og_media_id' => ['label' => 'Default social sharing image', 'type' => 'media'],
            'structured_data_enabled' => ['label' => 'Publish LocalBusiness structured data (enable only after the contact details above are real and verified)', 'type' => 'boolean', 'default' => '0'],
            'search_engine_indexing' => ['label' => 'Allow search engines to index the site', 'type' => 'boolean', 'default' => '1'],
        ],
    ],
    'footer' => [
        'label' => 'Footer',
        'fields' => [
            'footer_about' => ['label' => 'Footer about text', 'type' => 'textarea', 'default' => 'One Way Taxi offers comfortable one-way and outstation cab rides with clear, upfront fare estimates. Pay only for the distance you travel.', 'rules' => 'nullable|string|max:400'],
            'footer_copyright' => ['label' => 'Copyright line (use {year} for the current year)', 'type' => 'text', 'default' => '© {year} One Way Taxi. All rights reserved.', 'rules' => 'nullable|string|max:200'],
        ],
    ],
];
