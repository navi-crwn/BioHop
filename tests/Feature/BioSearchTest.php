<?php

use App\Models\AbuseFlag;
use App\Models\User;
use App\Support\BioIndexability;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// Search engines and public bio pages: one canonical address, a quality bar shared by the sitemap and the page's robots
// tag, the click redirect closed to crawlers, and a share picture that always exists.

beforeEach(function () {
    Cache::flush();
    config(['hop.public_url' => 'https://hel.ink', 'app.url' => 'https://bio.hel.ink']);
});

function seoDescription(): string
{
    return 'Handmade ceramics from Bandung: mugs, plates and small vases, shipped across Indonesia every week.';
}

function seoPage(string $slug, int $blocks = 3, array $extra = []): BioPage
{
    $page = BioPage::create($extra + [
        'user_id' => User::factory()->create()->id, 'slug' => $slug, 'title' => ucfirst($slug),
        'seo_description' => seoDescription(), 'is_published' => true, 'allow_indexing' => true,
    ]);
    for ($i = 0; $i < $blocks; $i++) {
        BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Link '.$i, 'url' => 'https://example.com/'.$i, 'order' => $i, 'is_active' => true]);
    }

    return $page;
}

function seoFlag(BioPage $page, string $status): void
{
    AbuseFlag::create(['subject_type' => 'bio_page', 'subject_id' => $page->id, 'score' => 30, 'signals' => ['spam'], 'action' => AbuseFlag::ACTION_FLAGGED, 'status' => $status, 'summary' => 'x']);
}

test('a public page names one canonical address on the public host, the same as og:url', function () {
    seoPage('canon');
    $html = $this->get('/b/canon')->assertOk()->getContent();

    expect($html)->toContain('<link rel="canonical" href="https://hel.ink/b/canon">')
        ->and($html)->toContain('<meta property="og:url" content="https://hel.ink/b/canon">')
        ->and(substr_count($html, 'rel="canonical"'))->toBe(1)
        ->and($html)->not->toContain('hreflang');
});

test('the upper case and the trailing slash address of a page send readers to the canonical path with a 301', function () {
    seoPage('hello');
    $this->get('/b/HELLO')->assertStatus(301)->assertRedirect('/b/hello');
    // the test client trims a trailing slash itself, so these two go through the kernel as a server would see them
    foreach (['/b/hello/' => '/b/hello', '/b/Hello/?src=qr' => '/b/hello?src=qr'] as $from => $to) {
        $res = app()->handle(Request::create($from));
        expect($res->getStatusCode())->toBe(301)->and($res->headers->get('Location'))->toBe($to);
    }
    $this->get('/b/hello')->assertOk();
});

test('a gate that posts to a page is not redirected', function () {
    seoPage('locked', 3, ['password_enabled' => true, 'password' => bcrypt('secret')]);
    $this->post('/b/locked', ['password' => 'wrong'])->assertOk();
});

test('a page that passes the bar is indexable and carries no robots tag', function () {
    seoPage('goodone');
    $html = $this->get('/b/goodone')->assertOk()->getContent();
    expect($html)->not->toContain('name="robots"');
    $this->get('/sitemap.xml')->assertOk()->assertSee('https://hel.ink/b/goodone');
});

test('a thin page (few blocks, short description, or flagged) is out of the sitemap and says noindex, but stays reachable', function () {
    seoPage('fewblocks', 2);
    seoPage('shortdesc', 3, ['seo_description' => 'Too short to say anything.']);
    seoFlag(seoPage('flagged'), AbuseFlag::STATUS_OPEN);
    seoPage('goodtwo');

    $this->get('/sitemap.xml')->assertOk()
        ->assertSee('/b/goodtwo')->assertDontSee('/b/fewblocks')->assertDontSee('/b/shortdesc')->assertDontSee('/b/flagged');
    foreach (['fewblocks', 'shortdesc', 'flagged'] as $slug) {
        expect($this->get('/b/'.$slug)->assertOk()->getContent())->toContain('<meta name="robots" content="noindex">', $slug);
    }
});

test('the bar counts enabled blocks only, uses the bio text when there is no SEO description, and forgives a dismissed finding', function () {
    $page = seoPage('barcheck', 3, ['seo_description' => null, 'bio' => seoDescription()]);
    expect(BioIndexability::passes($page))->toBeTrue();

    $page->links()->first()->update(['is_active' => false]);
    expect(BioIndexability::passes($page))->toBeFalse('a disabled block does not count');
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'divider', 'title' => '', 'order' => 9, 'is_active' => true]);
    expect(BioIndexability::passes($page))->toBeFalse('a divider does not count');
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'More', 'url' => 'https://example.com/more', 'order' => 10, 'is_active' => true]);
    expect(BioIndexability::passes($page))->toBeTrue();

    seoFlag($page, AbuseFlag::STATUS_DISMISSED);
    expect(BioIndexability::passes($page))->toBeTrue();
    expect(BioIndexability::passes(seoPage('sensitive', 3, ['content_warning' => 'sensitive'])))->toBeFalse();
});

test('the edge cache headers of a public page stay as they were', function () {
    seoPage('edgekeep');
    $res = $this->get('/b/edgekeep')->assertOk();
    expect($res->headers->hasCacheControlDirective('public'))->toBeTrue()
        ->and($res->headers->getCacheControlDirective('s-maxage'))->toBe('60')
        ->and($res->headers->getCookies())->toBe([]);
});

test('robots.txt closes the click redirects and the click redirect says noindex', function () {
    $this->get('/robots.txt')->assertOk()->assertSee("Disallow: /b/click/\n", false);

    $link = seoPage('clicky', 1)->links()->first();
    $res = $this->get('/b/click/'.$link->public_id)->assertRedirect('https://example.com/0');
    expect($res->headers->get('X-Robots-Tag'))->toBe('noindex, nofollow');
});

test('a share picture that is gone from storage falls back to the BioHop picture; one that exists is kept', function () {
    Storage::fake('public');
    seoPage('gonepic', 3, ['avatar_url' => 'avatars/missing.png']);
    $this->get('/b/gonepic')->assertOk()->assertSee('property="og:image" content="https://bio.hel.ink/images/brand/og.png"', false);

    Storage::disk('public')->put('avatars/here.png', 'x');
    seoPage('havepic', 3, ['avatar_url' => 'avatars/here.png']);
    $this->get('/b/havepic')->assertOk()->assertSee('property="og:image" content="https://bio.hel.ink/storage/avatars/here.png"', false);

    // the share picture is tried first, and a missing one gives way to the profile picture
    seoPage('sharefirst', 3, ['og_image' => 'bio-images/missing.png', 'avatar_url' => 'avatars/here.png']);
    $this->get('/b/sharefirst')->assertOk()->assertSee('content="https://bio.hel.ink/storage/avatars/here.png"', false);

    // an address on another host cannot be checked and is kept
    seoPage('farpic', 3, ['avatar_url' => 'https://img.example/a.png']);
    $this->get('/b/farpic')->assertOk()->assertSee('property="og:image" content="https://img.example/a.png"', false);
    expect(is_file(public_path('images/brand/og.png')))->toBeTrue();
});

test('a page render asks the abuse table about this page only, not for every finding', function () {
    seoFlag(seoPage('someoneelse'), AbuseFlag::STATUS_OPEN);
    $page = seoPage('lookedat');
    DB::enableQueryLog();
    $this->get('/b/lookedat')->assertOk();
    $flagQueries = collect(DB::getQueryLog())->pluck('query')->filter(fn ($q) => str_contains($q, 'abuse_flags'));
    expect($flagQueries)->toHaveCount(1)->and($flagQueries->first())->toContain('subject_id');
    expect(BioIndexability::isFlagged($page))->toBeFalse();
});

test('thin pages do not use up the places that good pages need in the sitemap', function () {
    config(['hop.sitemap_bio_pages' => 2]);
    foreach (['goodold1', 'goodold2'] as $slug) {
        seoPage($slug)->forceFill(['updated_at' => now()->subDays(5)])->saveQuietly();
    }
    foreach (['thinnew1', 'thinnew2', 'thinnew3'] as $slug) {
        seoPage($slug, 1);
    }
    $this->get('/sitemap.xml')->assertOk()
        ->assertSee('/b/goodold1')->assertSee('/b/goodold2')->assertDontSee('/b/thinnew');
});

test('a HEAD request to a non-canonical address is redirected like a GET', function () {
    seoPage('headpage');
    $this->call('HEAD', '/b/HEADPAGE')->assertStatus(301)->assertRedirect('/b/headpage');
    $res = app()->handle(Request::create('/b/headpage/', 'HEAD'));
    expect($res->getStatusCode())->toBe(301)->and($res->headers->get('Location'))->toBe('/b/headpage');
});

test('the page prints the same description the quality bar measures, also when the SEO description is empty', function () {
    seoPage('blankseo', 3, ['seo_description' => '', 'bio' => seoDescription()]);
    $html = $this->get('/b/blankseo')->assertOk()->getContent();
    expect($html)->toContain('<meta name="description" content="'.seoDescription().'">')
        ->and($html)->toContain('<meta property="og:description" content="'.seoDescription().'">')
        ->and($html)->not->toContain('name="robots"');
    $this->get('/sitemap.xml')->assertSee('/b/blankseo');

    seoPage('nodesc', 3, ['seo_description' => '', 'bio' => 'Short']);
    $html = $this->get('/b/nodesc')->assertOk()->getContent();
    expect($html)->toContain('<meta name="description" content="Short">')->toContain('<meta name="robots" content="noindex">');
    $this->get('/sitemap.xml')->assertDontSee('/b/nodesc');
});
