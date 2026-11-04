<?php

namespace App\Http\Middleware;

use Closure;
use Hop\Ui\Locales;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The page language: ?lang= (remembered in a cookie for a year), then the cookie, then
 * English. The browser's Accept-Language is not used, so English stays the default
 * (owner decision). Texts live in lang/{locale}.json; English is the key itself.
 * The nine languages come from hop/ui (Hop\Ui\Locales); a stored `zh` or `pt` means
 * `zh-CN` or `pt-BR`, and a retired language such as `ar` falls back to English.
 */
class SetLocale
{
    public const COOKIE = 'hop_lang';

    public function handle(Request $request, Closure $next): Response
    {
        $requested = self::requested($request);
        $locale = self::fromRequest($request);
        app()->setLocale($locale);
        $response = $next($request);
        if ($requested !== null) {
            $response->headers->setCookie(cookie()->forever(self::COOKIE, $requested, null, null, $request->isSecure(), false, false, 'lax'));
        }

        return $response;
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
        return self::requested($request) ?? Locales::normalize((string) $request->cookie(self::COOKIE, Locales::DEFAULT));
    }
}
