<?php

namespace Hop\Bio\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Reads an RSS or Atom feed (a blog, a podcast) into a short list of items for a feed block.
 * The server fetches it, so every address is checked against private and reserved networks first, redirects are followed
 * by hand (at most two, each checked again), the body is capped, and the result is cached for an hour.
 */
class FeedReader
{
    public const MAX_BYTES = 1_000_000;

    public const FRESH_SECONDS = 3600;

    public const FAILED_SECONDS = 600;

    /** @return array<int, array{title:string, url:string, date:?string}> */
    public static function items(string $feedUrl, int $count = 5, bool $refresh = false): array
    {
        $count = max(1, min(10, $count));
        $key = 'bio_feed:'.sha1($feedUrl);
        if (! $refresh && ($hit = Cache::get($key)) !== null) {
            return array_slice($hit['items'], 0, $count);
        }
        $stale = Cache::get($key.':stale');
        try {
            $items = self::parse(self::download($feedUrl));
        } catch (\Throwable) {
            // keep showing the last good copy if there is one; ask again in ten minutes
            Cache::put($key, ['items' => $stale ?? []], self::FAILED_SECONDS);

            return array_slice($stale ?? [], 0, $count);
        }
        Cache::put($key, ['items' => $items], self::FRESH_SECONDS);
        Cache::put($key.':stale', $items, now()->addDays(7));

        return array_slice($items, 0, $count);
    }

    /** @throws \RuntimeException */
    public static function download(string $url): string
    {
        return SafeFetch::get($url, self::MAX_BYTES, 3.0, 'application/rss+xml, application/atom+xml, application/xml, text/xml')['body'];
    }

    /** @return array<int, array{title:string, url:string, date:?string}> */
    public static function parse(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        libxml_use_internal_errors($previous);
        if (! $doc) {
            throw new \RuntimeException('Not a feed');
        }
        $raw = [];
        if (isset($doc->channel->item)) {
            foreach ($doc->channel->item as $item) {
                $raw[] = [(string) $item->title, (string) $item->link, (string) ($item->pubDate ?? '')];
            }
        } elseif ($doc->getName() === 'feed') {
            foreach ($doc->entry as $entry) {
                $href = '';
                foreach ($entry->link as $l) {
                    $rel = (string) ($l['rel'] ?? 'alternate');
                    if ($rel === 'alternate' || $href === '') {
                        $href = (string) $l['href'];
                    }
                }
                $raw[] = [(string) $entry->title, $href, (string) ($entry->published ?? $entry->updated ?? '')];
            }
        }
        $items = [];
        foreach ($raw as [$title, $link, $date]) {
            $title = Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags($title)) ?? ''), 140, '…');
            $link = trim($link);
            if ($title === '' || ! preg_match('#^https?://#i', $link) || strlen($link) > 500) {
                continue;
            }
            $time = $date !== '' ? strtotime($date) : false;
            $items[] = ['title' => $title, 'url' => $link, 'date' => $time ? date('Y-m-d', $time) : null];
            if (count($items) >= 10) {
                break;
            }
        }

        return $items;
    }
}
