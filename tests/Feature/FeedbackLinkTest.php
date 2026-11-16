<?php

use App\Models\User;
use App\Support\Feedback;
use Hop\Bio\Models\BioPage;
use Hop\Ui\Locales;

/*
| The Feedback link only opens the HOP ID form in a new tab: {hub}/feedback?app=biohop&from=<screen>&v=<version>.
| This app sends and stores nothing. The screen name is a route name or template, never a slug, id or query.
*/

/** @return list<array{url:string, query:array<string,string>, tab:string, rel:string, text:string}> */
function feedbackLinks(string $html): array
{
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html, LIBXML_NOERROR);
    $out = [];
    foreach ((new DOMXPath($dom))->query('//a[contains(@href, "/feedback?")]') as $a) {
        $url = $a->getAttribute('href');
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $out[] = ['url' => $url, 'query' => $query, 'tab' => $a->getAttribute('target'), 'rel' => $a->getAttribute('rel'), 'text' => trim($a->textContent)];
    }

    return $out;
}

test('every dashboard screen links to the hub form in a new tab with the screen name and the version', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'secretslug', 'title' => 'S']);
    $screens = ['/bio' => 'bio.index', '/bio/create' => 'bio.create', '/bio/manage' => 'bio.manage', '/settings' => 'settings', '/bio/secretslug/edit' => null];
    foreach ($screens as $path => $name) {
        $html = $this->actingAs($user)->get($path)->assertOk()->getContent();
        $links = feedbackLinks($html);
        expect($links)->toHaveCount(1, $path);
        $l = $links[0];
        expect($l['url'])->toStartWith('https://hub.test/feedback?app=biohop&from=')
            ->and($l['tab'])->toBe('_blank')->and($l['rel'])->toBe('noopener')->and($l['text'])->toBe('Feedback')
            ->and($l['query']['app'])->toBe('biohop')
            ->and($l['query']['v'])->toBe(json_decode(file_get_contents(resource_path('changelog.json')), true)['sources']['BioHop'])
            ->and(array_keys($l['query']))->toBe(['app', 'from', 'v']);
        if ($name) {
            expect($l['query']['from'])->toBe($name);
        }
        expect($l['url'])->not->toContain('secretslug')->and($l['query']['from'])->toMatch('~^[A-Za-z0-9._:/{}-]{1,80}$~');
    }
});

test('the public pages with a footer of their own show Feedback next to Help, with the screen name', function () {
    foreach (['/' => 'home', '/help' => 'pages.help', '/faq' => 'pages.faq', '/about' => 'pages.about', '/features' => 'pages.features', '/terms' => 'pages.terms', '/privacy' => 'pages.privacy', '/status' => 'pages.status', '/changelog' => 'changelog'] as $path => $name) {
        $links = feedbackLinks($this->get($path)->assertOk()->getContent());
        $footer = array_values(array_filter($links, fn ($l) => $l['text'] === 'Feedback'));
        expect($footer)->toHaveCount(1, $path)
            ->and($footer[0]['query']['from'])->toBe($name)->and($footer[0]['tab'])->toBe('_blank');
    }
});

test('Help and Contact carry one Send feedback link besides the footer one', function () {
    foreach (['/help', '/contact'] as $path) {
        $links = feedbackLinks($this->get($path)->assertOk()->getContent());
        expect(array_column($links, 'text'))->toContain('Send feedback')->toContain('Feedback')
            ->and(count(array_filter($links, fn ($l) => $l['text'] === 'Send feedback')))->toBe(1);
        foreach ($links as $l) {
            expect($l['tab'])->toBe('_blank')->and($l['rel'])->toBe('noopener');
        }
    }
});

test('a bio page of a user and the abuse form get no Feedback link', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'someone', 'title' => 'S', 'is_enabled' => true]);
    expect($this->get('/b/someone')->getContent())->not->toContain('/feedback?')
        ->and($this->get('/report-abuse')->assertOk()->getContent())->not->toContain('/feedback?');
});

test('without a hub address no page has a Feedback link or the Privacy sentence', function () {
    config(['hop.hub_url' => '']);
    $user = User::factory()->create();
    foreach (['/', '/help', '/contact', '/privacy', '/faq'] as $path) {
        $html = $this->get($path)->assertOk()->getContent();
        expect($html)->not->toContain('/feedback')->and($html)->not->toContain('The Feedback link opens');
    }
    expect($this->actingAs($user)->get('/bio')->getContent())->not->toContain('/feedback');
});

test('the Privacy page says what the Feedback link does, linking the hub policy, without naming hel.ink', function () {
    $html = $this->get('/privacy')->assertOk()->getContent();
    expect($html)->toContain('The Feedback link opens a form on the HOP ID site this app is connected to. This app receives nothing from that form; what the form stores and for how long is described in the <a href="https://hub.test/privacy" rel="noopener">HOP ID privacy policy</a>.');
    config(['hop.hub_url' => 'https://id.example.org/']);
    expect($this->get('/privacy')->getContent())->toContain('<a href="https://id.example.org/privacy" rel="noopener">HOP ID privacy policy</a>');
});

test('Feedback, Send feedback and the Privacy sentence are translated in every language', function (string $code) {
    $d = json_decode(file_get_contents(base_path("lang/{$code}.json")), true);
    $sentence = 'The Feedback link opens a form on the HOP ID site this app is connected to. This app receives nothing from that form; what the form stores and for how long is described in the :link.';
    foreach (['Feedback', 'Send feedback', $sentence, 'HOP ID privacy policy'] as $key) {
        expect($d[$key] ?? '')->not->toBe('');
        if ($code !== 'en' && $key !== 'Feedback') {
            expect($d[$key])->not->toBe($key);
        }
    }
    expect($d[$sentence])->toContain(':link')->not->toContain('hel.ink');
    $this->get('/privacy?lang='.$code)->assertOk()->assertSee(explode(':link', $d[$sentence])[0], false);
    $this->get('/help?lang='.$code)->assertOk()->assertSee('>'.e($d['Send feedback']).'</a>', false);
})->with(fn () => Locales::codes());

test('a hub address that is not http(s) gives no link at all', function () {
    expect(Feedback::url())->toStartWith('https://hub.test/feedback?app=biohop');
    foreach (['', 'hub.test', 'javascript:alert(1)', 'ftp://x.test'] as $bad) {
        config(['hop.hub_url' => $bad]);
        expect(Feedback::url())->toBe('', $bad);
    }
});
