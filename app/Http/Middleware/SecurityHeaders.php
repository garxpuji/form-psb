<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Attach defensive HTTP security headers to all responses.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(self), microphone=()');

        if (! $response->headers->has('Content-Security-Policy')) {
            $isViteHotDev = app()->isLocal() && is_file(public_path('hot'));

            $scriptSrc = $isViteHotDev
                ? "script-src 'self' 'unsafe-inline' 'unsafe-eval' http: https:"
                : "script-src 'self' 'unsafe-inline' 'unsafe-eval'";

            $styleSrc = $isViteHotDev
                ? "style-src 'self' 'unsafe-inline' http: https:"
                : "style-src 'self' 'unsafe-inline'";

            $connectSrc = $isViteHotDev
                ? "connect-src 'self' http: https: ws: wss:"
                : "connect-src 'self'";

            $csp = implode('; ', [
                "default-src 'self'",
                $scriptSrc,
                $styleSrc,
                "img-src 'self' data: blob:",
                "font-src 'self' data:",
                $connectSrc,
                "object-src 'none'",
                "frame-ancestors 'none'",
                "base-uri 'self'",
                "form-action 'self'",
            ]);

            $response->headers->set('Content-Security-Policy', $csp);
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
