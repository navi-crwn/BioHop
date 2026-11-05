<?php

use App\Models\User;
use Hop\Bio\Jobs\PurgeEdgeCache;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Support\EdgeCache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Js;
use Illuminate\Support\Sleep;

beforeEach(fn () => Cache::flush());

function edgePage(string $slug, array $extra = []): BioPage
{
    return BioPage::create($extra + ['user_id' => User::factory()->create()->id, 'slug' => $slug, 'title' => ucfirst($slug), 'is_published' => true]);
}

function edgeCookieNames($response): array
{
    return array_map(fn ($c) => $c->getName(), $response->headers->getCookies());
}

function cloudflareOn(): void
{
    config(['services.cloudflare.zone_id' => 'zone123', 'services.cloudflare.api_token' => 'token123', 'app.url' => 'https://bio.hel.ink', 'hop.public_url' => 'https://hel.ink']);
}

test('an anonymous visit to a plain published page may be cached at the edge, sets no cookie and is counted by the beacon', function () {
    $page = edgePage('edgeplain');
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Go', 'url' => 'https://example.com/', 'order' => 0, 'is_active' => true]);

    $res = $this->get('/b/edgeplain')->assertOk()->assertHeader('Vary', 'Cookie');
    $cc = $res->headers;
    expect($cc->hasCacheControlDirective('public'))->toBeTrue()
        ->and($cc->getCacheControlDirective('s-maxage'))->toBe('60')
        ->and($cc->getCacheControlDirective('max-age'))->toBe('0')
        ->and($cc->getCacheControlDirective('stale-while-revalidate'))->toBe('300')
        ->and($cc->hasCacheControlDirective('private'))->toBeFalse()
        ->and($cc->hasCacheControlDirective('no-cache'))->toBeFalse();
    expect(edgeCookieNames($res))->toBe([]);
    // the page request itself is not a counted view any more; the beacon in the page is
    expect($page->fresh()->view_count)->toBe(0);
    expect($res->getContent())->toContain(Js::from('/b/edgeplain/view')->toHtml());
    // the shop address of the same page behaves the same
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'product', 'title' => 'Tote', 'url' => 'https://shop.example/t', 'order' => 1, 'is_active' => true, 'settings' => ['price' => 5, 'currency' => 'USD']]);
    expect($this->get('/b/shop/edgeplain')->assertOk()->headers->hasCacheControlDirective('public'))->toBeTrue();
});

test('a visitor with a session cookie, a signed-in person and a chosen language get a private page counted on the server', function () {
    $page = edgePage('edgeprivate');

    $res = $this->withCookie(config('session.cookie'), 'abc')->get('/b/edgeprivate')->assertOk();
    expect($res->headers->hasCacheControlDirective('public'))->toBeFalse()
        ->and($res->headers->hasCacheControlDirective('private'))->toBeTrue()
        ->and($res->getContent())->not->toContain('edgeprivate/view');
    expect($page->fresh()->view_count)->toBe(1);

    $res = $this->actingAs($page->user)->get('/b/edgeprivate')->assertOk();
    expect($res->headers->get('Cache-Control'))->toContain('no-store')->not->toContain('public');

    foreach ([fn () => $this->get('/b/edgeprivate?lang=de'), fn () => $this->withUnencryptedCookie('hop_lang', 'de')->get('/b/edgeprivate')] as $visit) {
        $res = $visit()->assertOk();
        expect($res->headers->hasCacheControlDirective('public'))->toBeFalse('a page in a chosen language is not shared');
        expect($res->getContent())->not->toContain('edgeprivate/view');
    }
});

test('gated, interactive and unpublished pages are never cacheable', function () {
    edgePage('edgepass', ['password_enabled' => true, 'password' => Hash::make('secret')]);
    edgePage('edgeadult', ['content_warning' => 'adult']);
    $poll = edgePage('edgepoll');
    BioLink::create(['bio_page_id' => $poll->id, 'type' => 'poll', 'title' => 'Vote', 'order' => 0, 'is_active' => true, 'settings' => ['options' => [['text' => 'A'], ['text' => 'B']]]]);
    $form = edgePage('edgeform');
    BioLink::create(['bio_page_id' => $form->id, 'type' => 'form', 'title' => 'Join', 'order' => 0, 'is_active' => true, 'settings' => ['ask_email' => true]]);
    edgePage('edgedraft', ['is_published' => false]);

    foreach (['edgepass', 'edgeadult', 'edgepoll', 'edgeform'] as $slug) {
        $res = $this->get("/b/{$slug}")->assertOk();
        expect($res->headers->hasCacheControlDirective('public'))->toBeFalse("{$slug} must stay private")
            ->and($res->headers->hasCacheControlDirective('private'))->toBeTrue()
            ->and($res->headers->has('Vary'))->toBeFalse()
            ->and($res->getContent())->not->toContain("{$slug}\\/view");
    }
    $res = $this->get('/b/edgedraft')->assertNotFound();
    expect($res->headers->hasCacheControlDirective('public'))->toBeFalse();
    // a gated page served from the origin counted the visit itself, so its beacon (never emitted) is ignored
    $this->post('/b/edgepass/view')->assertNoContent();
    expect(BioPage::where('slug', 'edgepass')->value('view_count'))->toBe(0);
});

test('the view beacon counts once a day per visitor, ignores crawlers and privacy mode, and sets no cookie', function () {
    $page = edgePage('edgebeacon');

    $res = $this->post('/b/edgebeacon/view')->assertNoContent()->assertHeader('Cache-Control', 'no-store, private');
    expect(edgeCookieNames($res))->toBe([]);
    expect($page->fresh()->view_count)->toBe(1);
    // the same browser again: still one
    $this->post('/b/edgebeacon/view')->assertNoContent();
    expect($page->fresh()->view_count)->toBe(1);
    // another browser on the same address: a second visitor
    $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1')->post('/b/edgebeacon/view')->assertNoContent();
    expect($page->fresh()->view_count)->toBe(2);
    // the click de-duplication cookie identifies a visitor across addresses
    $this->withCookie('hel_vid', str_repeat('a', 32))->post('/b/edgebeacon/view')->assertNoContent();
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])->withCookie('hel_vid', str_repeat('a', 32))->post('/b/edgebeacon/view')->assertNoContent();
    expect($page->fresh()->view_count)->toBe(3);
    // a crawler is not a visit
    $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)')->post('/b/edgebeacon/view')->assertNoContent();
    expect($page->fresh()->view_count)->toBe(3);

    $quiet = edgePage('edgequiet', ['privacy_mode' => true]);
    $this->post('/b/edgequiet/view')->assertNoContent();
    expect($quiet->fresh()->view_count)->toBe(0);

    edgePage('edgehidden', ['is_published' => false]);
    $this->post('/b/edgehidden/view')->assertNotFound();
    $this->post('/b/nosuchpage/view')->assertNotFound();
});

test('the beacon route needs no CSRF token and is rate limited like clicks', function () {
    $page = edgePage('edgecsrf');
    // the plain test client sends no token; the route must take the request anyway
    $this->withoutExceptionHandling()->post('/b/edgecsrf/view')->assertNoContent();
    $route = app('router')->getRoutes()->getByName('bio.public.view');
    expect($route->gatherMiddleware())->toContain('throttle:bio-click')->toContain('ip.ban')
        ->not->toContain('web');
});

test('saving a page, publishing it or changing a block queues one purge of both addresses', function () {
    cloudflareOn();
    Queue::fake();
    $page = edgePage('edgepurge');
    $link = BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Go', 'url' => 'https://example.com/', 'order' => 0, 'is_active' => true]);
    $live = BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Live', 'url' => 'https://example.com/live', 'order' => 1, 'is_active' => true]);
    Queue::assertNothingPushed();

    $this->actingAs($page->user)->putJson('/bio/edgepurge', ['title' => 'Renamed'])->assertOk();
    Queue::assertPushed(PurgeEdgeCache::class, 1);
    Queue::assertPushed(PurgeEdgeCache::class, fn (PurgeEdgeCache $job) => in_array('https://hel.ink/b/edgepurge', $job->urls, true)
        && in_array('https://bio.hel.ink/b/edgepurge', $job->urls, true)
        && in_array('https://hel.ink/b/shop/edgepurge', $job->urls, true)
        && in_array('https://hel.ink/b/edgepurge?src=qr', $job->urls, true));

    Queue::fake();
    $this->actingAs($page->user)->postJson("/bio/edgepurge/links/{$link->id}/toggle")->assertOk();
    Queue::assertPushed(PurgeEdgeCache::class, 1);

    Queue::fake();
    $this->actingAs($page->user)->putJson('/bio/edgepurge', ['is_published' => false])->assertOk();
    Queue::assertPushed(PurgeEdgeCache::class, 1);

    // a draft that stays a draft has nothing cached
    Queue::fake();
    $this->actingAs($page->user)->putJson('/bio/edgepurge', ['title' => 'Still a draft'])->assertOk();
    Queue::assertNothingPushed();
    $this->actingAs($page->user)->putJson('/bio/edgepurge', ['is_published' => true])->assertOk();
    Queue::assertPushed(PurgeEdgeCache::class, 1);

    // a renamed address is purged under both names
    Queue::fake();
    $this->actingAs($page->user)->putJson('/bio/edgepurge', ['slug' => 'edgemoved'])->assertOk();
    Queue::assertPushed(PurgeEdgeCache::class, fn (PurgeEdgeCache $job) => in_array('https://hel.ink/b/edgepurge', $job->urls, true)
        && in_array('https://hel.ink/b/edgemoved', $job->urls, true));

    // counting views and clicks is not a change
    Queue::fake();
    $this->post('/b/edgemoved/view')->assertNoContent();
    $this->get("/b/click/{$live->public_id}")->assertRedirect('https://example.com/live');
    expect($live->fresh()->click_count)->toBe(1);
    expect(BioPage::where('slug', 'edgemoved')->value('view_count'))->toBe(1);
    Queue::assertNothingPushed();
});

test('without the Cloudflare settings nothing is queued and the headers are still sent', function () {
    Queue::fake();
    $page = edgePage('edgenocf');
    expect(EdgeCache::enabled())->toBeFalse();
    expect($this->get('/b/edgenocf')->assertOk()->headers->hasCacheControlDirective('public'))->toBeTrue();
    $this->actingAs($page->user)->putJson('/bio/edgenocf', ['title' => 'Renamed'])->assertOk();
    Queue::assertNothingPushed();
});

test('the purge job calls Cloudflare once with the addresses', function () {
    cloudflareOn();
    Http::fake(['api.cloudflare.com/*' => Http::response(['success' => true], 200)]);
    (new PurgeEdgeCache(['https://hel.ink/b/one', 'https://bio.hel.ink/b/one']))->handle();
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->url() === 'https://api.cloudflare.com/client/v4/zones/zone123/purge_cache'
        && $request->hasHeader('Authorization', 'Bearer token123')
        && $request['files'] === ['https://hel.ink/b/one', 'https://bio.hel.ink/b/one']);
});

test('a refused purge is logged and nothing more', function () {
    cloudflareOn();
    Sleep::fake();
    Http::fake(['api.cloudflare.com/*' => Http::response(['success' => false, 'errors' => [['code' => 10000, 'message' => 'Authentication error']]], 403)]);
    Log::spy();
    (new PurgeEdgeCache(['https://hel.ink/b/one']))->handle();
    Log::shouldHaveReceived('warning')->once()->withArgs(fn ($message, $context) => $context['status'] === 403 && $context['errors'][0]['code'] === 10000);
});

test('an unreachable Cloudflare is logged and nothing more', function () {
    cloudflareOn();
    Sleep::fake();
    Http::fake(fn () => throw new ConnectionException('timed out'));
    Log::spy();
    (new PurgeEdgeCache(['https://hel.ink/b/one']))->handle();
    Log::shouldHaveReceived('warning')->once();
});

test('the purge job does nothing without the Cloudflare settings', function () {
    Http::fake();
    (new PurgeEdgeCache(['https://hel.ink/b/one']))->handle();
    Http::assertNothingSent();
});
