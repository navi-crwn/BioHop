<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Support\LinktreeImport;

test('the sitemap lists no page of a suspended owner (that page answers 404)', function () {
    $ok = User::factory()->create();
    $gone = User::factory()->create(['status' => 'suspended']);
    BioPage::create(['user_id' => $ok->id, 'slug' => 'liveone', 'title' => 'Live', 'is_published' => true, 'allow_indexing' => true]);
    BioPage::create(['user_id' => $gone->id, 'slug' => 'goneone', 'title' => 'Gone', 'is_published' => true, 'allow_indexing' => true]);
    $this->get('/b/goneone')->assertNotFound();
    $this->get('/sitemap.xml')->assertOk()->assertSee('/b/liveone')->assertDontSee('/b/goneone');
});

test('/b and /b/ send visitors to this install, not to a hard-coded host', function () {
    config(['app.url' => 'https://staging.example.test']);
    $this->get('/b?x=1')->assertRedirect('https://staging.example.test/?x=1');
    $this->get('/b/')->assertRedirect('https://staging.example.test/');
});

test('the public not-found page does not reference an asset that does not exist', function () {
    $html = $this->get('/b/nothing-here-at-all')->assertNotFound()->getContent();
    preg_match_all('#(?:src|href)="[^"]*?/(images/[^"?]+)"#', $html, $m);
    foreach (array_unique($m[1]) as $path) {
        expect(file_exists(public_path($path)))->toBeTrue("missing public/$path");
    }
});

test('the /up health page is a plain answer without third-party scripts or fonts', function () {
    $this->get('/up')->assertOk()->assertDontSee('cdn.jsdelivr.net')->assertDontSee('fonts.bunny.net');
});

test('array-valued query parameters are a bad request, never a 500', function (string $url) {
    $user = User::factory()->create();
    expect($this->actingAs($user)->get($url)->getStatusCode())->toBeLessThan(500);
})->with(['/?lang[]=de', '/report-abuse?slug[]=x', '/bio/check-slug?slug[]=x', '/bio/links/search?query[]=x', '/login?next[]=x']);

test('a Linktree page with unexpected data types is read as empty, never a crash, and the import is capped', function (array $props) {
    $html = '<script id="__NEXT_DATA__" type="application/json">'.json_encode(['props' => ['pageProps' => $props]]).'</script>';
    $page = LinktreeImport::parse($html, 'someone');
    expect($page['links'])->toBeArray()->and(count($page['links']))->toBeLessThanOrEqual(200);
})->with([
    'type array' => [['links' => [['type' => ['x'], 'title' => 'a', 'url' => 'https://example.com']]]],
    'title array' => [['pageTitle' => ['x'], 'links' => [['title' => ['x'], 'url' => 'https://example.com']]]],
    'url array' => [['links' => [['title' => 'a', 'url' => ['https://example.com']]]]],
    'links string' => [['links' => 'abc']],
    'socials int' => [['socialLinks' => 5]],
    'children string' => [['links' => [['type' => 'GROUP', 'title' => 'g', 'children' => 'abc']]]],
    'twenty thousand links' => [['links' => array_fill(0, 20000, ['title' => 'a', 'url' => 'https://example.com/'])]],
]);
