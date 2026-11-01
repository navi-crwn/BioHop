<?php

namespace App\Bio;

use Hop\Ui\Assets;

/**
 * Addresses that belong to the HOP family (hel.ink and its subdomains, plus whatever the shared manifest lists).
 * A link to one of them is already short and already ours, so a bio block keeps it as it is instead of
 * wrapping it in another short link.
 */
final class FamilyUrls
{
    /** @return list<string> hosts of the manifest's apps (hub, link, bio, pixel, note, p.hel.ink ...) */
    public static function hosts(): array
    {
        $hosts = ['hel.ink'];
        foreach (Assets::family() as $app) {
            foreach ([$app['url'] ?? null, $app['account_url'] ?? null] as $url) {
                if ($url && ($host = parse_url((string) $url, PHP_URL_HOST))) {
                    $hosts[] = strtolower($host);
                }
            }
        }
        $own = parse_url((string) config('app.url'), PHP_URL_HOST);
        if ($own) {
            $hosts[] = strtolower($own);
        }

        return array_values(array_unique($hosts));
    }

    public static function isFamily(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }
        if ($host === 'hel.ink' || str_ends_with($host, '.hel.ink')) {
            return true;
        }

        return in_array($host, self::hosts(), true);
    }
}
