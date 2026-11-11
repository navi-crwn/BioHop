<?php

use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

const APP_YT = 'https://www.youtube.com/watch?v=abc123';

function appBlockPage(string $slug, array $settings, string $title = 'Watch'): array
{
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => $slug, 'title' => 'Apps', 'is_published' => true]);
    $link = BioLink::create(['bio_page_id' => $page->id, 'type' => 'app', 'title' => $title, 'order' => 0, 'is_active' => true, 'settings' => $settings]);

    return [$user, $page, $link];
}

beforeEach(function () {
    config(['services.turnstile.secret' => null, 'services.turnstile.site_key' => null]);
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

test('an app block whose address is a known app carries the app addresses, and its link stays the tracked https one', function () {
    [, , $link] = appBlockPage('appyt', ['web' => APP_YT]);
    $html = $this->get('/b/appyt')->assertOk()->getContent();

    expect($html)->toContain('data-app="YouTube"')->toContain('data-ios="youtube://www.youtube.com/watch?v=abc123"')->toContain('data-android="intent://')->toContain('data-fallback="'.APP_YT.'"')
        // without scripts the button is the same tracked link as before: no scheme address in its href
        ->toContain('href="'.route('bio.public.click', ['bioLink' => $link->public_id]).'"')
        ->not->toContain('href="youtube://')->not->toContain('href="intent://');
    // the stored value is the plain https address
    expect($link->fresh()->settings['web'])->toBe(APP_YT)->and($link->fresh()->appDeepLink()->name)->toBe('YouTube');
});

test('an unknown host, a look-alike host, an http address and a missing address get no app address', function (string $web) {
    appBlockPage('appno', ['web' => $web]);
    $this->get('/b/appno')->assertOk()->assertDontSee('data-app=', false)->assertDontSee('data-ios=', false)->assertDontSee('data-android=', false);
})->with([
    'unknown host' => ['https://example.com/watch?v=abc123'],
    'look-alike suffix' => ['https://www.youtube.com.evil.example/watch?v=abc123'],
    'look-alike prefix' => ['https://notyoutube.com/watch?v=abc123'],
    'userinfo trick' => ['https://www.youtube.com@evil.example/watch?v=abc123'],
    'plain http' => ['http://www.youtube.com/watch?v=abc123'],
    'no address' => [''],
]);

test('a block of another type never gets app attributes', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'applink', 'title' => 'x', 'is_published' => true]);
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'YT', 'url' => APP_YT, 'order' => 0, 'is_active' => true]);
    $this->get('/b/applink')->assertOk()->assertDontSee('data-app=', false);
});

test('the editor refuses a typed scheme and stores the https address as typed', function () {
    [$user] = appBlockPage('appedit', ['web' => 'https://example.com']);
    $this->actingAs($user);
    foreach (['youtube://x', 'intent://x#Intent;scheme=https;end', 'javascript:alert(1)'] as $bad) {
        $this->postJson('/bio/appedit/links', ['type' => 'app', 'title' => 'x', 'settings' => ['web' => $bad]])->assertStatus(422);
        $this->postJson('/bio/appedit/links', ['type' => 'app', 'title' => 'x', 'settings' => ['ios' => $bad, 'web' => 'https://example.com']])->assertStatus(422);
        $this->postJson('/bio/appedit/links', ['type' => 'app', 'title' => 'x', 'settings' => ['android' => $bad, 'web' => 'https://example.com']])->assertStatus(422);
    }
    $id = $this->postJson('/bio/appedit/links', ['type' => 'app', 'title' => 'Yt', 'settings' => ['web' => APP_YT]])->assertOk()->json('block.id');
    expect(BioLink::find($id)->settings['web'])->toBe(APP_YT);
});

test('the editor hint names the app only for a known https address', function () {
    [$user] = appBlockPage('apphint', []);
    $this->actingAs($user);
    $this->postJson('/bio/apphint/links/app-hint', ['url' => APP_YT])->assertOk()->assertJson(['app' => 'YouTube']);
    foreach (['https://example.com/', 'http://www.youtube.com/watch?v=abc123', 'youtube://x', ''] as $url) {
        $this->postJson('/bio/apphint/links/app-hint', ['url' => $url])->assertOk()->assertJson(['app' => null]);
    }
    // another member's page is not theirs to ask about
    $other = User::factory()->create();
    $this->actingAs($other)->postJson('/bio/apphint/links/app-hint', ['url' => APP_YT])->assertForbidden();
});

test('the hint line is translated in all nine languages and the page script is only loaded for an app address', function () {
    $key = 'Opens in the :app app on phones; computers and visitors without scripts get this address.';
    foreach (['en', 'zh-CN', 'de', 'id', 'ja', 'pt-BR', 'es', 'fr', 'ru'] as $lang) {
        $d = json_decode((string) file_get_contents(base_path("lang/$lang.json")), true);
        expect($d)->toHaveKey($key);
        expect($d[$key])->toContain(':app');
    }
    $js = (string) file_get_contents(base_path('resources/js/public.js'));
    expect($js)->toContain("document.querySelector('[data-app]')")->toContain("import('./app-links.js')");
    $links = (string) file_get_contents(base_path('resources/js/app-links.js'));
    expect($links)->not->toContain('http://')->not->toContain('//cdn');
});

test('the app-link script passes its JS tests (iPhone fallback, Android untouched)', function () {
    $node = trim((string) shell_exec('command -v node 2>/dev/null'));
    if ($node === '') {
        $this->markTestSkipped('node is not installed');
    }
    exec(escapeshellarg($node).' --test '.escapeshellarg(base_path('tests/js/app-links.test.mjs')).' 2>&1', $out, $code);
    expect($code)->toBe(0, implode("\n", $out));
});
