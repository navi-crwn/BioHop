<?php

namespace Hop\Bio\Support;

/**
 * Fonts for the public page come from our own domain (public/fonts/bio, built by scripts/build-bio-fonts.py), not from Google:
 * no third-party request, no visitor address sent away, and the @font-face rules are small enough to inline.
 * The files are addressed in full on the asset host (bio_asset_origin()): the public page is served from another host
 * (hel.ink/b/...), which does not have them. That host answers font requests with Access-Control-Allow-Origin.
 */
class BioFonts
{
    private static ?array $manifest = null;

    /** @return array{css:string, preload:?string}|null  null for system fonts (Georgia, monospace) */
    public static function forFamily(string $family): ?array
    {
        self::$manifest ??= is_file($m = public_path('fonts/bio/manifest.json')) ? (json_decode((string) file_get_contents($m), true) ?: []) : [];
        $slug = strtolower(str_replace(' ', '-', trim($family, "' \"")));
        $entry = self::$manifest[$slug] ?? null;
        if (! $entry || ! is_file($css = public_path('fonts/bio/'.$entry['css']))) {
            return null;
        }

        $base = bio_asset_origin().'/fonts/bio/';

        return [
            'css' => str_replace('url(', 'url('.$base, (string) file_get_contents($css)),
            'preload' => $entry['preload'] ? $base.$entry['preload'] : null,
        ];
    }
}
