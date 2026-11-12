<?php

use App\Http\Controllers\PageController;
use App\Models\User;
use Hop\Bio\Models\BioPage;
use Hop\Ui\Locales;
use Illuminate\Support\Facades\URL;

// BioHop's own public pages follow the family rule (KONTRAK-SEO-BAHASA, same as the hub): ?lang=<code> is a page in one
// language, the canonical keeps only that, on APP_URL's host; nine alternates plus x-default; the sitemap lists each
// page once with the same alternates; signed-in screens are noindex and not listed.

beforeEach(function () {
    config(['app.url' => 'https://bio.hel.ink']);
    URL::forceRootUrl('https://bio.hel.ink');
});

function seoDom(string $html): DOMXPath
{
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);

    return new DOMXPath($dom);
}

function seoAttr(string $html, string $query, string $attr): array
{
    $out = [];
    foreach (seoDom($html)->query($query) as $node) {
        $out[] = $node->getAttribute($attr);
    }

    return $out;
}

test('the canonical is APP_URL plus the path, keeping only a supported ?lang=', function () {
    $cases = [
        '/' => 'https://bio.hel.ink/',
        '/?lang=de' => 'https://bio.hel.ink/?lang=de',
        '/?lang=de&utm_source=x' => 'https://bio.hel.ink/?lang=de',
        '/?utm_source=x&page=2' => 'https://bio.hel.ink/',
        '/?lang=pt' => 'https://bio.hel.ink/?lang=pt-BR',
        '/?lang=xx' => 'https://bio.hel.ink/',
        '/?lang=ar' => 'https://bio.hel.ink/',
        '/faq?lang=ja&ref=y' => 'https://bio.hel.ink/faq?lang=ja',
        '/changelog' => 'https://bio.hel.ink/changelog',
        // asked on another host name: still the configured address
        'http://other.test/about' => 'https://bio.hel.ink/about',
    ];
    foreach ($cases as $url => $canonical) {
        $html = $this->get($url)->assertOk()->getContent();
        expect(seoAttr($html, '//head/link[@rel="canonical"]', 'href'))->toBe([$canonical], $url);
        expect(seoAttr($html, '//head/meta[@property="og:url"]', 'content'))->toBe([$canonical], $url);
    }
});

test('the home and info pages carry the nine language alternates and x-default', function () {
    foreach (['/' => 'https://bio.hel.ink/', '/features' => 'https://bio.hel.ink/features', '/status' => 'https://bio.hel.ink/status'] as $path => $base) {
        $links = [];
        foreach (seoDom($this->get($path)->assertOk()->getContent())->query('//head/link[@rel="alternate"][@hreflang]') as $link) {
            $links[$link->getAttribute('hreflang')] = $link->getAttribute('href');
        }
        expect(array_keys($links))->toBe([...Locales::codes(), 'x-default']);
        expect($links)->toHaveCount(10)->not->toHaveKey('ar');
        foreach (Locales::codes() as $code) {
            expect($links[$code])->toBe($base.'?lang='.$code);
        }
        expect($links['x-default'])->toBe($base);
    }
});

test('the home page has WebApplication JSON-LD, an absolute PNG share picture and a twitter card', function () {
    $html = $this->get('/')->assertOk()->getContent();
    $xpath = seoDom($html);
    $blocks = $xpath->query('//head/script[@type="application/ld+json"]');
    expect($blocks->length)->toBe(1);
    $data = json_decode($blocks->item(0)->textContent, true, 32, JSON_THROW_ON_ERROR);
    $byType = collect($data['@graph'])->keyBy('@type');
    expect($data['@context'])->toBe('https://schema.org');
    expect($byType['WebApplication'])->toMatchArray(['name' => 'BioHop', 'url' => 'https://bio.hel.ink/', 'inLanguage' => Locales::codes(), 'isAccessibleForFree' => true]);
    expect($byType['WebApplication']['publisher']['@id'])->toBe($byType['Organization']['@id']);
    expect($byType['Organization']['@id'])->toEndWith('/#organization');
    foreach (['/', '/about'] as $path) {
        $page = seoDom($this->get($path)->getContent());
        expect($page->query('//head/meta[@property="og:image"]')->item(0)->getAttribute('content'))->toMatch('#^https?://[^/]+/.+\.png$#');
        expect($page->query('//head/meta[@name="twitter:card"]')->length)->toBe(1);
        expect($page->query('//head/meta[@name="robots"]')->length)->toBe(0);
    }
});

test('signed-in screens are noindex, carry no language alternates and are not in the sitemap', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'seo-own', 'title' => 'Own']);
    foreach (['/bio', '/bio/seo-own/edit', '/settings', '/bio/create'] as $path) {
        $html = $this->actingAs($user)->get($path)->assertOk()->getContent();
        expect(seoAttr($html, '//head/meta[@name="robots"]', 'content'))->toBe(['noindex, nofollow'], $path);
        expect(seoDom($html)->query('//head/link[@hreflang]')->length)->toBe(0, $path);
    }
});

test('the sitemap lists every own public page once with its alternates, then the indexable bio pages', function () {
    config(['hop.public_url' => 'https://hel.ink']);
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'open-page', 'title' => 'Open', 'is_published' => true]);
    BioPage::create(['user_id' => $user->id, 'slug' => 'hidden-page', 'title' => 'Hidden', 'is_published' => true, 'allow_indexing' => false]);
    BioPage::create(['user_id' => $user->id, 'slug' => 'adult-page', 'title' => 'Adult', 'is_published' => true, 'content_warning' => 'adult', 'is_adult_content' => true]);

    $doc = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->getContent());
    $urls = [];
    foreach ($doc->url as $url) {
        $alternates = [];
        foreach ($url->children('http://www.w3.org/1999/xhtml')->link as $link) {
            $alternates[(string) $link->attributes()->hreflang] = (string) $link->attributes()->href;
        }
        $urls[(string) $url->loc] = $alternates;
    }
    expect(count($urls))->toBe(count($doc->url)); // each once

    $own = ['https://bio.hel.ink/', 'https://bio.hel.ink/status', 'https://bio.hel.ink/changelog'];
    foreach (PageController::PAGES as $page) {
        $own[] = 'https://bio.hel.ink/'.$page;
    }
    foreach ($own as $loc) {
        expect($urls)->toHaveKey($loc);
        expect(array_keys($urls[$loc]))->toBe([...Locales::codes(), 'x-default']);
        expect($urls[$loc]['x-default'])->toBe($loc)->and($urls[$loc]['de'])->toBe($loc.'?lang=de');
        $this->get(parse_url($loc, PHP_URL_PATH))->assertOk();
    }
    expect($urls)->toHaveKey('https://hel.ink/b/open-page')
        ->not->toHaveKey('https://hel.ink/b/hidden-page')->not->toHaveKey('https://hel.ink/b/adult-page');
    foreach (array_keys($urls) as $loc) {
        expect($loc)->not->toMatch('#/(bio|settings|dashboard|admin|login)(/|$)#')->not->toContain('?');
    }
});
