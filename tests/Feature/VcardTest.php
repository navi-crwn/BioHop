<?php

use App\Models\User;
use App\Support\ReservedSlugs;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Support\SingleLine;
use Illuminate\Support\Facades\Validator;

/*
| The contact block builds a .vcf in the browser from fields a member typed. The builder lives in resources/js/vcard.mjs
| (tests/js/vcard.test.mjs checks it); here: the server refuses line breaks, the pages use the shared module, and the
| texts of the whole block are translated.
*/

test('the vcard fields refuse line breaks and control characters', function () {
    $rule = fn (string $v) => Validator::make(['f' => $v], ['f' => [new SingleLine]])->fails();

    expect($rule("Ann\nNOTE:injected"))->toBeTrue()
        ->and($rule("Ann\r\nEND:VCARD"))->toBeTrue()
        ->and($rule("Ann\u{2028}x"))->toBeTrue()
        ->and($rule("Ann\x00x"))->toBeTrue()
        ->and($rule("Ann\xC3\x28"))->toBeTrue()
        ->and($rule('Ann; Marie, Jr.'))->toBeFalse()
        ->and($rule('日本語 Кириллица'))->toBeFalse();
});

test('saving a contact block with a line break in its name is refused', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'vcard-guard', 'title' => 'Card']);
    $this->actingAs($user);

    $res = $this->postJson('/bio/vcard-guard/links', ['type' => 'vcard', 'title' => 'Save', 'vcard_name' => "Ann\nNOTE:x"]);

    expect($res->status())->toBe(422)->and($res->json('errors'))->toHaveKey('vcard_name');
});

test('updating a contact block with a line break is refused too', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'vcard-upd', 'title' => 'Card']);
    $link = $page->links()->create(['type' => 'vcard', 'title' => 'Save', 'vcard_name' => 'Ann']);
    $this->actingAs($user);

    $res = $this->putJson("/bio/vcard-upd/links/{$link->id}", ['vcard_company' => "Co\nNOTE:x"]);

    expect($res->status())->toBe(422)->and($res->json('errors'))->toHaveKey('vcard_company');
});

test('both pages build the file with the shared module, not with a hand-written template', function () {
    foreach (['show', 'editor/preview'] as $view) {
        $src = (string) file_get_contents(base_path("packages/bio/resources/views/$view.blade.php"));
        expect($src)->not->toContain('BEGIN:VCARD')
            ->and($src)->not->toContain('Save Contact');
    }
    expect((string) file_get_contents(resource_path('js/public.js')))->toContain("from './vcard.mjs'")
        ->and((string) file_get_contents(resource_path('js/preview.js')))->toContain("from './vcard.mjs'");
});

test('the vcard module tests pass under node', function () {
    $node = trim((string) shell_exec('command -v node 2>/dev/null'));
    if ($node === '') {
        $this->markTestSkipped('node is not installed');
    }
    exec(escapeshellarg($node).' --test '.escapeshellarg(base_path('tests/js/vcard.test.mjs')).' 2>&1', $out, $code);

    expect($code)->toBe(0, implode("\n", $out));
});

test('the filehop slug is reserved like the other HOP apps', function () {
    expect(ReservedSlugs::isReserved('filehop'))->toBeTrue()
        ->and(ReservedSlugs::isReserved('FileHop'))->toBeTrue();
});

test('the whatsapp block of the editor is translated in every language', function () {
    $src = (string) file_get_contents(base_path('packages/bio/resources/views/editor/index.blade.php'));
    $start = strpos($src, "editingBlock?.type === 'whatsapp'");
    $block = substr($src, $start, strpos($src, "editingBlock?.type === 'product'") - $start);
    preg_match_all("/__\\('((?:[^'\\\\]|\\\\.)*)'\\)/", $block, $m);
    $keys = array_map(fn ($k) => stripslashes($k), $m[1]);
    $keys[] = 'WhatsApp rotator';
    $keys[] = 'Share chats between several numbers';

    expect(count($keys))->toBeGreaterThan(18);
    foreach (['de', 'es', 'fr', 'id', 'ja', 'pt-BR', 'ru', 'zh-CN'] as $lang) {
        $d = json_decode((string) file_get_contents(base_path("lang/$lang.json")), true);
        foreach ($keys as $k) {
            expect($d)->toHaveKey($k);
            expect($d[$k])->not->toBe('');
        }
    }
});

test('the "Save contact" label exists in every language', function () {
    foreach (['en', 'de', 'es', 'fr', 'id', 'ja', 'pt-BR', 'ru', 'zh-CN'] as $lang) {
        $d = json_decode((string) file_get_contents(base_path("lang/$lang.json")), true);
        expect($d)->toHaveKey('Save contact');
    }
});

test('the hop/safety pin names a released tag that the lock holds', function () {
    // hop/ui and hop/auth follow their branches until they are tagged: tests/Feature/ComposerPinTest.php.
    $json = json_decode((string) file_get_contents(base_path('composer.json')), true);
    $lock = json_decode((string) file_get_contents(base_path('composer.lock')), true);
    $version = fn (string $n) => collect($lock['packages'])->firstWhere('name', $n)['version'];

    expect($json['require']['hop/safety'])->toBe('^0.1.0')->and($version('hop/safety'))->toBe('v0.1.0');
});

test('sync-hop-assets.sh reads the version from the manifest', function () {
    $sh = (string) file_get_contents(base_path('scripts/sync-hop-assets.sh'));
    expect($sh)->toContain('apps.json')->and($sh)->not->toMatch('#hop-assets/v\d#');
});
