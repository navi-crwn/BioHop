<?php

namespace App\Http\Middleware;

use Closure;
use Hop\Bio\Support\BioEmbed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers for every web response. The frame-src list comes from the
 * bio package (BioEmbed::FRAME_HOSTS: YouTube, Spotify, … embeds on bio pages);
 * the editor's preview may be framed by the editor itself (bio.preview).
 */
class ContentSecurityPolicyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Per-request nonce: added to every <script> tag (Vite::cspNonce()) and @vite output.
        Vite::useCspNonce();
        $response = $next($request);
        $isPreviewRoute = $request->routeIs('bio.preview');
        $hub = rtrim((string) config('hop-auth.hub_url', config('hop.hub_url')), '/');
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://fonts.bunny.net https://fonts.googleapis.com https://cdn.jsdelivr.net https://unpkg.com https://static.cloudflareinsights.com https://www.googletagmanager.com https://www.google-analytics.com https://connect.facebook.net https://analytics.tiktok.com",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com https://cdn.jsdelivr.net https://unpkg.com",
            "img-src 'self' data: https: blob: https://flagcdn.com https://www.google-analytics.com https://www.facebook.com",
            "font-src 'self' https://fonts.bunny.net https://fonts.gstatic.com data:",
            "connect-src 'self' data: blob: https://cdn.jsdelivr.net https://unpkg.com https://www.google-analytics.com https://analytics.google.com https://www.facebook.com https://analytics.tiktok.com",
            "frame-src 'self' https://maps.google.com https://www.google.com https://youtube.com ".implode(' ', BioEmbed::FRAME_HOSTS),
            "object-src 'none'",
            "base-uri 'self'",
            // Signing in ends in a redirect to the hub; form-action applies to that redirect too.
            "form-action 'self' ".$hub,
            $isPreviewRoute ? "frame-ancestors 'self'" : "frame-ancestors 'none'",
            'upgrade-insecure-requests',
        ];
        $response->headers->set('Content-Security-Policy', implode('; ', $csp));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', $isPreviewRoute ? 'SAMEORIGIN' : 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        return $response;
    }
}
