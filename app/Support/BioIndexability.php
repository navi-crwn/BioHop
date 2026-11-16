<?php

namespace App\Support;

use App\Models\AbuseFlag;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Illuminate\Database\Eloquent\Builder;

/**
 * Whether a public bio page is offered to search engines. One rule for two places: the sitemap leaves a page out
 * and the page itself says noindex (still reachable by anyone with the link). A page is indexable when it is published,
 * its owner allowed indexing, it is not adult or sensitive, and it is not a thin page: enough blocks, a real description,
 * and no open finding of the abuse scan (abuse:scan).
 */
final class BioIndexability
{
    /** At least this many enabled blocks (dividers, spacers and headings do not count). */
    public const MIN_BLOCKS = 3;

    /** The description (SEO description, else the bio text) needs at least this many characters. */
    public const MIN_DESCRIPTION_CHARS = 50;

    /** Block kinds that only decorate a page. */
    public const DECORATION_TYPES = ['divider', 'spacer', 'header'];

    /** Pages the owner or the rules already keep out of search; the sitemap query starts from these. */
    public static function scopeAllowed(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where('allow_indexing', true)
            ->where('is_adult_content', false)
            ->where(fn ($q) => $q->whereNull('content_warning')->orWhere('content_warning', 'none'));
    }

    /** Enabled blocks that count towards the bar, as a count query for withCount(). */
    public static function countableBlocks(): \Closure
    {
        return fn ($q) => $q->active()->whereNotIn('type', [...BioLink::RETIRED_TYPES, ...self::DECORATION_TYPES]);
    }

    /** Ids of bio pages with a finding of the abuse scan that an admin has not dismissed. */
    public static function flaggedPageIds(): array
    {
        return AbuseFlag::where('subject_type', 'bio_page')
            ->where('status', '!=', AbuseFlag::STATUS_DISMISSED)
            ->pluck('subject_id')->all();
    }

    public static function description(BioPage $page): string
    {
        return trim((string) ($page->seo_description ?: $page->bio));
    }

    /**
     * @param  int|null  $blocks  countable blocks, when the caller already counted them
     * @param  bool|null  $flagged  whether the abuse scan flagged the page, when the caller already knows
     */
    public static function passes(BioPage $page, ?int $blocks = null, ?bool $flagged = null): bool
    {
        if (! $page->is_published || ! $page->allow_indexing || $page->is_adult_content
            || ! in_array($page->content_warning ?: 'none', ['none'], true)) {
            return false;
        }
        $blocks ??= $page->links()->tap(self::countableBlocks())->count();
        $flagged ??= in_array($page->id, self::flaggedPageIds(), true);

        return $blocks >= self::MIN_BLOCKS
            && mb_strlen(self::description($page)) >= self::MIN_DESCRIPTION_CHARS
            && ! $flagged;
    }
}
