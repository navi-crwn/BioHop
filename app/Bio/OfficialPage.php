<?php

namespace App\Bio;

use Hop\Bio\Models\BioPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * The official HEL.ink page (hel.ink/b/hel). The BioHop home shows it live inside a phone, so what happens to
 * that page is what visitors see there. Only this page, and only as `?embed=1`, may be framed, and an embedded
 * view is not counted as a visit.
 */
final class OfficialPage
{
    public static function slug(): string
    {
        return strtolower((string) config('hop.official_bio', 'hel'));
    }

    /** A GET of the official page asked for as an embed. */
    public static function isEmbed(Request $request): bool
    {
        return $request->isMethodSafe() && $request->query('embed') === '1' && $request->is('b/'.self::slug());
    }

    /** Where the phone on the home page loads from; null when the page is missing or not published (the static copy stays). */
    public static function embedUrl(): ?string
    {
        $live = Cache::remember('official-bio-live', 60, fn () => BioPage::where('slug', self::slug())->where('is_published', true)->where('password_enabled', false)->exists());

        return $live ? public_url('b/'.self::slug()).'?embed=1' : null;
    }
}
