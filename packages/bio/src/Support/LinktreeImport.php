<?php

namespace Hop\Bio\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Reads a public Linktree page (linktr.ee/<username>) into plain arrays: profile, links (with Linktree's
 * groups and headers) and social icons. Only the public page is read, nothing is signed in to.
 */
class LinktreeImport
{
    public const HOST = 'linktr.ee';

    /** Where Linktree keeps profile pictures and thumbnails; only these are downloaded. */
    public const PICTURE_HOSTS = ['linktr.ee', 'ugc.production.linktr.ee', 'cloudfront.net'];

    public const MAX_PICTURE_BYTES = 3 * 1024 * 1024;

    /** Linktree social types that are not simply the lower-cased platform id. */
    private const SOCIALS = ['twitter' => 'x', 'email_address' => 'email', 'apple_music' => 'apple-music', 'apple_podcasts' => 'apple-podcasts', 'amazon_music' => 'amazon-music', 'youtube_music' => 'youtube-music', 'cash_app' => 'cash-app', 'buy_me_a_coffee' => 'buy-me-a-coffee', 'google_play' => 'google-play', 'app_store' => 'appstore', 'phone_number' => 'phone'];

    /** "linktr.ee/name", "https://linktr.ee/name?x", "@name" or "name" -> name; null when it is not a Linktree address. */
    public static function username(string $input): ?string
    {
        $input = trim($input);
        if (preg_match('#^(?:https?://)?(?:www\.)?linktr\.ee/([A-Za-z0-9._-]+)#i', $input, $m)) {
            return $m[1];
        }
        if (preg_match('#^@?([A-Za-z0-9._-]{1,60})$#', $input, $m) && ! str_contains($input, '/')) {
            return $m[1];
        }

        return null;
    }

    /**
     * @return array{username:string, title:string, bio:?string, picture:?string, theme:?string, links:array<int,array{type:string,title:string,url:?string,thumbnail:?string,children:array<int,array{title:string,url:?string,thumbnail:?string}>}>, socials:array<int,array{platform:string,value:string}>}
     *
     * @throws \RuntimeException with a message for the member when the page cannot be read
     */
    public static function fetch(string $username): array
    {
        try {
            // SafeFetch: private addresses refused (again on every redirect), connection pinned to the checked addresses
            $response = SafeFetch::get('https://'.self::HOST.'/'.rawurlencode($username), 2_000_000, 12, 'text/html,application/xhtml+xml');
        } catch (\Throwable $e) {
            throw new \RuntimeException($e->getCode() === 404 ? __('There is no Linktree page with that name.') : __('Linktree did not answer. Try again in a moment.'));
        }

        return self::parse($response['body'], $username);
    }

    /** @return array<string, mixed> */
    public static function parse(string $html, string $fallbackUsername = ''): array
    {
        if (! preg_match('#<script[^>]*id="__NEXT_DATA__"[^>]*>(.*?)</script>#s', $html, $m)) {
            throw new \RuntimeException(__('That page could not be read. Linktree may have changed its layout, or the page is private.'));
        }
        $data = json_decode(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5), true);
        // Linktree's page data is not ours: anything that is not the expected shape is treated as empty, never as a crash.
        $props = is_array($data['props']['pageProps'] ?? null) ? $data['props']['pageProps'] : [];
        $account = is_array($props['account'] ?? null) ? $props['account'] : [];
        if (($props['hasPagePassword'] ?? false) || ($props['pageLockType'] ?? null)) {
            throw new \RuntimeException(__('That Linktree page is locked, so nothing can be read from it.'));
        }
        $links = [];
        foreach (self::items($props['links'] ?? null) as $link) {
            if (! is_array($link) || ! empty($link['parent'])) {
                continue;
            }
            $type = strtoupper(self::scalar($link['type'] ?? 'CLASSIC'));
            $children = [];
            foreach (self::items($link['children'] ?? null) as $child) {
                if (is_array($child) && self::address($child['url'] ?? null)) {
                    $children[] = ['title' => self::text($child['title'] ?? ''), 'url' => self::address($child['url']), 'thumbnail' => self::picture($child['thumbnail'] ?? null)];
                }
            }
            $url = self::address($link['url'] ?? null);
            if ($type === 'HEADER' || ($type === 'GROUP' || $children !== [])) {
                $links[] = ['type' => $children !== [] ? 'collection' : 'header', 'title' => self::text($link['title'] ?? ''), 'url' => null, 'thumbnail' => null, 'children' => $children];
            } elseif ($url) {
                $links[] = ['type' => 'link', 'title' => self::text($link['title'] ?? '') ?: (string) parse_url($url, PHP_URL_HOST), 'url' => $url, 'thumbnail' => self::picture($link['thumbnail'] ?? null), 'children' => []];
            }
        }
        $socials = [];
        $brands = config('brands.platforms', []);
        foreach (self::items($props['socialLinks'] ?? null) as $social) {
            $type = strtolower(self::scalar($social['type'] ?? ''));
            $platform = self::SOCIALS[$type] ?? str_replace('_', '-', $type);
            $url = self::address($social['url'] ?? null, true);
            if ($url && isset($brands[$platform])) {
                $socials[] = ['platform' => $platform, 'value' => $url];
            } elseif ($url) {
                $links[] = ['type' => 'link', 'title' => ucfirst($type), 'url' => $url, 'thumbnail' => null, 'children' => []];
            }
        }
        $username = self::scalar($props['username'] ?? $account['username'] ?? $fallbackUsername);
        $title = self::text($props['pageTitle'] ?? $account['pageTitle'] ?? '') ?: ltrim($username, '@');
        $theme = $props['theme'] ?? null;

        return [
            'username' => $username,
            'title' => Str::limit(ltrim($title, '@'), 45, ''),
            'bio' => Str::limit(self::text($props['description'] ?? $account['description'] ?? ''), 155, '') ?: null,
            'picture' => self::picture($props['profilePictureUrl'] ?? $account['profilePictureUrl'] ?? null),
            'theme' => is_array($theme) ? ($theme['key'] ?? null) : (is_string($theme) ? $theme : null),
            'links' => $links,
            'socials' => $socials,
        ];
    }

    /** Downloads the profile picture onto the public disk; null when it is not from Linktree or too big. */
    public static function storePicture(?string $url): ?string
    {
        if (! $url || ! self::picture($url)) {
            return null;
        }
        try {
            $response = SafeFetch::get($url, self::MAX_PICTURE_BYTES, 12, 'image/*');
        } catch (\Throwable) {
            return null;
        }
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$response['type']] ?? null;
        if (! $ext || $response['body'] === '') {
            return null;
        }
        $path = 'avatars/'.Str::lower(Str::random(24)).'.'.$ext;
        Storage::disk('public')->put($path, $response['body']);

        return $path;
    }

    /** A scalar as a string; arrays and objects (a changed Linktree layout) become ''. */
    private static function scalar(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /** The entries of a list that really is a list, at most 200 (the import must not outgrow the block limit). */
    private static function items(mixed $value): array
    {
        return is_array($value) ? array_slice($value, 0, 200) : [];
    }

    private static function text(mixed $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags(self::scalar($value))) ?? '');
    }

    private static function address(mixed $url, bool $allowContact = false): ?string
    {
        $url = trim(self::scalar($url));
        $schemes = $allowContact ? '(?:https?://|mailto:|tel:|sms:)' : 'https?://';
        if ($url === '' || ! preg_match('#^'.$schemes.'#i', $url) || strlen($url) > 500) {
            return null;
        }

        return $url;
    }

    private static function picture(mixed $url): ?string
    {
        $url = trim(self::scalar($url));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (! str_starts_with($url, 'https://') || $host === '') {
            return null;
        }
        foreach (self::PICTURE_HOSTS as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return $url;
            }
        }

        return null;
    }
}
