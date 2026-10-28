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
        // Public bio pages are served at PUBLIC_URL (hel.ink/b/...) while the built assets and uploads
        // live at ASSET_URL (bio.hel.ink), so that origin must be allowed next to 'self'.
        $assets = rtrim((string) config('app.asset_url'), '/');
        $assets = $assets !== '' ? ' '.$assets : '';
        $csp = [
            "default-src 'self'",
            "script-src 'self'{$assets} 'unsafe-inline' 'unsafe-eval' https://fonts.bunny.net https://fonts.googleapis.com https://cdn.jsdelivr.net https://unpkg.com https://static.cloudflareinsights.com https://www.googletagmanager.com https://www.google-analytics.com https://connect.facebook.net https://analytics.tiktok.com",
            "style-src 'self'{$assets} 'unsafe-inline' https://fonts.bunny.net https://fonts.googleapis.com https://cdn.jsdelivr.net https://unpkg.com",
            "img-src 'self' data: https: blob: https://flagcdn.com https://www.google-analytics.com https://www.facebook.com",
            "font-src 'self'{$assets} https://fonts.bunny.net https://fonts.gstatic.com data:",
            "connect-src 'self'{$assets} data: blob: https://cdn.jsdelivr.net https://unpkg.com https://www.google-analytics.com https://analytics.google.com https://www.facebook.com https://analytics.tiktok.com",
            "frame-src 'self' https://maps.google.com https://www.google.com https://youtube.com ".implode(' ', BioEmbed::FRAME_HOSTS),
            "object-src 'none'",
            "base-uri 'self'",
            // Signing in ends at the hub, and logging out hops hub -> each app's logout -> the home page.
            // form-action applies to every redirect after a form post, so every HOP host is allowed.
            "form-action 'self' ".self::hopOrigins($hub),
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

    /** Origins of the hub, every HOP app and the public host, space-separated, without duplicates. */
    private static function hopOrigins(string $hub): string
    {
        $urls = [$hub, public_url()];
        foreach ((array) config('hop.apps', []) as $app) {
            $urls[] = (string) ($app['url'] ?? '');
        }
        $origins = [];
        foreach ($urls as $url) {
            $parts = parse_url($url);
            if (! empty($parts['scheme']) && ! empty($parts['host'])) {
                $origins[] = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
            }
        }

        return implode(' ', array_values(array_unique($origins)));
    }
}
