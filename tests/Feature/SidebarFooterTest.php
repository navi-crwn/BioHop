<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Hop\Ui\Locales;

/*
| The footer of the sidebar is the same in every HOP dashboard: Help, Feedback, Terms, Privacy, Changelog, GitHub, Announcement,
| one under the other, then "Part of Hop Easy Link". The browser measures (tests/browser notes in the report); this pins the markup.
*/

function sideDom(string $html): DOMXPath
{
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html, LIBXML_NOERROR);

    return new DOMXPath($dom);
}

test('the sidebar footer lists seven links in a fixed order, each to the right page, then the family line', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'sidefoot', 'title' => 'S']);
    foreach ([route('bio.index'), '/bio/sidefoot/edit', route('bio.manage'), route('settings')] as $url) {
        $html = $this->actingAs($user)->get($url)->assertOk()->getContent();
        $x = sideDom($html);
        $links = $x->query('//aside//nav[contains(@class, "hop-side-links")]//li/a');
        expect($links)->toHaveCount(7, $url);
        $got = [];
        foreach ($links as $a) {
            $got[] = [trim($a->textContent), $a->getAttribute('href')];
        }
        expect($got[1][1])->toStartWith('https://hub.test/feedback?app=biohop&from=');
        expect($got)->toBe([
            ['Help', route('pages.help')],
            ['Feedback', $got[1][1] ?? ''],
            ['Terms', route('pages.terms')],
            ['Privacy', route('pages.privacy')],
            ['Changelog', route('changelog')],
            ['GitHub', 'https://github.com/navi-crwn/BioHop'],
            ['Announcement', 'https://hel.ink/announcements'],
        ]);
        // the links scroll with the menu: one scroll area holds the menu, the footer list and the family line, in that order
        $area = $x->query('//aside//div[contains(@class, "overflow-y-auto")]');
        expect($area)->toHaveCount(1);
        expect($x->query('.//nav[contains(@class, "hop-side-links")]', $area->item(0)))->toHaveCount(1)
            ->and($x->query('.//*[contains(@class, "hop-family-side")]', $area->item(0)))->toHaveCount(1);
        // the family line is last, and nothing else sits in the footer
        expect($x->query('//aside//*[contains(@class, "hop-family-side")]/following-sibling::*'))->toHaveCount(0)
            ->and($x->query('//aside//nav[contains(@class, "hop-side-links")]/following-sibling::*[1][contains(@class, "hop-family-side")]'))->toHaveCount(1);
        expect(substr_count($html, 'hop-family-row'))->toBe(substr_count($html, 'class="hop-family-row"'));
    }
});

test('the footer words follow the member\'s language and "Hop Easy Link" is never translated', function () {
    $user = User::factory()->create();
    foreach (Locales::codes() as $lang) {
        $d = json_decode(file_get_contents(base_path("lang/{$lang}.json")), true);
        foreach (['Help', 'Feedback', 'Terms', 'Privacy', 'Changelog', 'Announcement', 'More'] as $key) {
            expect(isset($d[$key]) && $d[$key] !== '')->toBeTrue("{$lang}: {$key}");
        }
        $html = $this->actingAs($user)->get('/bio?lang='.$lang)->assertOk()->getContent();
        foreach (['Help', 'Feedback', 'Terms', 'Privacy', 'Changelog', 'Announcement'] as $key) {
            expect($html)->toContain('>'.e($d[$key]).'</a>');
        }
        expect($html)->toContain('Hop Easy Link');
    }
});

test('without a hub address the sidebar footer stays at six links, with no Feedback', function () {
    config(['hop.hub_url' => '']);
    $user = User::factory()->create();
    $html = $this->actingAs($user)->get(route('bio.index'))->assertOk()->getContent();
    $links = sideDom($html)->query('//aside//nav[contains(@class, "hop-side-links")]//li/a');
    expect($links)->toHaveCount(6);
    $got = [];
    foreach ($links as $a) {
        $got[] = trim($a->textContent);
    }
    expect($got)->toBe(['Help', 'Terms', 'Privacy', 'Changelog', 'GitHub', 'Announcement'])
        ->and($html)->not->toContain('/feedback');
});
