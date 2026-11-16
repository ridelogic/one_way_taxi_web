<?php

/*
|--------------------------------------------------------------------------
| Page section schemas (Homepage CMS)
|--------------------------------------------------------------------------
| Each section type declares its editable content fields and optional
| repeatable "items". Title/subtitle/media/active/order are columns on
| page_sections; everything else is stored in its JSON `content` column.
| Field types: text, textarea, url, number, icon, boolean.
*/

$iconItem = [
    'icon' => ['label' => 'Icon', 'type' => 'icon'],
    'title' => ['label' => 'Title', 'type' => 'text'],
    'text' => ['label' => 'Text', 'type' => 'text'],
];

return [
    'hero' => [
        'label' => 'Hero slider settings & trust strip',
        'note' => 'Slides (images, headlines, buttons, order, visibility) are managed in Admin → Hero slider.',
        'fields' => [
            'autoplay_seconds' => ['label' => 'Seconds between slides (0 = no autoplay)', 'type' => 'number', 'min' => 0, 'max' => 30],
        ],
        'items' => ['label' => 'Benefit indicators under the hero text (e.g. Safe & Reliable Travel, 24/7 Service, Across India)', 'max' => 4, 'fields' => $iconItem],
    ],
    'benefits' => [
        'label' => 'Benefits strip (below the booking form)',
        'fields' => [],
        'items' => ['label' => 'Benefits (e.g. Transparent Pricing, Wide Fleet Options)', 'max' => 4, 'fields' => $iconItem],
    ],
    'stats' => [
        'label' => 'Business statistics',
        'fields' => [
            'note' => ['label' => 'Footnote (e.g. “Figures as of 2026”)', 'type' => 'text'],
            'is_verified' => ['label' => 'These figures are verified (hides the “sample” label)', 'type' => 'boolean'],
        ],
        'items' => ['label' => 'Statistics', 'max' => 4, 'fields' => [
            'icon' => ['label' => 'Icon', 'type' => 'icon'],
            'value' => ['label' => 'Value (e.g. 10K+)', 'type' => 'text'],
            'label' => ['label' => 'Label', 'type' => 'text'],
        ]],
    ],
    'booking' => [
        'label' => 'Booking card',
        'note' => 'The booking form across the bottom of the hero: Oneway / Outstation / Rental tabs, then From, To, Date, Mobile No and Email. The title is read by screen readers. Hero text, scene photo and vehicle are edited under Hero slides; the benefits under the Hero section.',
        'fields' => [
            'submit_label' => ['label' => 'Submit button label', 'type' => 'text'],
            'note' => ['label' => 'Note below the button', 'type' => 'text'],
        ],
    ],
    'vehicle_carousel' => [
        'label' => 'Vehicle & service carousel',
        'fields' => [
            'button_label' => ['label' => 'Button label', 'type' => 'text'],
            'button_url' => ['label' => 'Button URL', 'type' => 'url'],
        ],
        'items' => ['label' => 'Carousel cards', 'max' => 16, 'fields' => [
            'title' => ['label' => 'Title', 'type' => 'text'],
            'text' => ['label' => 'Short description', 'type' => 'text'],
            'badge' => ['label' => 'Badge (e.g. “4 seats”)', 'type' => 'text'],
            'link' => ['label' => 'Link URL', 'type' => 'url'],
            'image' => ['label' => 'Image', 'type' => 'media'],
        ]],
    ],
    'safety' => [
        'label' => 'Safety',
        'media' => 'Image',
        'fields' => [],
        'items' => ['label' => 'Safety benefits', 'max' => 8, 'fields' => $iconItem],
    ],
    'service_areas' => [
        'label' => 'Service areas',
        'fields' => [
            'body' => ['label' => 'Intro text', 'type' => 'textarea'],
        ],
        'note' => 'The city list comes from active cities in Admin → Cities, grouped by state.',
    ],
    'corporate' => [
        'label' => 'Corporate taxi',
        'media' => 'Image',
        'fields' => [
            'body' => ['label' => 'Body text', 'type' => 'textarea'],
            'cta_label' => ['label' => 'Button label', 'type' => 'text'],
            'cta_url' => ['label' => 'Button URL', 'type' => 'url'],
        ],
        'items' => ['label' => 'Benefits', 'max' => 8, 'fields' => $iconItem],
    ],
    'partner' => [
        'label' => 'Become a partner',
        'media' => 'Image',
        'fields' => [
            'body' => ['label' => 'Body text', 'type' => 'textarea'],
            'cta_label' => ['label' => 'Button label', 'type' => 'text'],
            'cta_url' => ['label' => 'Button URL', 'type' => 'url'],
        ],
        'items' => ['label' => 'Partner benefits', 'max' => 8, 'fields' => $iconItem],
    ],
    'app' => [
        'label' => 'Mobile app promotion',
        'fields' => [
            'body' => ['label' => 'Body text', 'type' => 'textarea'],
        ],
        'items' => ['label' => 'App features', 'max' => 6, 'fields' => [
            'text' => ['label' => 'Feature', 'type' => 'text'],
        ]],
        'note' => 'Store links and the QR code are set in Admin → Settings → Mobile app. Without links the section says the app is coming soon — no fake download buttons are shown.',
    ],
    'contact' => [
        'label' => 'Contact',
        'fields' => [
            'body' => ['label' => 'Intro text', 'type' => 'textarea'],
            'show_form' => ['label' => 'Show the contact form', 'type' => 'boolean'],
        ],
        'note' => 'Phone, email, address and hours come from Admin → Settings → Contact details.',
    ],
    'routes' => [
        'label' => 'Popular routes & fares',
        'fields' => [
            'eyebrow' => ['label' => 'Small gold label above the title', 'type' => 'text'],
            'button_label' => ['label' => 'Button label', 'type' => 'text'],
            'button_url' => ['label' => 'Button URL', 'type' => 'url'],
            'limit' => ['label' => 'Number of routes to show', 'type' => 'number', 'min' => 1, 'max' => 12],
            'map_media_id' => ['label' => 'Left illustration (India map)', 'type' => 'media'],
            'map_title' => ['label' => 'Caption beside the map (green)', 'type' => 'text'],
            'map_highlight' => ['label' => 'Caption highlight (gold)', 'type' => 'text'],
            'scenic_media_id' => ['label' => 'Right decorative image', 'type' => 'media'],
        ],
    ],
    'services' => [
        'label' => 'Services',
        'fields' => [],
        'note' => 'Service cards are managed in Admin → Services (tick “Show on homepage”).',
    ],
    'fleet' => [
        'label' => 'Fleet',
        'fields' => [
            'eyebrow' => ['label' => 'Small gold label above the title', 'type' => 'text'],
            'title_highlight' => ['label' => 'Gold words at the end of the title', 'type' => 'text'],
            'button_label' => ['label' => 'Button label', 'type' => 'text'],
            'limit' => ['label' => 'Number of vehicles to show', 'type' => 'number', 'min' => 1, 'max' => 12],
        ],
        'note' => 'Vehicles are managed in Admin → Fleet.',
    ],
    'why_choose' => [
        'label' => 'Why choose us',
        'media' => 'Side image',
        'fields' => [],
        'items' => ['label' => 'Reasons', 'max' => 8, 'fields' => $iconItem],
    ],
    'how_it_works' => [
        'label' => 'How it works',
        'fields' => [],
        'items' => ['label' => 'Steps', 'max' => 6, 'fields' => [
            'icon' => ['label' => 'Icon', 'type' => 'icon'],
            'title' => ['label' => 'Title', 'type' => 'text'],
            'text' => ['label' => 'Text', 'type' => 'text'],
        ]],
    ],
    'destinations' => [
        'label' => 'Popular destinations',
        'fields' => [
            'limit' => ['label' => 'Number of destinations', 'type' => 'number', 'min' => 1, 'max' => 12],
        ],
        'note' => 'Destinations are cities marked “Popular destination” in Admin → Cities.',
    ],
    'about' => [
        'label' => 'About',
        'media' => 'Image',
        'fields' => [
            'body' => ['label' => 'Body text', 'type' => 'textarea'],
            'cta_label' => ['label' => 'Button label', 'type' => 'text'],
            'cta_url' => ['label' => 'Button URL', 'type' => 'url'],
        ],
        'items' => ['label' => 'Bullet points', 'max' => 6, 'fields' => [
            'text' => ['label' => 'Text', 'type' => 'text'],
        ]],
    ],
    'testimonials' => [
        'label' => 'Testimonials',
        'fields' => [
            'limit' => ['label' => 'Number of testimonials', 'type' => 'number', 'min' => 1, 'max' => 9],
        ],
        'note' => 'Testimonials are managed in Admin → Testimonials. Entries marked “sample” are labelled on the site.',
    ],
    'faqs' => [
        'label' => 'FAQs',
        'fields' => [
            'limit' => ['label' => 'Number of questions', 'type' => 'number', 'min' => 1, 'max' => 12],
        ],
        'note' => 'Questions are managed in Admin → FAQs (tick “Show on homepage”).',
    ],
    'cta' => [
        'label' => 'Call-to-action band',
        'media' => 'Background image',
        'fields' => [
            'primary_label' => ['label' => 'Primary button label', 'type' => 'text'],
            'primary_url' => ['label' => 'Primary button URL (leave empty to call the business phone)', 'type' => 'url'],
            'secondary_label' => ['label' => 'Secondary button label', 'type' => 'text'],
            'secondary_url' => ['label' => 'Secondary button URL', 'type' => 'url'],
        ],
    ],
];
