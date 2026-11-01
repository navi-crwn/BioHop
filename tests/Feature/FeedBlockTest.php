<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Support\FeedReader;
use Hop\Safety\UrlGuard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    // no real DNS in tests: localhost resolves to a private address, every other host to a public one
    app()->bind(UrlGuard::class, fn () => new class extends UrlGuard
    {
        protected function resolveHost(string $host): array
        {
            return in_array($host, ['localhost', 'internal.example'], true) ? ['127.0.0.1'] : ['93.184.216.34'];
        }
    });
});

const RSS_FIXTURE = '<?xml version="1.0"?><rss version="2.0"><channel><title>Blog</title>'
    .'<item><title>First &amp; best</title><link>https://blog.example/first</link><pubDate>Mon, 26 Oct 2026 10:00:00 GMT</pubDate></item>'
    .'<item><title><![CDATA[<b>Second</b> post]]></title><link>https://blog.example/second</link></item>'
    .'<item><title>Bad link</title><link>javascript:alert(1)</link></item>'
    .'</channel></rss>';

const ATOM_FIXTURE = '<?xml version="1.0" encoding="utf-8"?><feed xmlns="http://www.w3.org/2005/Atom"><entry><title>Atom one</title><link rel="alternate" href="https://atom.example/1"/><published>2026-10-20T08:00:00Z</published></entry></feed>';

test('RSS and Atom feeds become clean items, dropping unsafe links', function () {
    $rss = FeedReader::parse(RSS_FIXTURE);
    expect($rss)->toHaveCount(2)->and($rss[0])->toBe(['title' => 'First & best', 'url' => 'https://blog.example/first', 'date' => '2026-10-26'])
        ->and($rss[1]['title'])->toBe('Second post');
    expect(FeedReader::parse(ATOM_FIXTURE)[0])->toMatchArray(['title' => 'Atom one', 'url' => 'https://atom.example/1', 'date' => '2026-10-20']);
    expect(fn () => FeedReader::parse('not xml at all'))->toThrow(RuntimeException::class);
});

test('a feed block shows the latest posts on the page and the feed is fetched once an hour', function () {
    Http::fake(['blog.example/feed.xml' => Http::response(RSS_FIXTURE)]);
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'feedp', 'title' => 'Feed', 'is_published' => true]);
    $this->actingAs($user)->postJson('/bio/feedp/links', ['type' => 'rss', 'title' => 'Latest posts', 'url' => 'https://blog.example/feed.xml', 'settings' => ['count' => 1]])->assertOk();
    $html = $this->get('/b/feedp')->assertOk()->getContent();
    expect($html)->toContain('First &amp; best')->toContain('href="https://blog.example/first"')->not->toContain('Second post');
    $this->get('/b/feedp');
    Http::assertSentCount(1);
});

test('the feed fetch refuses private addresses and keeps the last good copy when the feed breaks', function () {
    Http::fake(['internal.example/*' => Http::response(RSS_FIXTURE), 'localhost/*' => Http::response(RSS_FIXTURE)]);
    expect(FeedReader::items('http://localhost/feed.xml'))->toBe([])->and(FeedReader::items('http://internal.example/feed.xml'))->toBe([]);
    Http::assertNothingSent();

    Http::fake(['blog.example/feed.xml' => Http::sequence()->push(RSS_FIXTURE)->push('server error', 500)]);
    expect(FeedReader::items('https://blog.example/feed.xml', 5, true))->toHaveCount(2);
    expect(FeedReader::items('https://blog.example/feed.xml', 5, true))->toHaveCount(2);
});

test('a redirect is followed only to a safe address', function () {
    Http::fake([
        'blog.example/old.xml' => Http::response('', 301, ['Location' => 'http://internal.example/feed.xml']),
        'blog.example/moved.xml' => Http::response('', 301, ['Location' => 'https://blog.example/feed.xml']),
        'blog.example/feed.xml' => Http::response(RSS_FIXTURE),
    ]);
    expect(FeedReader::items('https://blog.example/old.xml', 5, true))->toBe([])
        ->and(FeedReader::items('https://blog.example/moved.xml', 5, true))->toHaveCount(2);
});
