<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BusinessEnquiryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public website
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');

Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{service:slug}', [ServiceController::class, 'show'])->name('services.show');

Route::get('/tamil-nadu', [DistrictController::class, 'index'])->name('districts.index');
Route::get('/tamil-nadu/{district:slug}', [DistrictController::class, 'show'])->name('districts.show');
// Union Territory pages (e.g. Puducherry) use the same page system but are not part of Tamil Nadu: /{slug}.
Route::get('/{district:slug}', [DistrictController::class, 'show'])
    ->whereIn('district', \App\Models\DistrictPage::TERRITORY_SLUGS)->name('territories.show');
Route::get('/routes', [RouteController::class, 'index'])->name('routes.index');
Route::get('/routes/{taxiRoute:slug}', [RouteController::class, 'show'])->name('routes.show');

Route::get('/fleet', [FleetController::class, 'index'])->name('fleet.index');
Route::get('/fleet/{vehicle}', [FleetController::class, 'show'])->name('fleet.show'); // retired page: redirects to booking

Route::get('/faqs', FaqController::class)->name('faqs');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');

Route::get('/book', [BookingController::class, 'create'])->name('booking.create');
Route::post('/book', [BookingController::class, 'store'])->middleware('throttle:bookings')->name('booking.store');
Route::post('/fare-estimate', [BookingController::class, 'estimate'])->middleware('throttle:fare-estimate')->name('booking.estimate');
Route::get('/booking/{reference}/confirmation', [BookingController::class, 'confirmation'])
    ->where('reference', 'OWT-[0-9]{6}-[A-Z0-9]{5}')
    ->name('booking.confirmation');

Route::get('/corporate', [BusinessEnquiryController::class, 'corporate'])->name('corporate');
Route::post('/corporate', [BusinessEnquiryController::class, 'storeCorporate'])->middleware('throttle:contact')->name('corporate.store');
Route::get('/become-a-partner', [BusinessEnquiryController::class, 'partner'])->name('partner');
Route::post('/become-a-partner', [BusinessEnquiryController::class, 'storePartner'])->middleware('throttle:contact')->name('partner.store');
Route::get('/download-app', [BusinessEnquiryController::class, 'app'])->name('app');
Route::redirect('/cabs', '/book', 301);

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/image-credits', [PageController::class, 'credits'])->name('credits');

/*
|--------------------------------------------------------------------------
| Admin CMS
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware('admin.headers')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [Admin\AuthController::class, 'create'])->name('login');
        Route::post('login', [Admin\AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');

        // First-time setup: the website owner creates their own admin account (closed once an owner exists).
        Route::get('setup', [Admin\SetupController::class, 'create'])->name('setup');
        Route::post('setup', [Admin\SetupController::class, 'store'])->middleware('throttle:admin-setup')->name('setup.store');

        // Forgot password: single-use, expiring reset link emailed to the admin's registered address.
        Route::get('forgot-password', [Admin\PasswordResetController::class, 'request'])->name('password.request');
        Route::post('forgot-password', [Admin\PasswordResetController::class, 'email'])->middleware('throttle:admin-password')->name('password.email');
        Route::get('reset-password/{token}', [Admin\PasswordResetController::class, 'edit'])->name('password.reset');
        Route::post('reset-password', [Admin\PasswordResetController::class, 'update'])->middleware('throttle:admin-password')->name('password.update');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('logout', [Admin\AuthController::class, 'destroy'])->name('logout');
        Route::get('account', [Admin\AccountController::class, 'edit'])->name('account.edit');
        Route::put('account', [Admin\AccountController::class, 'update'])->name('account.update');

        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        // Homepage Content: videos, photos and homepage sections (the hero banner is fixed and not edited here).
        Route::patch('homepage/{section}/move', [Admin\HomeSectionController::class, 'move'])->name('homepage.move');
        Route::get('homepage', [Admin\HomeSectionController::class, 'index'])->name('homepage.index');
        Route::get('homepage/{section}/edit', [Admin\HomeSectionController::class, 'edit'])->name('homepage.edit');
        Route::put('homepage/{section}', [Admin\HomeSectionController::class, 'update'])->name('homepage.update');
        Route::patch('homepage/{section}/toggle', [Admin\HomeSectionController::class, 'toggle'])->name('homepage.toggle');

        Route::get('pages/{page}/preview', [Admin\PageController::class, 'preview'])->name('pages.preview');
        Route::resource('pages', Admin\PageController::class)->except('show');

        Route::get('media/picker', [Admin\MediaController::class, 'picker'])->name('media.picker');
        Route::post('media/{medium}/replace', [Admin\MediaController::class, 'replace'])->name('media.replace');
        Route::resource('media', Admin\MediaController::class)->except(['create', 'show']);

        Route::resource('cities', Admin\CityController::class)->except('show');
        // District Management: one screen per district with tabs (details, videos, photos, SEO, cars, services, routes, FAQs).
        $tabs = 'videos|photos|seo|cars|services|routes|faqs';
        Route::get('districts/{district}/tab/{tab}', [Admin\DistrictCmsController::class, 'show'])->where('tab', $tabs)->name('districts.tab');
        Route::put('districts/{district}/tab/{tab}', [Admin\DistrictCmsController::class, 'update'])->where('tab', 'seo|services|routes')->name('districts.tab.update');
        Route::post('districts/{district}/cars', [Admin\DistrictVehicleController::class, 'store'])->name('districts.cars.store');
        Route::get('district-cars/{car}/edit', [Admin\DistrictVehicleController::class, 'edit'])->name('district-cars.edit');
        Route::put('district-cars/{car}', [Admin\DistrictVehicleController::class, 'update'])->name('district-cars.update');
        Route::patch('district-cars/{car}/toggle', [Admin\DistrictVehicleController::class, 'toggle'])->name('district-cars.toggle');
        Route::delete('district-cars/{car}', [Admin\DistrictVehicleController::class, 'destroy'])->name('district-cars.destroy');
        Route::post('districts/{district}/routes', [Admin\DistrictRouteController::class, 'store'])->name('districts.routes.store');
        Route::put('district-routes/{districtRoute}', [Admin\DistrictRouteController::class, 'update'])->name('district-routes.update');
        Route::delete('district-routes/{districtRoute}', [Admin\DistrictRouteController::class, 'destroy'])->name('district-routes.destroy');
        Route::post('districts/{district}/faqs', [Admin\DistrictFaqController::class, 'store'])->name('districts.faqs.store');
        Route::put('district-faqs/{faq}', [Admin\DistrictFaqController::class, 'update'])->name('district-faqs.update');
        Route::delete('district-faqs/{faq}', [Admin\DistrictFaqController::class, 'destroy'])->name('district-faqs.destroy');
        Route::delete('districts/{district}/videos/{video}', [Admin\PromoVideoController::class, 'detach'])->name('districts.videos.detach');
        Route::delete('districts/{district}/photos/{photo}', [Admin\GalleryPhotoController::class, 'detach'])->name('districts.photos.detach');
        Route::resource('districts', Admin\DistrictPageController::class)->except('show');
        Route::patch('districts/{district}/toggle', [Admin\DistrictPageController::class, 'toggle'])->name('districts.toggle');

        // Shared media: one record per video / photo, shown on the homepage and/or any selected districts.
        Route::get('videos/check', [Admin\PromoVideoController::class, 'check'])->name('videos.check');
        Route::resource('videos', Admin\PromoVideoController::class)->except('show');
        Route::patch('videos/{video}/toggle', [Admin\PromoVideoController::class, 'toggle'])->name('videos.toggle');
        Route::resource('photos', Admin\GalleryPhotoController::class)->except(['show', 'create']);
        Route::patch('photos/{photo}/toggle', [Admin\GalleryPhotoController::class, 'toggle'])->name('photos.toggle');
        Route::resource('routes', Admin\TaxiRouteController::class)->except('show')->parameters(['routes' => 'taxiRoute']);
        Route::resource('vehicles', Admin\VehicleController::class)->except('show');
        Route::get('fares', [Admin\FareRuleController::class, 'index'])->name('fares.index');
        Route::get('fares/{vehicle}/edit', [Admin\FareRuleController::class, 'edit'])->name('fares.edit');
        Route::put('fares/{vehicle}', [Admin\FareRuleController::class, 'update'])->name('fares.update');

        Route::resource('bookings', Admin\BookingController::class)->only(['index', 'show', 'update', 'destroy']);
        Route::resource('corporate-enquiries', Admin\CorporateEnquiryController::class)->only(['index', 'show', 'update', 'destroy'])->parameters(['corporate-enquiries' => 'enquiry']);
        Route::resource('partner-enquiries', Admin\PartnerEnquiryController::class)->only(['index', 'show', 'update', 'destroy'])->parameters(['partner-enquiries' => 'enquiry']);
        Route::resource('messages', Admin\ContactMessageController::class)->only(['index', 'show', 'destroy']);

        Route::resource('services', Admin\ServiceController::class)->except('show');
        Route::resource('faqs', Admin\FaqController::class)->except('show');
        // Promotional Announcements: the homepage offers ticker (disabled by default, admin enables each one).
        Route::get('announcements/{announcement}/preview', [Admin\PromoAnnouncementController::class, 'preview'])->name('announcements.preview');
        Route::patch('announcements/{announcement}/toggle', [Admin\PromoAnnouncementController::class, 'toggle'])->name('announcements.toggle');
        Route::resource('announcements', Admin\PromoAnnouncementController::class)->except('show');
        Route::resource('testimonials', Admin\TestimonialController::class)->except('show');
        Route::resource('navigation', Admin\NavigationController::class)->except('show')->parameters(['navigation' => 'item']);

        Route::get('settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    });
});

// Login route name expected by Laravel's auth middleware.
Route::redirect('/login', '/admin/login')->name('login');

/*
|--------------------------------------------------------------------------
| CMS pages (must stay last — catches /{slug})
|--------------------------------------------------------------------------
*/

Route::get('/{page:slug}', [PageController::class, 'show'])
    ->where('page', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('pages.show');
