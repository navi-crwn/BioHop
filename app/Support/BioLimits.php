<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The limits an operator can change from the admin panel (PUT /api/hop/settings).
 * Values live in the bio_settings table, so a cache clear or an update never resets them;
 * without a stored value each limit falls back to config('bio.*') (env: BIO_*).
 */
final class BioLimits
{
    private const CACHE_KEY = 'biohop:limits';

    /** Setting key => [config key, type, min, max]. The API keys are fixed: the admin panel sends them by name. */
    public const KEYS = [
        'bio.slug_min_length' => ['slug_min_length', 'int', 3, 20],
        'bio.max_pages_per_user' => ['max_pages', 'int', 1, 100],
        'bio.max_blocks_per_page' => ['max_blocks', 'int', 1, 500],
        'bio.allow_custom_css' => ['allow_custom_css', 'bool', 0, 1],
    ];

    /** The value for a setting key: stored, else the configured default. */
    public static function get(string $key): int|bool
    {
        [$config, $type] = self::KEYS[$key];
        $value = self::stored()[$key] ?? config('bio.'.$config);

        return $type === 'bool' ? (bool) $value : (int) $value;
    }

    /** The configured default, ignoring anything stored. */
    public static function default(string $key): int|bool
    {
        [$config, $type] = self::KEYS[$key];
        $value = config('bio.'.$config);

        return $type === 'bool' ? (bool) $value : (int) $value;
    }

    public static function maxPages(): int
    {
        return self::get('bio.max_pages_per_user');
    }

    public static function maxBlocks(): int
    {
        return self::get('bio.max_blocks_per_page');
    }

    public static function allowCustomCss(): bool
    {
        return self::get('bio.allow_custom_css');
    }

    /** Shortest slug a member may take; admin accounts may always go down to 3. */
    public static function slugMin(?object $user = null): int
    {
        return $user?->isAdmin() ? 3 : self::get('bio.slug_min_length');
    }

    /** Stores already-validated values (see KEYS for the ranges). */
    public static function save(array $values): void
    {
        foreach ($values as $key => $value) {
            DB::table('bio_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => json_encode($value), 'updated_at' => now(), 'created_at' => now()],
            );
        }
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, int|bool> */
    private static function stored(): array
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }
        try {
            $rows = DB::table('bio_settings')->pluck('value', 'key');
        } catch (\Throwable) {
            return []; // the table is created by the migration: until it runs, the defaults apply
        }
        $stored = [];
        foreach ($rows as $key => $json) {
            if (isset(self::KEYS[$key])) {
                $stored[$key] = json_decode((string) $json, true);
            }
        }
        Cache::forever(self::CACHE_KEY, $stored);

        return $stored;
    }
}
