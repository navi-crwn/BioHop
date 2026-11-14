<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Hop\Ui\Locales;

/*
| The family colour palette (classic | mist | sand): the cookie hop_palette, applied before first paint for a signed-in member's
| screens only, chosen in the sidebar. The browser run measures the behaviour (cookie, attribute, reload, junk value, contrast);
| this pins the markup, the shared values and the words.
*/

test('a member\'s screens are marked and carry the picker, a guest\'s and a visitor\'s pages are not', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'palpage', 'title' => 'P', 'is_published' => true]);

    $html = $this->actingAs($user)->get('/bio')->assertOk()->getContent();
    expect($html)->toMatch('~<html[^>]*\sdata-member[\s>]~')
        ->and(substr_count($html, 'data-palette-choice='))->toBe(3)
        ->and($html)->toContain('data-palette-choice="classic" aria-pressed="true"')->toContain('data-palette-choice="mist" aria-pressed="false"')->toContain('data-palette-choice="sand" aria-pressed="false"')
        ->and($html)->toContain('Classic')->toContain('Mist')->toContain('Sand');
    // above the footer links, inside the one scroll area of the sidebar
    expect(strpos($html, 'hop-pal-row'))->toBeLessThan(strpos($html, 'hop-side-links'));

    auth()->logout();
    $guest = $this->get('/about')->assertOk()->getContent();
    expect($guest)->not->toMatch('~<html[^>]*\sdata-member[\s>]~')->not->toContain('data-palette-choice');
    // the public bio page never loads the palette script or styles at all
    $this->get('/b/palpage')->assertOk()->assertDontSee('hop_palette')->assertDontSee('data-palette');
});

test('the script writes the cookie like hop_theme, only mist and sand set the attribute, and only on a member\'s screens', function () {
    $js = file_get_contents(base_path('resources/js/hop-prefs.js'));
    expect($js)->toContain("cookie('hop_palette')")->toContain("write('hop_palette', p)")
        ->toContain("c === 'mist' || c === 'sand' ? c : 'classic'")->toContain("hasAttribute('data-member')")->toContain("removeAttribute('data-palette')")
        ->toContain('setPalette: setPalette');
    // the theme keeps its own cookie and the trial depth cookie of the pilot is not in this app
    expect($js)->toContain("write('hop_theme'")->not->toContain('hop_palette_depth');
    expect(file_get_contents(base_path('resources/css/palettes.css')))->not->toContain('palette-depth');
});

test('the palettes carry the frozen family values: surfaces and accents, light and dark', function () {
    $css = file_get_contents(base_path('resources/css/palettes.css'));
    $rgb = fn (string $hex) => implode(' ', array_map('hexdec', str_split(ltrim($hex, '#'), 2)));
    $block = function (string $selector) use ($css) {
        preg_match('/'.preg_quote($selector, '/').' \{(.*?)\n\}/s', $css, $m);

        return $m[1] ?? '';
    };
    $table = [
        ':root[data-palette="mist"]' => ['bg' => '#f4f6fc', 'glass' => '#ffffff', 'panel-2' => '#eef1fa', 'glass-border' => '#dde2f1', 'accent' => '#3f4fc4', 'accent-2' => '#2f74b8'],
        ':root[data-palette="mist"].dark' => ['bg' => '#0d1120', 'glass' => '#141a2e', 'panel-2' => '#1a2138', 'glass-border' => '#262e4a', 'accent' => '#9aaeff', 'accent-2' => '#86c8f3'],
        ':root[data-palette="sand"]' => ['bg' => '#faf6f1', 'glass' => '#ffffff', 'panel-2' => '#f5eee6', 'glass-border' => '#ece2d6', 'accent' => '#a8481f', 'accent-2' => '#a2406f'],
        ':root[data-palette="sand"].dark' => ['bg' => '#17110d', 'glass' => '#1f1814', 'panel-2' => '#271f19', 'glass-border' => '#3a2e25', 'accent' => '#f3b48c', 'accent-2' => '#ea9fb0'],
    ];
    foreach ($table as $selector => $tokens) {
        $body = $block($selector);
        expect($body)->not->toBe('', $selector);
        foreach ($tokens as $token => $hex) {
            expect($body)->toContain("--{$token}: {$rgb($hex)};");
        }
    }
    // the picker looks like the other apps': a small capital heading, three equal boxes in one row
    expect($css)->toContain('text-transform: uppercase')->toContain('grid-template-columns: repeat(3, 1fr)');
    // classic is no attribute: nothing in the file is written for it
    expect($css)->not->toContain('data-palette="classic"');
});

test('classic keeps the backdrop it always had: the glow variables default to the old colours', function () {
    $app = file_get_contents(base_path('resources/css/app.css'));
    expect($app)->toContain('--glow-1: 129 140 248;')->toContain('--glow-2: 125 211 252;')->toContain('--glow-3: 148 163 184;')
        ->toContain('--glow-1: 99 102 241;')->toContain('--glow-2: 14 165 233;')->toContain('--glow-3: 71 85 105;');
});

test('the picker words exist in all nine languages', function () {
    foreach (Locales::codes() as $lang) {
        $d = json_decode(file_get_contents(base_path("lang/{$lang}.json")), true);
        foreach (['Colours', 'Classic', 'Mist', 'Sand'] as $key) {
            expect(isset($d[$key]) && $d[$key] !== '')->toBeTrue("{$lang}: {$key}");
        }
    }
});
