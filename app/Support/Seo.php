<?php

namespace App\Support;

use Hop\Ui\Locales;
use Illuminate\Http\Request;

/**
 * What search engines read on BioHop's own public pages (home, info pages, changelog, status): one canonical address
 * per page and language, the language alternates, and the home page's structured data. The family rule
 * (KONTRAK-SEO-BAHASA, same as the hub's App\Support\Seo): ?lang=<code> is the address of a page in one language, the
 * bare address is the default; every other query string is dropped and the host is always APP_URL's, never the
 * request's. Users' bio pages (/b/{slug}) are not covered here.
 */
final class Seo
{
    public const NAME = 'BioHop';

    /** The address of a path on this app, optionally in one language (?lang=, except English: the bare address). */
    public static function url(string $path = '', ?string $locale = null): string
    {
        $path = trim($path, '/');
        $url = rtrim((string) config('app.url'), '/').($path === '' ? '/' : '/'.$path);

        // English lives at the bare address (the hub's rule): ?lang=en still renders English, but it is not a second address.
        return $locale === null || $locale === 'en' ? $url : $url.'?lang='.$locale;
    }

    /** The canonical address of the page being served: its path, plus ?lang= when the request named one of the nine. */
    public static function canonical(Request $request): string
    {
        return self::url($request->path(), Locales::resolve(is_string($request->query('lang')) ? $request->query('lang') : null));
    }

    /** @return array<string, string> hreflang => address: the nine languages (each ?lang=), then x-default (bare) */
    public static function alternates(string $path = ''): array
    {
        $out = [];
        foreach (Locales::codes() as $code) {
            $out[$code] = self::url($path, $code);
        }
        $out['x-default'] = self::url($path);

        return $out;
    }

    /**
     * JSON-LD for the home page: BioHop as a web application of the HEL.ink family. The publisher is the hub's
     * organization (same @id as the hub's home page graph), so the family reads as one publisher.
     *
     * @return array<string, mixed>
     */
    public static function homeGraph(): array
    {
        $home = self::url();
        $hub = rtrim((string) config('hop.public_url'), '/').'/';

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebApplication',
                    '@id' => $home.'#app',
                    'name' => self::NAME,
                    'url' => $home,
                    'description' => __('Share all your links, social profiles, embeds, vCard and QR code on one fast, customizable page at hel.ink/b/yourname. Free, open source, and powered by your HOP ID.'),
                    'applicationCategory' => 'SocialNetworkingApplication',
                    'operatingSystem' => 'Any (web browser)',
                    'browserRequirements' => 'Requires JavaScript for the editor; bio pages work without it.',
                    'inLanguage' => Locales::codes(),
                    'isAccessibleForFree' => true,
                    'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
                    'image' => asset('images/brand/og.png'),
                    'publisher' => ['@id' => $hub.'#organization'],
                ],
                [
                    '@type' => 'Organization',
                    '@id' => $hub.'#organization',
                    'name' => 'HEL.ink',
                    'url' => $hub,
                ],
            ],
        ];
    }
}
