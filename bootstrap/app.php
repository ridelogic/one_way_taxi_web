<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

// Keep .env values in each request's own $_SERVER/$_ENV instead of the process-wide environment (putenv).
// Threaded PHP (e.g. XAMPP's Apache module on Windows) shares one environment between concurrent requests,
// and PHP clears putenv() values when a request ends — so under load some requests saw no .env at all
// (missing APP_KEY, wrong database). Must run before the kernel loads .env.
Env::disablePutenv();

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'admin.headers' => \App\Http\Middleware\AdminSecurityHeaders::class,
        ]);

        // The title suffix (Admin → Settings → SEO) keeps its leading space, e.g. " | One Way Taxi".
        $middleware->trimStrings(except: ['seo_title_suffix']);

        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // "Sign out" clicked in a tab whose session already ended (idle timeout, or signed out in another tab)
        // carries a stale CSRF token. The user is already signed out, so send them to the login page instead of
        // a 419 "Page Expired" screen. CSRF is still enforced: a mismatched token never signs anyone out, and if
        // a user is still signed in the normal 419 response stands.
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419 && $request->routeIs('admin.logout') && Auth::guest()) {
                return redirect()->route('admin.login')->with('status', 'Your session had already ended. Please sign in again.');
            }
        });
    })->create();
