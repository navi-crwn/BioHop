<?php

use App\Helpers\SocialUrlHelper;

test('a pasted address without its scheme is the address, not a handle', function (string $platform, string $value, string $url) {
    expect(SocialUrlHelper::buildUrl($platform, $value))->toBe($url);
})->with([
    'youtube channel as the placeholder shows' => ['youtube', 'youtube.com/@helena', 'https://youtube.com/@helena'],
    'youtube with www' => ['youtube', 'www.youtube.com/@helena', 'https://www.youtube.com/@helena'],
    'youtu.be short address' => ['youtube', 'youtu.be/dQw4w9WgXcQ', 'https://youtu.be/dQw4w9WgXcQ'],
    'telegram as the placeholder shows' => ['telegram', 't.me/helena', 'https://t.me/helena'],
    'spotify artist page' => ['spotify', 'open.spotify.com/artist/4gzpq5DPGxSnKTe4SA8HAU', 'https://open.spotify.com/artist/4gzpq5DPGxSnKTe4SA8HAU'],
    'linkedin profile' => ['linkedin', 'linkedin.com/in/helena', 'https://linkedin.com/in/helena'],
    'facebook page' => ['facebook', 'facebook.com/helena.studio', 'https://facebook.com/helena.studio'],
    'the platform host on its own' => ['instagram', 'instagram.com', 'https://instagram.com'],
    'a full address is kept' => ['x', 'https://twitter.com/helena', 'https://twitter.com/helena'],
]);

test('handles still become the platform address', function (string $platform, string $value, string $url) {
    expect(SocialUrlHelper::buildUrl($platform, $value))->toBe($url);
})->with([
    'instagram handle with @' => ['instagram', '@helena', 'https://instagram.com/helena'],
    'instagram handle with a dot' => ['instagram', 'john.doe', 'https://instagram.com/john.doe'],
    'youtube handle' => ['youtube', '@helena', 'https://youtube.com/@helena'],
    'telegram handle' => ['telegram', 'helena', 'https://t.me/helena'],
    'bluesky handle with dots' => ['bluesky', 'helena.bsky.social', 'https://bsky.app/profile/helena.bsky.social'],
    'tumblr blog host' => ['tumblr', 'helena.tumblr.com', 'https://helena.tumblr.com'],
    'mastodon user@instance' => ['mastodon', '@helena@mastodon.art', 'https://mastodon.art/@helena'],
    'reddit subreddit' => ['reddit', 'r/illustration', 'https://reddit.com/r/illustration'],
    'spotify username' => ['spotify', 'helena', 'https://open.spotify.com/user/helena'],
    'website without scheme' => ['website', 'helena.studio/shop', 'https://helena.studio/shop'],
]);

test('e-mail addresses and phone numbers are never read as web addresses', function () {
    expect(SocialUrlHelper::buildUrl('email', 'helena@example.com'))->toBe('mailto:helena@example.com')
        ->and(SocialUrlHelper::buildUrl('whatsapp', '+62 812 3456 789'))->toBe('https://wa.me/628123456789')
        ->and(SocialUrlHelper::buildUrl('phone', '+62 812 3456 789'))->toBe('tel:+628123456789')
        ->and(SocialUrlHelper::looksLikeAddress('email', 'example.com/x'))->toBeFalse();
});
