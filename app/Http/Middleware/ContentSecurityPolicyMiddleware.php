<?php

namespace App\Http\Middleware;

use App\Bio\OfficialPage;
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
        // The official page, asked for as an embed, may sit in a frame on the HOP sites (the BioHop home).
        $embed = OfficialPage::isEmbed($request);
        $request->attributes->set('hop.embed', $embed);
        $response = $next($request);
        $isPreviewRoute = $request->routeIs('bio.preview');
        $hub = rtrim((string) config('hop-auth.hub_url', config('hop.hub_url')), '/');
        // Public bio pages are served at PUBLIC_URL (hel.ink/b/...) while the built assets, fonts, pictures and audio
        // live at ASSET_URL (default APP_URL, bio.hel.ink), so that origin must be allowed next to 'self'.
        $assets = ' '.self::originOf(bio_asset_origin());
        // The BioHop home frames the official page from the public host (hel.ink/b/hel?embed=1).
        $publicHost = self::originOf(public_url());
        // https://hel.ink serves the shared HOP family assets (fonts, floating tiles; hop/ui). Nothing is loaded from a CDN;
        // the hosts named below for pixels are a page owner's own optional, consent-gated Google, Meta and TikTok pixels.
        $csp = [
            "default-src 'self'",
            "script-src 'self'{$assets} https://hel.ink 'unsafe-inline' 'unsafe-eval' https://challenges.cloudflare.com https://static.cloudflareinsights.com https://www.googletagmanager.com https://www.google-analytics.com https://connect.facebook.net https://analytics.tiktok.com",
            "style-src 'self'{$assets} https://hel.ink 'unsafe-inline'",
            "img-src 'self' data: https: blob: https://www.google-analytics.com https://www.facebook.com",
            "media-src 'self'{$assets}",
            "font-src 'self'{$assets} https://hel.ink data:",
            "connect-src 'self'{$assets} data: blob: https://challenges.cloudflare.com https://www.google-analytics.com https://analytics.google.com https://www.facebook.com https://analytics.tiktok.com",
            "frame-src 'self' {$publicHost} https://challenges.cloudflare.com https://maps.google.com https://www.google.com https://youtube.com ".implode(' ', BioEmbed::FRAME_HOSTS),
            "object-src 'none'",
            "base-uri 'self'",
            // Signing in ends at the hub, and logging out hops hub -> each app's logout -> the home page.
            // form-action applies to every redirect after a form post, so every HOP host is allowed.
            "form-action 'self' ".self::chainOrigins($hub),
            $embed ? "frame-ancestors 'self' ".self::hopOrigins($hub) : ($isPreviewRoute ? "frame-ancestors 'self'" : "frame-ancestors 'none'"),
            'upgrade-insecure-requests',
        ];
        $response->headers->set('Content-Security-Policy', implode('; ', $csp));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // X-Frame-Options cannot name several origins, so an embed relies on frame-ancestors alone.
        if (! $embed) {
            $response->headers->set('X-Frame-Options', $isPreviewRoute ? 'SAMEORIGIN' : 'DENY');
        } else {
            $response->headers->remove('X-Frame-Options');
        }
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        return $response;
    }

    /**
     * Where the sign-out chain may go after a form post: every origin above plus every https subdomain of the family's
     * domain (hub.hel.ink -> *.hel.ink), because the chain passes apps this app does not list (NoteHop).
     */
    private static function chainOrigins(string $hub): string
    {
        $origins = self::hopOrigins($hub);
        $parts = parse_url($hub);
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (($parts['scheme'] ?? '') === 'https' && substr_count($host, '.') >= 2) {
            $origins .= ' https://*.'.preg_replace('/^[^.]+\./', '', $host);
        }

        return $origins;
    }

    /** scheme://host[:port] of an address (a source in a CSP list takes no path here). */
    private static function originOf(string $url): string
    {
        $parts = parse_url($url);

        return ! empty($parts['scheme']) && ! empty($parts['host'])
            ? $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '')
            : "'self'";
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
