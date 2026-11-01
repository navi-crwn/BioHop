<?php

namespace Hop\Bio\Support;

use Hop\Safety\UrlGuard;
use Illuminate\Support\Facades\Http;

/**
 * Fetching an address the member typed, from our server: never a private or reserved network (checked again on every
 * redirect), at most two redirects, a short timeout and a size cap. Used by the feed block and the product-details helper.
 */
class SafeFetch
{
    public const MAX_REDIRECTS = 2;

    /**
     * @return array{body:string, type:string, url:string}
     *
     * @throws \RuntimeException
     */
    public static function get(string $url, int $maxBytes = 1_000_000, float $timeout = 3.0, string $accept = '*/*'): array
    {
        $guard = app(UrlGuard::class);
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            if (! $guard->isSafe($url)) {
                throw new \RuntimeException('Address not allowed');
            }
            $response = Http::timeout($timeout)->connectTimeout(min(2.0, $timeout))->withOptions(['allow_redirects' => false])
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; BioHop fetch)', 'Accept' => $accept, 'Accept-Language' => 'en'])
                ->get($url);
            if (in_array($response->status(), [301, 302, 303, 307, 308], true) && ($next = $response->header('Location'))) {
                $url = (string) (parse_url($next, PHP_URL_SCHEME) ? $next : self::absolute($url, $next));

                continue;
            }
            if (! $response->ok() || strlen($response->body()) > $maxBytes) {
                throw new \RuntimeException('Not readable');
            }

            return ['body' => $response->body(), 'type' => strtolower(strtok((string) $response->header('Content-Type'), ';') ?: ''), 'url' => $url];
        }
        throw new \RuntimeException('Too many redirects');
    }

    public static function absolute(string $base, string $location): string
    {
        $p = parse_url($base);
        if (str_starts_with($location, '//')) {
            return ($p['scheme'] ?? 'https').':'.$location;
        }

        return ($p['scheme'] ?? 'https').'://'.($p['host'] ?? '').(isset($p['port']) ? ':'.$p['port'] : '').(str_starts_with($location, '/') ? '' : '/').$location;
    }
}
