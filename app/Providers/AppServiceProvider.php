<?php

namespace App\Providers;

use App\Models\NavigationItem;
use App\Models\Service;
use App\Support\AppLinks;
use App\Support\Settings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Settings::class);
        $this->app->singleton(AppLinks::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('partials.pagination');

        RateLimiter::for('bookings', fn (Request $request) => Limit::perMinute(6)->by($request->ip()));
        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(4)->by($request->ip()));
        RateLimiter::for('fare-estimate', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        View::share('settings', $this->app->make(Settings::class));
        View::share('appLinks', $this->app->make(AppLinks::class));

        View::composer(['partials.header', 'partials.footer'], function ($view) {
            $view->with('menus', NavigationItem::active()->orderBy('sort_order')->get()->groupBy('menu'));
            $view->with('navServices', Service::active()->ordered()->get(['title', 'slug', 'icon']));
        });
    }
}
