<?php

use App\Console\Commands\OfficialPage;
use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Illuminate\Support\Facades\Cache;

// Search engines and new bio pages (owner decision, 2026-11-16): a new page is not offered to search engines until its
// owner switches indexing on; pages that exist keep what they have; adult and sensitive pages can never switch it on.

beforeEach(function () {
    Cache::flush();
    config(['hop.public_url' => 'https://hel.ink', 'app.url' => 'https://bio.hel.ink']);
});

function optInWizard(User $user, string $slug, array $extra = []): array
{
    return test()->actingAs($user)->post('/bio/wizard', $extra + [
        'slug' => $slug, 'title' => 'Helena Studio',
        'bio' => 'Handmade ceramics from Bandung: mugs, plates and small vases, shipped across Indonesia every week.',
        'links' => ['https://example.com/one', 'https://example.com/two', 'https://example.com/three'],
    ], ['Accept' => 'application/json'])->assertOk()->json();
}

test('a page made in the wizard starts out of search: noindex on the page, not in the sitemap', function () {
    $user = User::factory()->create();
    optInWizard($user, 'optinwiz');
    $page = BioPage::where('slug', 'optinwiz')->firstOrFail();
    $page->update(['is_published' => true]);

    // everything else about the page passes the quality bar: three blocks and a real description
    expect($page->fresh()->allow_indexing)->toBeFalse();
    $this->get('/b/optinwiz')->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    $this->get('/sitemap.xml')->assertOk()->assertDontSee('optinwiz');
});

test('a page made with the plain form, and a page created in code, start out of search too', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post('/bio', ['slug' => 'plainform', 'title' => 'Plain'])->assertRedirect();
    expect(BioPage::where('slug', 'plainform')->value('allow_indexing'))->toBeFalse();

    // the model says "no" when nobody said anything (the database default does the same on MySQL; SQLite relies on the model)
    expect(BioPage::create(['user_id' => $user->id, 'slug' => 'bymodel', 'title' => 'M'])->fresh()->allow_indexing)->toBeFalse();
    expect((new BioPage)->allow_indexing)->toBeFalse();
});

test('a copy of an indexed page is a new page: it starts out of search and the original keeps its setting', function () {
    $user = User::factory()->create();
    $page = indexablePage($user, 'origin');
    $this->actingAs($user)->post('/bio/origin/duplicate')->assertRedirect();
    expect(BioPage::where('slug', 'origincopy')->value('allow_indexing'))->toBeFalse()
        ->and($page->fresh()->allow_indexing)->toBeTrue();
});

test('after a copy the editor opens with the "not on Google yet" notice, once', function () {
    $user = User::factory()->create();
    indexablePage($user, 'noticecopy');
    $this->actingAs($user)->post('/bio/noticecopy/duplicate')->assertRedirect();
    $this->get('/bio/noticecopycopy/edit')->assertOk()->assertSee('"indexingNotice":true', false);
    $this->get('/bio/noticecopycopy/edit')->assertOk()->assertSee('"indexingNotice":false', false);
});

test('the editor shows indexing off from the start for a locked page, before it takes its first snapshot', function () {
    // An old adult page may still hold "yes". The server stores "no" on the next save and the editor never hears of it,
    // so the editor sets it itself in init(), before the snapshot that a save is compared with.
    $js = file_get_contents(base_path('resources/js/editor.js'));
    $set = strpos($js, 'if (this.indexingLocked()) this.bioPage.allow_indexing = false;');
    $snap = strpos($js, 'this._pageSnap = this.pageSnapshot();');
    expect($set)->not->toBeFalse()->and($snap)->not->toBeFalse()->and($set)->toBeLessThan($snap);

    // and the page data it starts from still says "yes" for such a page: the case the line above exists for
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'oldadultui', 'title' => 'Old', 'allow_indexing' => true, 'content_warning' => 'adult', 'is_adult_content' => true]);
    $this->actingAs($user)->get('/bio/oldadultui/edit')->assertOk()->assertSee('"allow_indexing":true', false);
});

test('pages that exist stay as they are: the new default touches no stored row, and running it again changes nothing', function () {
    $user = User::factory()->create();
    $indexed = indexablePage($user, 'oldyes');
    $hidden = BioPage::create(['user_id' => $user->id, 'slug' => 'oldno', 'title' => 'N', 'is_published' => true, 'allow_indexing' => false]);

    $migration = require base_path('packages/bio/database/migrations/2026_11_16_000001_default_bio_pages_to_not_indexed.php');
    $migration->up();
    $migration->up();

    // and on SQLite it must not rebuild the table: that would delete the page's blocks through the cascading key
    expect($indexed->links()->count())->toBe(3)
        ->and($indexed->fresh()->allow_indexing)->toBeTrue()->and($hidden->fresh()->allow_indexing)->toBeFalse();
    $this->get('/b/oldyes')->assertOk()->assertDontSee('name="robots"', false);
    $this->get('/sitemap.xml')->assertOk()->assertSee('oldyes');
});

test('the owner can switch indexing on, and then a complete page is in the sitemap without a noindex', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'optin', 'title' => 'Opt in', 'is_published' => true,
        'seo_description' => 'A page with enough words in its description to be offered to search engines.']);
    foreach (range(1, 3) as $i) {
        BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'L'.$i, 'url' => 'https://example.com/'.$i, 'order' => $i, 'is_active' => true]);
    }
    $this->get('/sitemap.xml')->assertDontSee('/b/optin');

    $this->actingAs($user)->putJson('/bio/optin', ['allow_indexing' => true])->assertOk();

    expect($page->fresh()->allow_indexing)->toBeTrue();
    Cache::flush();
    $this->get('/b/optin')->assertOk()->assertDontSee('name="robots"', false);
    $this->get('/sitemap.xml')->assertSee('/b/optin');
});

test('adult and sensitive pages cannot switch indexing on: the server refuses and says why', function (string $warning) {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'lockedpg', 'title' => 'L', 'is_published' => true, 'content_warning' => $warning, 'is_adult_content' => $warning === 'adult']);

    $this->actingAs($user)->putJson('/bio/lockedpg', ['allow_indexing' => true])
        ->assertStatus(422)->assertJsonValidationErrors('allow_indexing');
    expect($page->fresh()->allow_indexing)->toBeFalse();
    // the same through the old flag
    $this->putJson('/bio/lockedpg', ['allow_indexing' => true, 'is_adult_content' => true])->assertStatus(422);
    expect($page->fresh()->allow_indexing)->toBeFalse();
})->with(['adult', 'sensitive']);

test('a page that becomes adult in the save that also says yes, or that already said yes before the rule, is stored as no', function () {
    $user = User::factory()->create();
    $page = indexablePage($user, 'turnsadult');
    $this->actingAs($user)->putJson('/bio/turnsadult', ['allow_indexing' => true, 'content_warning' => 'adult'])->assertOk();
    expect($page->fresh()->allow_indexing)->toBeFalse()->and($page->fresh()->is_adult_content)->toBeTrue();

    // an old adult page that still says yes: an ordinary save no longer refuses, and the stored value follows the rule
    $old = BioPage::create(['user_id' => $user->id, 'slug' => 'oldadult', 'title' => 'Old', 'is_published' => true, 'allow_indexing' => true, 'content_warning' => 'adult', 'is_adult_content' => true]);
    $this->putJson('/bio/oldadult', ['title' => 'Old renamed', 'allow_indexing' => true])->assertOk();
    expect($old->fresh()->title)->toBe('Old renamed')->and($old->fresh()->allow_indexing)->toBeFalse();

    // taking the warning off in the same save may switch indexing on
    $this->putJson('/bio/oldadult', ['content_warning' => 'none', 'allow_indexing' => true])->assertOk();
    expect($old->fresh()->allow_indexing)->toBeTrue();
});

test('an adult page stays out of the sitemap, as before', function () {
    $user = User::factory()->create();
    indexablePage($user, 'adultmap', ['content_warning' => 'adult', 'is_adult_content' => true]);
    $this->get('/sitemap.xml')->assertDontSee('adultmap');
});

test('the editor opens with the "not on Google yet" notice once after a page is created, and never otherwise', function () {
    $user = User::factory()->create();
    $res = optInWizard($user, 'noticeone');

    $first = $this->get($res['redirect'])->assertOk()->getContent();
    expect($first)->toContain('"indexingNotice":true')->toContain('Your page is not on Google yet')->toContain('Open SEO settings');
    $this->get($res['redirect'])->assertOk()->assertSee('"indexingNotice":false', false);

    $this->post('/bio', ['slug' => 'noticetwo', 'title' => 'Two'])->assertRedirect();
    $this->get('/bio/noticetwo/edit')->assertSee('"indexingNotice":true', false);
    $this->get('/bio/noticetwo/edit')->assertSee('"indexingNotice":false', false);
});

test('the indexing switch sits in the search group of the settings, with the locked state and its reason', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'switchpg', 'title' => 'S']);
    $html = $this->actingAs($user)->get('/bio/switchpg/edit')->assertOk()->getContent();

    $search = strpos($html, 'data-settings-section="search"');
    $privacy = strpos($html, 'data-settings-section="privacy"');
    $switch = strpos($html, 'data-indexing-switch');
    expect($switch)->toBeGreaterThan($search)->toBeLessThan($privacy)
        ->and($html)->toContain('x-model="bioPage.allow_indexing"')
        ->and(substr_count($html, 'x-model="bioPage.allow_indexing"'))->toBe(1)
        ->and($html)->toContain('is-locked')->toContain('Pages with adult or sensitive content are not offered to search engines.');
});

test('the official page is written as indexed, since it is the one page meant to be found', function () {
    expect(OfficialPage::fields()['allow_indexing'])->toBeTrue();
});

test('the new words exist in all nine languages', function (string $lang) {
    $words = json_decode(file_get_contents(base_path("lang/{$lang}.json")), true);
    foreach (['Search engine indexing', 'Your page is not on Google yet', 'Open SEO settings', 'Got it',
        'Pages with adult or sensitive content are not offered to search engines.',
        'Off for new pages. Turn it on to let Google and other search engines list this page.',
        'A page with fewer than :blocks enabled blocks or a description under :chars characters stays out of search until it is filled in.',
        'New bio pages are not listed by Google by default. To turn it on, go to Settings, then Search & sharing, and switch on "Search engine indexing".'] as $key) {
        expect($words[$key] ?? '')->not->toBe('');
    }
    // the placeholders survive translation
    expect($words['A page with fewer than :blocks enabled blocks or a description under :chars characters stays out of search until it is filled in.'])->toContain(':blocks')->toContain(':chars');
})->with(['en', 'zh-CN', 'de', 'id', 'ja', 'pt-BR', 'es', 'fr', 'ru']);
