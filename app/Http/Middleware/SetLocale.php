<?php

namespace App\Http\Middleware;

use Closure;
use Hop\Ui\Locales;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * The page language: ?lang= (remembered in a cookie for a year), then the cookie, then
 * English. The browser's Accept-Language is not used, so English stays the default
 * (owner decision). Texts live in lang/{locale}.json; English is the key itself.
 * The nine languages come from hop/ui (Hop\Ui\Locales); a stored `zh` or `pt` means
 * `zh-CN` or `pt-BR`, and a retired language such as `ar` falls back to English.
 *
 * hop_lang is the family's language cookie (KONTRAK-PREFERENSI): on hel.ink and its subdomains it belongs to .hel.ink,
 * so a language picked in one HOP app is the language of the next; elsewhere (localhost, a self-hosted copy) it stays
 * on the host. It is plain text (bootstrap/app.php), because Go and JavaScript read it too.
 */
class SetLocale
{
    public const COOKIE = 'hop_lang';

    /** Marks a browser whose old host-only hop_lang was moved to .hel.ink (once; see migrate()). Plain text. */
    public const MIGRATED = 'hop_pref';

    private const YEAR = 31536000;

    public function handle(Request $request, Closure $next): Response
    {
        $requested = self::requested($request);
        $locale = self::fromRequest($request);
        app()->setLocale($locale);
        $response = $next($request);
        $family = self::familyHost($request);
        if ($family) {
            self::migrate($request, $response);
        }
        if ($requested !== null) {
            $response->headers->setCookie(self::cookie(self::COOKIE, $requested, $family ? '.hel.ink' : null, $family || $request->isSecure()));
        }

        return $response;
    }

    /** hel.ink or one of its subdomains: the family cookies are shared there. */
    public static function familyHost(Request $request): bool
    {
        return (bool) preg_match('/(^|\.)hel\.ink$/', strtolower($request->getHost()));
    }

    /**
     * One-time move on a family host. Until now hop_lang was host-only; a browser that keeps it and also gets the
     * .hel.ink cookie sends both, the old one first, so the old one would win. Without the marker hop_pref=2 the answer
     * expires the host-only cookie, sets the .hel.ink one to the value the request carried, and sets the marker.
     * A request that carries no hop_lang has nothing to move and gets no cookie, so a plain public bio page stays
     * cacheable at the edge (README "Behind Cloudflare").
     */
    private static function migrate(Request $request, Response $response): void
    {
        if ($request->cookies->get(self::MIGRATED) === '2' || self::cookieValue($request) === null) {
            return;
        }
        $response->headers->setCookie(self::cookie(self::COOKIE, '', null, true, 0));
        $carried = Locales::resolve(self::cookieValue($request));
        if ($carried !== null) {
            $response->headers->setCookie(self::cookie(self::COOKIE, $carried, '.hel.ink', true));
        }
        $response->headers->setCookie(self::cookie(self::MIGRATED, '2', null, true));
    }

    /** Path=/, one year (Max-Age=0 to expire), SameSite=Lax, readable by scripts, value as is. */
    private static function cookie(string $name, string $value, ?string $domain, bool $secure, int $maxAge = self::YEAR): Cookie
    {
        return Cookie::create($name, $value, $maxAge === 0 ? 1 : time() + $maxAge, '/', $domain, $secure, false, true, Cookie::SAMESITE_LAX);
    }

    /** The language asked for in ?lang=, or null. */
    private static function requested(Request $request): ?string
    {
        return Locales::resolve(is_string($request->query('lang')) ? $request->query('lang') : '');
    }

    /**
     * The page language from the query and the cookie only (no database), so the error pages,
     * which can render before this middleware ran or when the database is down, can use it too.
     */
    public static function fromRequest(Request $request): string
    {
        return self::requested($request) ?? Locales::normalize(self::cookieValue($request) ?? Locales::DEFAULT);
    }

    /**
     * The value of hop_lang. A browser that still holds this host's old host-only cookie besides the newer .hel.ink one
     * sends both, oldest first, and PHP keeps the first: the last one in the raw Cookie header is the newer family
     * choice, so that one counts (KONTRAK-PREFERENSI). With a single hop_lang this is the cookie as PHP read it.
     */
    public static function cookieValue(Request $request): ?string
    {
        $last = null;
        foreach (explode(';', (string) $request->headers->get('Cookie', '')) as $pair) {
            $parts = explode('=', $pair, 2);
            if (count($parts) === 2 && trim($parts[0]) === self::COOKIE) {
                $last = urldecode(trim($parts[1]));
            }
        }
        $value = $last ?? $request->cookies->get(self::COOKIE);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
