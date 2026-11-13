<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reminder cards a member can hide (the shared HOP "Hide · Shows again next week" button, data-hop-remind).
 * Hiding stores dismissed_at per account and card. The card stays away for seven days and then comes back,
 * unless its task is done by then: a finished task that was hidden stays hidden for good.
 */
class Reminders
{
    /** The cards this app has. Anything else is refused, so the table cannot be filled with made-up keys. */
    public const KEYS = ['setup-checklist', 'whatsapp-rotator-retired'];

    public const HIDE_DAYS = 7;

    public static function dismiss(int $userId, string $key): CarbonInterface
    {
        $now = Carbon::now();
        DB::table('bio_reminders')->updateOrInsert(
            ['user_id' => $userId, 'key' => $key],
            ['dismissed_at' => $now, 'updated_at' => $now, 'created_at' => $now],
        );

        return $now->copy()->addDays(self::HIDE_DAYS);
    }

    /**
     * The announcement that the WhatsApp rotator block is being retired: shown to members who still have such a block
     * (the block cannot be added any more; old ones keep working), until they hide it for a week.
     */
    public static function whatsappRetiredNotice(int $userId): bool
    {
        $has = \Hop\Bio\Models\BioLink::query()->where('type', 'whatsapp')
            ->whereIn('bio_page_id', \Hop\Bio\Models\BioPage::query()->where('user_id', $userId)->select('id'))->exists();

        return $has && ! self::hidden($userId, 'whatsapp-rotator-retired');
    }

    /** The day the rotator stops for good, as a date in the member's language; null while the owner has not set one. */
    public static function whatsappRetireDate(): ?string
    {
        $on = trim((string) config('hop.whatsapp_retire_on'));
        if ($on === '') {
            return null;
        }
        try {
            return Carbon::parse($on)->locale(app()->getLocale())->translatedFormat('j F Y');
        } catch (\Throwable) {
            return null;
        }
    }

    /** True while the card must not be shown: hidden less than seven days ago, or hidden and its task is done. */
    public static function hidden(int $userId, string $key, bool $taskDone = false): bool
    {
        try {
            $at = DB::table('bio_reminders')->where('user_id', $userId)->where('key', $key)->value('dismissed_at');
        } catch (QueryException) {
            // The dashboard still renders when the migration has not run yet (a deploy between pull and migrate).
            return false;
        }
        if (! $at) {
            return false;
        }

        return $taskDone || Carbon::parse($at)->gt(Carbon::now()->subDays(self::HIDE_DAYS));
    }
}
