<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * The family announcement shown on the dashboard home: one card, from the hub's public list
 * `{hub}/announcements.json?app=biohop`. Fetched by the server (never the browser), two seconds at most, kept for five
 * minutes; when the hub does not answer the last good copy is used (or nothing) and the hub is left alone for a minute,
 * so a dead hub never slows the dashboard down or breaks it. No hub address (a stand-alone install): nothing is fetched.
 */
class FamilyAnnouncements
{
    public const APP = 'biohop';

    public const TIMEOUT = 2;

    public const FRESH_SECONDS = 300;

    public const DOWN_SECONDS = 60;

    public const STALE_SECONDS = 86400;

    public const BODY_LIMIT = 300;

    private const KEY = 'family-announcements:'.self::APP;

    /** The reminder key under which a member hides the announcement with this id for a week, or null for an id that cannot be one. */
    public static function reminderKey(string $id): ?string
    {
        return preg_match('/^[A-Za-z0-9_-]{1,40}$/', $id) ? 'ann-'.$id : null;
    }

    /** The one card to show this member now, or null: critical first, else the newest; hidden ones (except critical) skipped. */
    public static function cardFor(int $userId): ?array
    {
        $shown = array_values(array_filter(self::items(), fn (array $i) => $i['level'] === 'critical' || ! Reminders::hidden($userId, $i['key'])));
        usort($shown, fn ($a, $b) => [$b['level'] === 'critical', $b['published']] <=> [$a['level'] === 'critical', $a['published']]);

        return $shown[0] ?? null;
    }

    /** Whether this reminder key belongs to an announcement the hub lists right now (so only real ones can be hidden). */
    public static function isLiveKey(string $key): bool
    {
        foreach (self::items() as $item) {
            if ($item['key'] === $key) {
                return $item['level'] !== 'critical';
            }
        }

        return false;
    }

    /** @return array<int, array{key:string,level:string,title:string,body:string,url:?string,published:int}> */
    public static function items(): array
    {
        $hub = rtrim((string) config('hop.hub_url'), '/');
        if ($hub === '' || ! config('hop.announcements')) {
            return [];
        }
        $fresh = Cache::get(self::KEY);
        if (is_array($fresh)) {
            return self::live($fresh);
        }
        $stale = Cache::get(self::KEY.':stale');
        if (Cache::has(self::KEY.':down')) {
            return self::live(is_array($stale) ? $stale : []);
        }
        try {
            $res = Http::timeout(self::TIMEOUT)->connectTimeout(self::TIMEOUT)->acceptJson()->get($hub.'/announcements.json', ['app' => self::APP]);
            $json = $res->successful() ? $res->json() : null;
            if (! is_array($json) || ($json['version'] ?? null) !== 1 || ! is_array($json['items'] ?? null)) {
                throw new \RuntimeException('not an announcement list');
            }
            $items = [];
            foreach (array_slice($json['items'], 0, 20) as $raw) {
                if ($item = self::clean($raw)) {
                    $items[] = $item;
                }
            }
            Cache::put(self::KEY, $items, self::FRESH_SECONDS);
            Cache::put(self::KEY.':stale', $items, self::STALE_SECONDS);

            return self::live($items);
        } catch (\Throwable) {
            Cache::put(self::KEY.':down', true, self::DOWN_SECONDS);

            return self::live(is_array($stale) ? $stale : []);
        }
    }

    /** One item of the hub's list as a plain, safe array; null when it is not usable here. */
    private static function clean(mixed $raw): ?array
    {
        if (! is_array($raw)) {
            return null;
        }
        $key = self::reminderKey((string) ($raw['id'] ?? ''));
        $title = trim(strip_tags((string) ($raw['title'] ?? '')));
        $apps = is_array($raw['apps'] ?? null) ? $raw['apps'] : ['*'];
        if ($key === null || $title === '' || (! in_array('*', $apps, true) && ! in_array(self::APP, $apps, true))) {
            return null;
        }
        $url = trim((string) ($raw['url'] ?? ''));
        $level = in_array($raw['level'] ?? '', ['info', 'warning', 'critical'], true) ? $raw['level'] : 'info';

        return [
            'key' => $key,
            'level' => $level,
            'title' => mb_substr($title, 0, 200),
            'body' => mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags((string) ($raw['body'] ?? '')))), 0, self::BODY_LIMIT),
            'url' => preg_match('#^https?://[^\s<>"\']+$#i', $url) ? $url : null,
            'published' => self::time($raw['published_at'] ?? null) ?? 0,
            'expires' => self::time($raw['expires_at'] ?? null),
        ];
    }

    /** Items that are on air now (checked again on every read, since a copy can be five minutes old). */
    private static function live(array $items): array
    {
        $now = time();

        return array_values(array_filter($items, fn (array $i) => $i['published'] <= $now && (($i['expires'] ?? null) === null || $i['expires'] > $now)));
    }

    private static function time(mixed $value): ?int
    {
        try {
            return $value ? Carbon::parse((string) $value)->getTimestamp() : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
