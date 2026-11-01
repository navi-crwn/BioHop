<?php

use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Illuminate\Support\Facades\DB;

function editorPage(string $slug = 'editor-save'): array
{
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => $slug, 'title' => 'Before']);

    return [$user, $page];
}

test('a save that sends only one field does not trip over an older, longer value in another field', function () {
    [$user, $page] = editorPage();
    // The database allows 200 characters; the form used to allow more than the server did.
    DB::table('bio_pages')->where('id', $page->id)->update(['seo_title' => str_repeat('legacy ', 12)]);
    $this->actingAs($user);
    $this->putJson('/bio/editor-save', ['title' => 'After'])->assertOk();
    expect($page->fresh()->title)->toBe('After');
    expect($page->fresh()->seo_title)->toBe(str_repeat('legacy ', 12));
});

test('the seo title takes 60 characters and a longer one is refused with the field named', function () {
    [$user] = editorPage('seo-limits');
    $this->actingAs($user);
    $this->putJson('/bio/seo-limits', ['seo_title' => str_repeat('a', 60), 'seo_description' => str_repeat('b', 160)])->assertOk();
    $this->putJson('/bio/seo-limits', ['seo_title' => str_repeat('a', 61)])
        ->assertStatus(422)->assertJsonValidationErrors('seo_title');
    $this->putJson('/bio/seo-limits', ['seo_description' => str_repeat('b', 161)])
        ->assertStatus(422)->assertJsonValidationErrors('seo_description');
});

test('the editor page carries its data as json and loads no third-party script', function () {
    [$user, $page] = editorPage('editor-data');
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'One', 'url' => 'https://example.com/', 'order' => 0, 'is_active' => true]);
    $this->actingAs($user);
    $html = $this->get('/bio/editor-data/edit')->assertOk()->getContent();
    expect($html)->toContain('id="bio-editor-config"')
        ->not->toContain('cdn.jsdelivr.net')->not->toContain('unpkg.com')->not->toContain('cdn.tailwindcss.com');
    preg_match('/<script type="application\/json" id="bio-editor-config">(.*?)<\/script>/s', $html, $m);
    $config = json_decode($m[1], true, flags: JSON_THROW_ON_ERROR);
    expect($config['bioPage']['slug'])->toBe('editor-data')
        ->and($config['blocks'])->toHaveCount(1)
        ->and($config['bioPage'])->not->toHaveKey('links')
        ->and($config['platforms'])->not->toBeEmpty()
        ->and($config['publicBase'])->toEndWith('/b');
});

test('the live preview frame loads its own Alpine, not one from a CDN', function () {
    [$user] = editorPage('preview-self');
    $this->actingAs($user);
    $this->get('/bio/preview-self/preview')->assertOk()->assertDontSee('unpkg.com', false);
});

test('the sidebar lists every public HOP app with its mark, once', function () {
    [$user] = editorPage('sidebar');
    $this->actingAs($user);
    $html = $this->get('/bio')->assertOk()->getContent();
    foreach (['LinkHop', 'PixelHop', 'NoteHop'] as $app) {
        expect(substr_count($html, '<span class="flex-1">'.$app.'</span>'))->toBe(1);
    }
    expect($html)->toContain('Account (HOP ID)')->not->toContain('HOP ID (account)');
    // BioHop is the current app and does not list itself in the family
    expect(substr_count($html, '<span class="flex-1">BioHop</span>'))->toBe(0);
});
