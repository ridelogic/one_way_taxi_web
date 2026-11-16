<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BusinessEnquiryController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FaqController;
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

Route::get('/routes', [RouteController::class, 'index'])->name('routes.index');
Route::get('/routes/{taxiRoute:slug}', [RouteController::class, 'show'])->name('routes.show');

Route::get('/fleet', [FleetController::class, 'index'])->name('fleet.index');
Route::get('/fleet/{vehicle:slug}', [FleetController::class, 'show'])->name('fleet.show');

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
Route::redirect('/cabs', '/fleet', 301);

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
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('logout', [Admin\AuthController::class, 'destroy'])->name('logout');
        Route::get('account', [Admin\AccountController::class, 'edit'])->name('account.edit');
        Route::put('account', [Admin\AccountController::class, 'update'])->name('account.update');

        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::patch('slides/{slide}/move', [Admin\HeroSlideController::class, 'move'])->name('slides.move');
        Route::resource('slides', Admin\HeroSlideController::class)->except('show');
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
