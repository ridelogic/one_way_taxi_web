<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * DEMONSTRATION CONTENT — services, FAQs, CMS pages, homepage sections and navigation menus.
 * Statistics are seeded as unverified sample figures and are labelled as such on the site.
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        // Earlier demo data used "Corporate Travel"; rename in place so existing rows are kept.
        Service::where('slug', 'corporate-travel')->update(['slug' => 'corporate-taxi']);
        NavigationItem::where('url', '/services/corporate-travel')->update(['url' => '/services/corporate-taxi', 'label' => 'Corporate Taxi']);

        $this->seedServices();
        $this->seedFaqs();
        $this->seedTestimonials();
        $this->seedPages();
        $this->seedHomeSections();
        $this->seedNavigation();
    }

    private function seedServices(): void
    {
        $services = [
            ['one-way-taxi', 'One Way Taxi', 'route', 'Pay only for a single journey. No return fare, no hidden charges.', 'hero-munnar-highway.jpg',
                ['Pay for one-way distance only', 'Doorstep pickup and drop', 'Sedan, SUV and Innova options', 'Fare estimate before you book'],
                '<p>A one-way taxi is the most economical way to travel between cities when you don’t need a return trip. Traditional outstation cabs charge you for the driver’s journey back; with a one-way drop you pay only for the distance you actually travel.</p><h2>How one-way pricing works</h2><p>Your fare is calculated from the route distance and the per-kilometre rate of your chosen vehicle, plus the driver allowance. Tolls, inter-state permits and parking are added as applicable and are always explained before you confirm.</p><h2>Who it’s for</h2><ul><li>Relocating or travelling to another city</li><li>Reaching an airport or railway station in a different city</li><li>Families travelling with luggage</li><li>Business travellers who need a reliable intercity ride</li></ul>'],
            ['outstation-taxi', 'Outstation Taxi', 'map', 'Round trips and multi-day tours with experienced highway drivers.', 'scenic-yercaud-ghat-road.jpg',
                ['Round trips and multi-city tours', 'Drivers experienced on ghat roads', 'Flexible stops along the way', 'Clean, air-conditioned vehicles'],
                '<p>Planning a weekend in the hills or a temple tour across Tamil Nadu? Our outstation taxis come with drivers who know the highways and ghat roads, so you can relax and enjoy the journey.</p><h2>Popular outstation trips</h2><ul><li>Ooty, Kodaikanal and Yercaud hill-station getaways</li><li>Madurai, Rameswaram and Thanjavur temple circuits</li><li>Mysuru and Coorg from Bengaluru</li></ul><p>Tell us your itinerary and we will share a clear quote.</p>'],
            ['airport-transfers', 'Airport Transfers', 'plane', 'On-time pickups and drops for every flight, day or night.', 'airport-chennai.jpg',
                ['We plan around your flight time', 'Pickup from arrivals', 'Help with luggage', 'Available 24/7'],
                '<p>Start and end your trip without stress. We schedule airport drops with enough buffer for check-in and traffic, and our drivers meet you at arrivals for pickups.</p><h2>Airports we commonly serve</h2><ul><li>Chennai International Airport (MAA)</li><li>Kempegowda International Airport, Bengaluru (BLR)</li><li>Coimbatore International Airport (CJB)</li><li>Madurai Airport (IXM)</li></ul><p>Share your flight number when you book so we can plan the pickup.</p>'],
            ['local-taxi', 'Local Taxi', 'navigation', 'Hourly packages for errands, meetings and sightseeing in the city.', 'scenic-city-highway.jpg',
                ['Hourly rental packages', 'Multiple stops', 'Sightseeing within the city', 'Professional local drivers'],
                '<p>Need a car for a few hours? Our local taxi packages are ideal for shopping, hospital visits, meetings or sightseeing, with a driver who knows the city.</p><p>Contact us for package rates in your city.</p>'],
            ['corporate-taxi', 'Corporate Taxi', 'building', 'Dependable taxis for employees, guests and events.', 'fleet-premium-sedan.jpg',
                ['Monthly billing on request', 'Guest and delegate pickups', 'Premium sedans available', 'Dedicated support contact'],
                '<p>We help businesses move employees, clients and event guests reliably. Arrange one-off intercity trips or a regular schedule, with consolidated billing on request.</p><p>Write to us with your requirements and our team will get in touch.</p>'],
        ];

        foreach ($services as $i => [$slug, $title, $icon, $short, $image, $highlights, $body]) {
            Service::firstOrCreate(['slug' => $slug], [
                'title' => $title,
                'icon' => $icon,
                'short_description' => $short,
                'highlights' => $highlights,
                'body' => $body,
                'media_id' => MediaSeeder::id($image),
                'show_on_home' => true,
                'is_active' => true,
                'sort_order' => $i + 1,
                'seo_title' => $title.' Service',
                'seo_description' => $short,
            ]);
        }
    }

    private function seedFaqs(): void
    {
        $faqs = [
            ['Booking', 'What is a one-way taxi?', 'A one-way taxi takes you from one city to another and you pay only for that single journey. Unlike a traditional outstation cab, you are not charged for the driver’s return trip.', true],
            ['Booking', 'How do I book a cab?', 'Choose your pickup city, destination, travel date, time and vehicle type, then request a quote. Enter your name and phone number to submit the request. You will receive a booking reference immediately and our team will call you to confirm.', true],
            ['Booking', 'Is my booking confirmed as soon as I submit the form?', 'Submitting the form creates a booking request with a reference number. Your trip is confirmed once our team has called you to verify the details, vehicle availability and final fare.', true],
            ['Fares', 'How is the fare calculated?', 'Fares are based on the route distance and the per-kilometre rate of the vehicle you choose, plus a driver allowance. A minimum chargeable distance may apply for short trips. You see an estimate before you submit your request.', true],
            ['Fares', 'Are tolls and permits included?', 'Toll charges, inter-state permits and parking fees are not included in the estimate unless stated. They are charged as applicable on your route, and our team will explain them when confirming your booking.', true],
            ['Fares', 'Do I need to pay online?', 'No. There is no online payment on this website. Payment terms are shared by our team when your booking is confirmed.', false],
            ['Travel', 'Can I make stops on the way?', 'Short refreshment stops are fine. If you need additional sightseeing stops or detours, mention them in the notes so we can include them in your quote.', false],
            ['Travel', 'Which vehicles are available?', 'We offer economy sedans, premium sedans, SUVs and the Toyota Innova Crysta. The exact model is subject to availability for your travel date.', true],
            ['Support', 'How can I change or cancel my booking?', 'Call or WhatsApp us with your booking reference and we will help you reschedule or cancel.', false],
        ];

        foreach ($faqs as $i => [$category, $question, $answer, $home]) {
            Faq::firstOrCreate(['question' => $question], [
                'answer' => $answer,
                'category' => $category,
                'show_on_home' => $home,
                'is_active' => true,
                'sort_order' => $i + 1,
            ]);
        }
    }

    /** SAMPLE testimonials — flagged is_sample and labelled on the site until replaced with genuine reviews. */
    private function seedTestimonials(): void
    {
        $rows = [
            ['Sample Customer A', 'Chennai', 'Chennai to Coimbatore', 5, 'Clean car, punctual driver and the fare matched the estimate. Exactly what you want from a one-way drop.'],
            ['Sample Customer B', 'Bengaluru', 'Bengaluru to Mysuru', 5, 'Booked late at night and got a call back within minutes. The ride was smooth and comfortable.'],
            ['Sample Customer C', 'Coimbatore', 'Coimbatore to Ooty', 4, 'Our driver handled the ghat road hairpins calmly. Great option for families with luggage.'],
        ];

        foreach ($rows as $i => [$name, $location, $trip, $rating, $quote]) {
            Testimonial::firstOrCreate(['customer_name' => $name], [
                'location' => $location,
                'trip' => $trip,
                'rating' => $rating,
                'quote' => $quote,
                'is_sample' => true,
                'is_active' => true,
                'sort_order' => $i + 1,
            ]);
        }
    }

    private function seedPages(): void
    {
        Page::firstOrCreate(['slug' => 'home'], [
            'title' => 'Home',
            'template' => 'home',
            'status' => 'published',
            'is_system' => true,
            'seo_title' => 'One Way Taxi — Reliable One-Way Cabs Across India',
            'seo_description' => 'Book reliable one-way taxis with transparent fares. Outstation, airport and intercity cab services with verified drivers and 24/7 support.',
            'published_at' => now(),
        ]);

        Page::firstOrCreate(['slug' => 'about'], [
            'title' => 'About Us',
            'template' => 'about',
            'heading' => 'About One Way Taxi',
            'subheading' => 'Comfortable, honest and dependable intercity travel.',
            'hero_media_id' => MediaSeeder::id('scenic-western-ghats.jpg'),
            'body' => '<p>One Way Taxi was started with a simple idea: travellers should only pay for the journey they take. Traditional outstation cabs charge for the driver’s return trip, which makes single-direction travel expensive. Our one-way model removes that cost.</p><h2>What we stand for</h2><ul><li><strong>Transparent pricing</strong> — you see a fare estimate before you book, and extra charges are explained upfront.</li><li><strong>Professional drivers</strong> — experienced on highways and ghat roads, courteous and punctual.</li><li><strong>Comfortable vehicles</strong> — clean, air-conditioned sedans, SUVs and Innova Crysta.</li><li><strong>Real support</strong> — talk to a person whenever you need help.</li></ul><p><em>This is demonstration content. Replace it with your company’s story in Admin → Pages.</em></p>',
            'status' => 'published',
            'show_in_nav' => false,
            'is_system' => true,
            'seo_title' => 'About Us',
            'seo_description' => 'Learn about One Way Taxi — transparent one-way cab fares, professional drivers and comfortable vehicles across South India.',
            'published_at' => now(),
        ]);

        $legalNote = '<p><em>This is a template provided for demonstration. Please have it reviewed by a legal professional and update it to reflect your business before publishing.</em></p>';

        Page::firstOrCreate(['slug' => 'privacy-policy'], [
            'title' => 'Privacy Policy',
            'template' => 'legal',
            'heading' => 'Privacy Policy',
            'body' => $legalNote.'<h2>Information we collect</h2><p>When you request a booking or contact us, we collect the details you provide: your name, phone number, email address (optional), pickup and destination, travel date and time, and any notes. We also record your IP address and browser type to protect against misuse.</p><h2>How we use your information</h2><ul><li>To respond to your booking request and confirm your trip</li><li>To contact you about your booking</li><li>To improve our services and keep our website secure</li></ul><p>We do not sell your personal information.</p><h2>Sharing</h2><p>We share trip details with the assigned driver only as needed to complete your journey, and with authorities where required by law.</p><h2>Retention</h2><p>We keep booking records for as long as needed for operational, accounting and legal purposes.</p><h2>Your choices</h2><p>You can ask us to access, correct or delete your personal information by contacting us using the details on our Contact page.</p><h2>Cookies</h2><p>This website uses essential cookies to keep your session secure. We do not use advertising cookies.</p>',
            'status' => 'published',
            'is_system' => true,
            'seo_title' => 'Privacy Policy',
            'published_at' => now(),
        ]);

        Page::firstOrCreate(['slug' => 'terms-and-conditions'], [
            'title' => 'Terms and Conditions',
            'template' => 'legal',
            'heading' => 'Terms and Conditions',
            'body' => $legalNote.'<h2>Booking requests</h2><p>Submitting a request on this website does not guarantee a booking. A booking is confirmed only after our team contacts you and confirms the vehicle, timing and fare.</p><h2>Fares</h2><p>Fare estimates shown on the website are indicative and are based on standard route distances. Tolls, inter-state permits, parking and any additional distance or waiting time are charged as applicable. The final fare is communicated before the trip.</p><h2>Cancellations</h2><p>Please inform us as early as possible if you need to cancel or change your trip. Cancellation terms are shared at the time of confirmation.</p><h2>Passenger responsibilities</h2><ul><li>Provide accurate pickup details and be ready at the agreed time</li><li>Do not carry illegal or hazardous items</li><li>Follow the driver’s guidance on safety, seat belts and passenger limits</li></ul><h2>Liability</h2><p>We take every care to ensure a safe and timely journey but are not responsible for delays caused by traffic, weather, road closures or other circumstances beyond our control.</p>',
            'status' => 'published',
            'is_system' => true,
            'seo_title' => 'Terms and Conditions',
            'published_at' => now(),
        ]);
    }

    private function seedHomeSections(): void
    {
        $home = Page::where('slug', 'home')->firstOrFail();

        $sections = [
            'hero' => [
                'title' => 'YOUR JOURNEY. YOUR WAY. ONE WAY TAXI.',
                'subtitle' => 'Reliable one-way rides. Transparent fares. Comfortable journeys across India.',
                'media' => 'hero-sunset-mountains.jpg',
                'content' => [
                    'eyebrow' => 'PREMIUM ONE-WAY TAXI SERVICE',
                    'heading_line_1' => 'YOUR JOURNEY.',
                    'heading_line_2' => 'YOUR WAY.',
                    'heading_highlight' => 'ONE WAY TAXI',
                    'primary_cta_label' => '',
                    'primary_cta_url' => '/book',
                    'secondary_cta_label' => '',
                    'secondary_cta_url' => '/routes',
                    'overlay_strength' => 30,
                    'mirror_background' => false,
                    'vehicle_media_id' => MediaSeeder::id('hero-suv-black.webp'),
                    'vehicle_size' => 29,
                    'items' => [
                        ['icon' => 'shield-check', 'title' => 'Verified Drivers', 'text' => 'Background-checked'],
                        ['icon' => 'rupee', 'title' => 'Fixed Fares', 'text' => 'No Hidden Charges'],
                        ['icon' => 'headset', 'title' => '24/7 Support', 'text' => 'Always with You'],
                    ],
                ],
            ],
            'stats' => [
                'title' => 'Our numbers',
                'content' => [
                    'note' => 'Sample figures for demonstration — update in Admin → Homepage.',
                    'is_verified' => false,
                    'items' => [
                        ['icon' => 'users', 'value' => '50,000+', 'label' => 'Happy Customers'],
                        ['icon' => 'star', 'value' => '4.8/5', 'label' => 'Customer Rating'],
                        ['icon' => 'award', 'value' => '5+', 'label' => 'Years of Service'],
                    ],
                ],
            ],
            'booking' => [
                'title' => 'BOOK YOUR RIDE',
                'subtitle' => 'Get instant fare estimate for your one-way trip',
                'content' => [
                    'comfort_message' => 'SAFE • COMFORTABLE • AFFORDABLE',
                    'cta_label' => 'GET A QUOTE',
                    'items' => [
                        ['icon' => 'rupee', 'title' => 'No Hidden Charges', 'text' => 'Transparent Pricing'],
                        ['icon' => 'shield-check', 'title' => 'Best Price Guarantee', 'text' => 'Fair One-Way Rates'],
                        ['icon' => 'zap', 'title' => 'Quick Confirmation', 'text' => 'Quick & Easy'],
                    ],
                ],
            ],
            'routes' => [
                'title' => 'POPULAR ROUTES & FARES',
                'subtitle' => 'Most booked one-way taxi routes across India',
                'content' => ['button_label' => 'View All Routes', 'button_url' => '/routes', 'limit' => 3],
            ],
            'services' => [
                'title' => 'Our Services',
                'subtitle' => 'Whatever the journey, we have a ride for it.',
                'content' => [],
            ],
            'fleet' => [
                'title' => 'Choose Your Ride',
                'subtitle' => 'Well-maintained, air-conditioned vehicles for every group size.',
                'content' => ['button_label' => 'View Full Fleet'],
            ],
            'why_choose' => [
                'title' => 'Why Travel With Us',
                'subtitle' => 'Everything you need for a stress-free intercity journey.',
                'media' => 'scenic-hairpin-aerial.jpg',
                'content' => ['items' => [
                    ['icon' => 'rupee', 'title' => 'Transparent pricing', 'text' => 'See an estimate before you book. Tolls and permits are explained upfront.'],
                    ['icon' => 'shield-check', 'title' => 'Professional drivers', 'text' => 'Experienced on highways and ghat roads, punctual and courteous.'],
                    ['icon' => 'armchair', 'title' => 'Comfortable vehicles', 'text' => 'Clean, air-conditioned sedans, SUVs and Innova Crysta.'],
                    ['icon' => 'smartphone', 'title' => 'Easy booking', 'text' => 'Request a ride in under a minute — no app or account needed.'],
                    ['icon' => 'headset', 'title' => 'Customer support', 'text' => 'Talk to a real person before, during and after your trip.'],
                ]],
            ],
            'how_it_works' => [
                'title' => 'How It Works',
                'subtitle' => 'Book your one-way cab in four simple steps.',
                'content' => ['items' => [
                    ['icon' => 'map-pin', 'title' => 'Choose your route', 'text' => 'Select your pickup city and destination.'],
                    ['icon' => 'calendar', 'title' => 'Pick date & vehicle', 'text' => 'Choose when you travel and the car that suits you.'],
                    ['icon' => 'rupee', 'title' => 'Request your fare', 'text' => 'See an instant estimate and send your request.'],
                    ['icon' => 'check-circle', 'title' => 'Get confirmation', 'text' => 'Our team calls to confirm your booking.'],
                ]],
            ],
            'destinations' => [
                'title' => 'Popular Destinations',
                'subtitle' => 'Hill stations, heritage cities and coastal escapes.',
                'content' => ['limit' => 8],
            ],
            'about' => [
                'title' => 'About One Way Taxi',
                'subtitle' => 'Pay only for the journey you take.',
                'media' => 'scenic-western-ghats.jpg',
                'content' => [
                    'body' => 'We make intercity travel simple and affordable. With one-way pricing you are never charged for the driver’s return journey, and every fare estimate is shown before you book. Our drivers know the highways and hill roads of South India, and our support team is a phone call away.',
                    'cta_label' => 'Learn More About Us',
                    'cta_url' => '/about',
                    'items' => [
                        ['text' => 'One-way fares with no return charges'],
                        ['text' => 'Doorstep pickup from home, hotel or airport'],
                        ['text' => 'Sedans, SUVs and Innova Crysta'],
                        ['text' => 'Booking confirmed personally by our team'],
                    ],
                ],
            ],
            'testimonials' => [
                'title' => 'What Travellers Say',
                'subtitle' => 'Feedback from passengers on our one-way rides.',
                'content' => ['limit' => 3],
            ],
            'faqs' => [
                'title' => 'Frequently Asked Questions',
                'subtitle' => 'Quick answers about booking, fares and travel.',
                'content' => ['limit' => 6],
            ],
            'cta' => [
                'title' => 'Ready to travel? Book your one-way taxi today.',
                'subtitle' => 'Get a fare estimate in seconds or speak to our team.',
                'media' => 'scenic-yercaud-ghat-road.jpg',
                'content' => [
                    'primary_label' => 'Book Your Ride',
                    'primary_url' => '/book',
                    'secondary_label' => 'Call Us Now',
                    'secondary_url' => '',
                ],
            ],
        ];

        $order = 1;
        foreach ($sections as $key => $section) {
            PageSection::firstOrCreate(['page_id' => $home->id, 'key' => $key], [
                'type' => $key,
                'title' => $section['title'],
                'subtitle' => $section['subtitle'] ?? null,
                'content' => $section['content'],
                'media_id' => isset($section['media']) ? MediaSeeder::id($section['media']) : null,
                'sort_order' => $order++,
                'is_active' => true,
            ]);
        }
    }

    private function seedNavigation(): void
    {
        $menus = [
            'header' => [['Home', '/'], ['Services', '/services'], ['Routes', '/routes'], ['Fleet', '/fleet'], ['About', '/about'], ['Contact', '/contact']],
            'footer_company' => [['About Us', '/about'], ['Routes & Fares', '/routes'], ['Our Fleet', '/fleet'], ['FAQs', '/faqs'], ['Contact Us', '/contact']],
            'footer_services' => [['One Way Taxi', '/services/one-way-taxi'], ['Outstation Taxi', '/services/outstation-taxi'], ['Airport Transfers', '/services/airport-transfers'], ['Local Taxi', '/services/local-taxi'], ['Corporate Taxi', '/services/corporate-taxi']],
            'footer_legal' => [['Privacy Policy', '/privacy-policy'], ['Terms & Conditions', '/terms-and-conditions'], ['Image Credits', '/image-credits']],
        ];

        foreach ($menus as $menu => $items) {
            foreach ($items as $i => [$label, $url]) {
                NavigationItem::firstOrCreate(['menu' => $menu, 'url' => $url], [
                    'label' => $label,
                    'sort_order' => $i + 1,
                    'is_active' => true,
                ]);
            }
        }
    }
}
