<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Hop\Ui\Assets;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * The shared HOP dialogs (hop-assets v5 hop-ui.js) replace the browser's confirm/alert/prompt. The script is loaded
 * only when the family manifest lists it; resources/js/dialogs.js falls back to the browser's own until then.
 */
function withUiManifest(?array $ui): void
{
    $bundled = Assets::bundled();
    // The bundled manifest (v9) already lists hop-ui; the test decides what the manifest says about it.
    unset($bundled['assets']['ui_css'], $bundled['assets']['ui_js']);
    if ($ui !== null) {
        $bundled['assets'] += $ui;
    }
    config(['hop-ui.assets.remote' => true]);
    Cache::forget('hop-ui:assets');
    Http::fake([Assets::URL => Http::response($bundled)]);
}

test('the layout loads hop-ui with its SRI hash and the CSP nonce when the manifest lists it', function () {
    withUiManifest([
        'ui_css' => ['url' => 'https://hel.ink/hop-assets/v5/hop-ui.css', 'integrity' => 'sha384-cssHASH'],
        'ui_js' => ['url' => 'https://hel.ink/hop-assets/v5/hop-ui.js', 'integrity' => 'sha384-jsHASH'],
    ]);
    $user = User::factory()->create();
    $res = $this->actingAs($user)->get('/settings')->assertOk();
    $html = $res->getContent();
    expect($html)->toContain('<link rel="stylesheet" href="https://hel.ink/hop-assets/v5/hop-ui.css" integrity="sha384-cssHASH" crossorigin="anonymous">')
        ->toMatch('~<script src="https://hel\.ink/hop-assets/v5/hop-ui\.js" integrity="sha384-jsHASH" crossorigin="anonymous" defer nonce="[^"]+"></script>~');
    $csp = (string) $res->headers->get('Content-Security-Policy');
    expect($csp)->toMatch('~script-src[^;]*https://hel\.ink~')->toMatch('~style-src[^;]*https://hel\.ink~');
    Cache::forget('hop-ui:assets');
});

test('nothing is loaded while the manifest has no hop-ui yet, or lists the script without a hash', function () {
    withUiManifest(null);
    $user = User::factory()->create();
    $this->actingAs($user)->get('/settings')->assertOk()->assertDontSee('hop-ui.js', false)->assertDontSee('hop-ui.css', false);

    withUiManifest([
        'ui_css' => ['url' => 'https://hel.ink/hop-assets/v5/hop-ui.css', 'integrity' => 'sha384-cssHASH'],
        'ui_js' => ['url' => 'https://hel.ink/hop-assets/v5/hop-ui.js', 'integrity' => ''],
    ]);
    $this->get('/settings')->assertOk()->assertDontSee('hop-ui.js', false);
    Cache::forget('hop-ui:assets');
});

test('no page or script calls the browser dialogs directly; they all go through dialogs.js', function () {
    $sources = array_merge(
        glob(base_path('resources/js/*.js')),
        glob(base_path('packages/bio/resources/views/*.blade.php')),
        glob(base_path('packages/bio/resources/views/editor/*.blade.php')),
        glob(base_path('resources/views/*.blade.php')),
        glob(base_path('resources/views/*/*.blade.php')),
    );
    foreach ($sources as $file) {
        if (str_ends_with($file, 'dialogs.js')) {
            continue;
        }
        expect(preg_match('~(?<![.\w])(?:window\.)?(?:confirm|alert|prompt)\(~', (string) file_get_contents($file)))
            ->toBe(0, basename($file).' still calls a browser dialog');
    }
    $js = file_get_contents(base_path('resources/js/dialogs.js'));
    expect($js)->toContain('window.HopUI')->toContain('window.confirm(')->toContain('window.hopDialog');
});

test('delete dialogs name what goes, in the member\'s language', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'dlgpage', 'title' => 'Helena Studio']);
    $id = json_decode(file_get_contents(base_path('lang/id.json')), true);

    $home = $this->actingAs($user)->get('/bio?lang=id')->assertOk()->getContent();
    expect($home)->toContain('data-confirm-title="'.e($id['Delete this page?']).'"')
        ->toContain('data-confirm-subject="Helena Studio"')->toContain('data-confirm-danger');

    $editor = $this->get('/bio/dlgpage/edit?lang=id')->assertOk()->getContent();
    expect($editor)->toContain('"dialogs":{')->toContain(json_encode($id['Delete this block?'], JSON_UNESCAPED_UNICODE))
        ->toContain('askRemoveBlock(idx, $event)')->toContain('bulkDelete($event)')->toContain('deleteArchived(item, $event)')
        ->toContain('confirmDelete($event)')->toContain('hopDialog.confirm(')->toContain('hopDialog.alert(');
    $this->get('/settings?lang=en');
});
