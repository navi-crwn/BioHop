<?php

namespace Hop\Bio\Support;

use Hop\Bio\Models\BioPage;
use Hop\Bio\Models\BioRevision;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Versions of a page. One is taken automatically before an editing session changes things (at most one per quarter of an
 * hour), the owner can save one by name, and restoring takes one first so a restore can itself be undone.
 * A version holds the page's design and content and every block; it does not hold the address, whether the page is
 * published, the password, tracking ids, privacy switches or click counts, which a restore leaves as they are.
 */
class BioRevisions
{
    public const KEEP = 30;

    public const AUTO_EVERY_MINUTES = 15;

    public const PAGE_FIELDS = [
        'title', 'bio', 'status_text', 'status_until', 'avatar_url', 'avatar_shape', 'badge', 'badge_color', 'theme', 'layout', 'header_layout', 'cover_image',
        'background_type', 'background_value', 'background_color', 'background_gradient', 'background_image', 'text_color', 'title_color', 'bio_color',
        'link_bg_color', 'link_text_color', 'button_bg_color', 'button_text_color', 'button_shape', 'button_shadow', 'header_bg_color', 'font_family',
        'block_shape', 'block_shadow', 'social_icon_color', 'social_placement', 'social_links', 'socials', 'socials_position', 'social_icons_position',
        'hover_effect', 'background_animation', 'hide_branding', 'auto_utm', 'footer_note', 'footer_url', 'fab_type', 'fab_label', 'fab_url', 'og_image', 'favicon',
    ];

    public static function snapshot(BioPage $page, ?string $label = null): BioRevision
    {
        // read from the database: a model just created in this request has no defaults yet
        $page = $page->fresh() ?? $page;
        $blocks = $page->links()->get()->map(fn ($l) => $l->only(array_merge(['id'], $l->getFillable())))->values()->all();
        $revision = $page->revisions()->create([
            'label' => $label !== null ? mb_substr($label, 0, 60) : null,
            'blocks_count' => count($blocks),
            'snapshot' => json_encode(['page' => $page->only(self::PAGE_FIELDS), 'blocks' => $blocks], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        $old = $page->revisions()->orderByDesc('id')->skip(self::KEEP)->take(500)->pluck('id');
        if ($old->isNotEmpty()) {
            BioRevision::whereIn('id', $old)->delete();
        }

        return $revision;
    }

    /** Before a change: take a version unless one was taken a moment ago. */
    public static function maybeSnapshot(BioPage $page): void
    {
        $last = $page->revisions()->latest('id')->value('created_at');
        if ($last && now()->diffInMinutes($last, true) < self::AUTO_EVERY_MINUTES) {
            return;
        }
        try {
            self::snapshot($page);
        } catch (\Throwable) {
            // a failed version must never stop an edit
        }
    }

    /** Brings a version back: page fields are replaced, blocks are updated, recreated if deleted, and newer ones are archived (never deleted). */
    public static function restore(BioPage $page, BioRevision $revision): void
    {
        $data = json_decode($revision->snapshot, true) ?: [];
        DB::transaction(function () use ($page, $revision, $data) {
            self::snapshot($page, 'Before restoring '.$revision->created_at?->format('j M H:i'));
            $page->update(Arr::only($data['page'] ?? [], self::PAGE_FIELDS));
            $skip = array_flip(['bio_page_id', 'click_count']);
            $kept = [];
            foreach ($data['blocks'] ?? [] as $block) {
                $attrs = array_diff_key(Arr::except($block, ['id']), $skip);
                $link = isset($block['id']) ? $page->links()->find($block['id']) : null;
                if ($link) {
                    $link->update($attrs);
                } else {
                    $link = $page->links()->create($attrs + ['click_count' => 0]);
                }
                $kept[] = $link->id;
            }
            $page->links()->whereNotIn('id', $kept)->whereNull('archived_at')->update(['archived_at' => now()]);
        });
    }
}
