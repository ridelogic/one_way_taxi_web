<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin-only response headers: the CMS can't be framed by another site (clickjacking), browsers
 * don't MIME-sniff its responses, and its pages aren't stored in the browser cache, so the Back button
 * can't show admin data after signing out. Applied to the /admin route group only — public pages are unaffected.
 */
class AdminSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'none'");
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'same-origin');

        // File downloads and media keep their own caching rules; HTML pages are never cached.
        if (str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
