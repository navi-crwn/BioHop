<?php

use App\Models\AbuseReport;
use App\Models\User;
use Hop\Bio\Contracts\ShortlinkCreator;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Hop\Safety\EncryptedNoteLink;
use Hop\Safety\UrlGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

// An encrypted NoteHop link keeps the note key in its #fragment. BioHop stores destinations and redirects to them,
// so it must refuse them (a fake key is used throughout).
const FAKE_NOTE_URL = 'https://note.hel.ink/AbC123xyz0#k=FAKEKEY0notehop0s2audit0000000000000000000';
const FAKE_NOTE_KEY = 'FAKEKEY0notehop0s2audit0000000000000000000';

function notePage(string $slug = 'note-key'): array
{
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => $slug, 'title' => 'T']);

    return [$user, $page];
}

function storedAnywhere(string $needle): array
{
    $hits = [];
    foreach (['bio_links', 'bio_pages', 'abuse_reports', 'sessions', 'bio_revisions'] as $table) {
        if (! Schema::hasTable($table)) {
            continue;
        }
        foreach (DB::table($table)->get() as $row) {
            if (str_contains(json_encode($row), $needle) || str_contains(json_encode($row), rawurlencode($needle))) {
                $hits[] = $table;
            }
        }
    }

    return array_values(array_unique($hits));
}

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
    config(['services.turnstile.secret' => null, 'services.turnstile.site_key' => null]);
});

test('a link block to an encrypted note is refused and the key is not stored', function () {
    [$user, $page] = notePage();
    $this->actingAs($user)->postJson('/bio/note-key/links', ['type' => 'link', 'title' => 'My note', 'url' => FAKE_NOTE_URL])
        ->assertStatus(422)->assertJsonValidationErrors('url');
    expect(storedAnywhere(FAKE_NOTE_KEY))->toBe([]);
});

test('the same address with a full-width host letter is refused too', function () {
    [$user] = notePage('note-key-fw');
    $this->actingAs($user)->postJson('/bio/note-key-fw/links', ['type' => 'link', 'title' => 'x', 'url' => 'https://ｎote.hel.ink/AbC123xyz0#k='.FAKE_NOTE_KEY])
        ->assertStatus(422);
    expect(storedAnywhere(FAKE_NOTE_KEY))->toBe([]);
});

test('a social link, the fallback address and the footer address are refused', function () {
    [$user] = notePage('note-key-page');
    $this->actingAs($user);
    $this->putJson('/bio/note-key-page', ['fallback_url' => FAKE_NOTE_URL])->assertStatus(422);
    $this->putJson('/bio/note-key-page', ['footer_url' => FAKE_NOTE_URL])->assertStatus(422);
    $this->putJson('/bio/note-key-page', ['social_links' => [['platform' => 'website', 'value' => FAKE_NOTE_URL]]])->assertStatus(422);
    expect(storedAnywhere(FAKE_NOTE_KEY))->toBe([]);
});

test('/bio/shorten never hands an encrypted note address to the shortener', function () {
    $spy = new class implements ShortlinkCreator
    {
        public int $calls = 0;

        public function findOrCreate(Authenticatable $user, string $url, ?string $title = null): array
        {
            $this->calls++;

            return ['reused' => false, 'short_url' => 'https://hel.ink/l/x'];
        }

        public function search(Authenticatable $user, string $query = '', int $limit = 10): array
        {
            return [];
        }
    };
    app()->instance(ShortlinkCreator::class, $spy);
    [$user] = notePage('note-key-short');
    $this->actingAs($user)->postJson('/bio/shorten', ['url' => FAKE_NOTE_URL])->assertStatus(422);
    expect($spy->calls)->toBe(0);
});

test('an abuse report that quotes an encrypted note address is stored without the key', function () {
    $this->from('/report-abuse')->post('/report-abuse', [
        'slug' => 'some-page', 'url' => FAKE_NOTE_URL,
        'email' => 'reporter@example.com', 'reason' => 'It links to '.FAKE_NOTE_URL.' which is abusive.',
    ]);
    $r = AbuseReport::first();
    expect(json_encode($r))->not->toContain(FAKE_NOTE_KEY);
    expect(storedAnywhere(FAKE_NOTE_KEY))->toBe([]);
});

test('free text on a page does not carry a note key either', function () {
    [$user] = notePage('note-key-text');
    $this->actingAs($user)->putJson('/bio/note-key-text', ['bio' => 'read it here '.FAKE_NOTE_URL])->assertStatus(422);
    expect(storedAnywhere(FAKE_NOTE_KEY))->toBe([]);
});

test('bio:scrub-note-keys finds and removes keys saved before the rule, and leaves everything else', function () {
    [$user, $page] = notePage('note-key-scrub');
    $withKey = BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'n', 'url' => FAKE_NOTE_URL, 'order' => 0, 'is_active' => true]);
    $other = BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'ok', 'url' => 'https://example.com/#a=1', 'order' => 1, 'is_active' => true]);
    DB::table('bio_pages')->where('id', $page->id)->update(['bio' => 'see '.FAKE_NOTE_URL, 'social_links' => json_encode([['platform' => 'website', 'value' => FAKE_NOTE_URL]])]);
    $escaped = json_encode(['label' => 'x', 'href' => 'https://ｎote.hel.ink/AbC123xyz0#k='.FAKE_NOTE_KEY]);
    DB::table('bio_links')->where('id', $other->id)->update(['settings' => $escaped]);
    DB::table('abuse_reports')->insert(['slug' => 's', 'url' => FAKE_NOTE_URL, 'email' => 'a@example.com', 'reason' => 'quoted '.FAKE_NOTE_URL, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()]);

    $this->artisan('bio:scrub-note-keys')->expectsOutputToContain('dry run, rows with a key: 4')->assertSuccessful();
    expect(storedAnywhere(FAKE_NOTE_KEY))->toContain('bio_links', 'bio_pages', 'abuse_reports');

    $this->artisan('bio:scrub-note-keys', ['--apply' => true])->expectsOutputToContain('scrubbed rows: 4')->assertSuccessful();
    expect(storedAnywhere(FAKE_NOTE_KEY))->toBe([])
        ->and($withKey->fresh()->url)->toBe('https://note.hel.ink/AbC123xyz0')
        ->and($other->fresh()->url)->toBe('https://example.com/#a=1');
    $this->artisan('bio:scrub-note-keys')->expectsOutputToContain('dry run, rows with a key: 0')->assertSuccessful();
});

test('the refusal text exists in all nine languages', function () {
    $key = EncryptedNoteLink::MESSAGE;
    foreach (['en', 'id', 'de', 'es', 'fr', 'ja', 'pt-BR', 'ru', 'zh-CN'] as $locale) {
        $lang = json_decode(file_get_contents(base_path("lang/{$locale}.json")), true);
        expect($lang)->toHaveKey($key);
        if ($locale !== 'en') {
            expect($lang[$key])->not->toBe($key);
        }
    }
});

// The forms a key can take outside a plain address field.
function noteKeyTexts(): array
{
    $key = FAKE_NOTE_KEY;

    return [
        'plain' => 'read '.FAKE_NOTE_URL.' now',
        'bare' => "read note.hel.ink/AbC123xyz0#k={$key} now",
        'label' => "note:note.hel.ink/AbC123xyz0#k={$key}",
        'label with equals' => "note=note.hel.ink/AbC123xyz0#k={$key}",
        'tab in the fragment' => "read https://note.hel.ink/AbC123xyz0#\tk={$key} now",
        'tab in the key' => 'read https://note.hel.ink/AbC123xyz0#k='.substr($key, 0, 9)."\t".substr($key, 9).' now',
        'line break in the fragment' => "read https://note.hel.ink/AbC123xyz0#\nk={$key} now",
        'entity for the hash' => "read https://note.hel.ink/AbC123xyz0&#35;k={$key} now",
        'hex entity for the hash' => "read https://note.hel.ink/AbC123xyz0&#x23;k={$key} now",
        'named entity for the hash' => "read https://note.hel.ink/AbC123xyz0&num;k={$key} now",
        'entity twice for the hash' => "read https://note.hel.ink/AbC123xyz0&amp;#35;k={$key} now",
        'anchor' => '<a href="'.FAKE_NOTE_URL.'">read</a>',
        'anchor in single quotes' => "<a href='".FAKE_NOTE_URL."'>read</a>",
        'anchor without quotes' => '<a href='.FAKE_NOTE_URL.'>read</a>',
        'anchor with an entity' => "<a href=\"https://note.hel.ink/AbC123xyz0&#35;k={$key}\">read</a>",
        'anchor with a tab in the host' => "<a href=\"https://no\tte.hel.ink/AbC123xyz0#k={$key}\">read</a>",
        'escaped anchor' => "&lt;a href=&quot;https://note.hel.ink/AbC123xyz0#k={$key}&quot;&gt;read&lt;/a&gt;",
        'percent-encoded' => "https://example.com/?u=https%3A%2F%2Fnote.hel.ink%2FAbC%23k%3D{$key}",
    ];
}

function noteKeyUrls(): array
{
    $key = FAKE_NOTE_KEY;

    return [
        'plain' => FAKE_NOTE_URL,
        'bare' => "note.hel.ink/AbC123xyz0#k={$key}",
        'one slash' => "https:/note.hel.ink/AbC123xyz0#k={$key}",
        'backslashes' => "https:\\\\note.hel.ink\\AbC123xyz0#k={$key}",
        'owner token' => "https://n.hel.ink/AbC123xyz0#a={$key}",
        'query form of the fragment' => "https://note.hel.ink/AbC123xyz0#?k={$key}",
        'full-width host' => "https://ｎote.hel.ink/AbC123xyz0#k={$key}",
    ];
}

function plainNoteLink(): string
{
    return 'https://note.hel.ink/AbC123xyz0';
}

test('the floating button refuses a key when only its address is saved, as the editor does', function () {
    [$user] = notePage('fabonly');
    BioPage::where('slug', 'fabonly')->update(['fab_type' => 'custom', 'is_published' => true]);
    $this->actingAs($user)->putJson('/bio/fabonly', ['fab_url' => FAKE_NOTE_URL])->assertStatus(422)->assertJsonValidationErrors('fab_url');
    expect(storedAnywhere(FAKE_NOTE_KEY))->toBe([]);
    auth()->logout();
    $this->get('/b/fabonly')->assertDontSee(FAKE_NOTE_KEY, false);
});

test('the floating button refuses a key saved before its type is chosen', function () {
    [$user] = notePage('fabfirst');
    $this->actingAs($user)->putJson('/bio/fabfirst', ['fab_url' => FAKE_NOTE_URL])->assertStatus(422);
    expect(storedAnywhere(FAKE_NOTE_KEY))->toBe([]);
});

test('switching the floating button to custom checks the address already stored', function () {
    [$user, $page] = notePage('fabswitch');
    $page->forceFill(['fab_type' => 'whatsapp', 'fab_url' => 'javascript:alert(1)'])->save();
    $this->actingAs($user)->putJson('/bio/fabswitch', ['fab_type' => 'custom'])->assertStatus(422)->assertJsonValidationErrors('fab_url');
    $page->forceFill(['fab_url' => '+62 812 3456 7890'])->save();
    $this->putJson('/bio/fabswitch', ['fab_type' => 'custom'])->assertOk();
});

test('an unsafe address is refused on a floating button that is already custom', function () {
    [$user, $page] = notePage('fabunsafe');
    $page->forceFill(['fab_type' => 'custom'])->save();
    $this->actingAs($user)->putJson('/bio/fabunsafe', ['fab_url' => 'javascript:alert(1)'])->assertStatus(422)->assertJsonValidationErrors('fab_url');
});

test('the floating button keeps a plain note link, a normal address and a WhatsApp number', function () {
    [$user, $page] = notePage('fabgood');
    $page->forceFill(['fab_type' => 'custom', 'is_published' => true])->save();
    $this->actingAs($user);
    $this->putJson('/bio/fabgood', ['fab_url' => plainNoteLink()])->assertOk();
    expect($page->fresh()->fab_url)->toBe(plainNoteLink());
    $this->putJson('/bio/fabgood', ['fab_url' => 'https://example.com/#k=not-a-note'])->assertOk();
    $this->putJson('/bio/fabgood', ['fab_type' => 'whatsapp', 'fab_url' => '+62 812 3456 7890'])->assertOk();
});

test('html and text blocks refuse a key in any of its forms', function (string $text) {
    [$user] = notePage('htmlkey');
    $this->actingAs($user);
    $this->postJson('/bio/htmlkey/links', ['type' => 'html', 'title' => 'n', 'html_content' => $text])->assertStatus(422)->assertJsonValidationErrors('html_content');
    $this->postJson('/bio/htmlkey/links', ['type' => 'text', 'title' => 'n', 'content' => $text])->assertStatus(422)->assertJsonValidationErrors('content');
    $block = BioLink::create(['bio_page_id' => BioPage::where('slug', 'htmlkey')->value('id'), 'type' => 'html', 'title' => 'x', 'html_content' => '<p>ok</p>', 'order' => 0, 'is_active' => true]);
    $this->putJson("/bio/htmlkey/links/{$block->id}", ['html_content' => $text])->assertStatus(422);
    expect(storedAnywhere(FAKE_NOTE_KEY))->toBe([]);
})->with(fn () => noteKeyTexts());

test('html and text blocks keep ordinary markup and links to notes without a key', function (string $html) {
    [$user, $page] = notePage('htmlgood');
    $this->actingAs($user);
    $this->postJson('/bio/htmlgood/links', ['type' => 'html', 'title' => 'n', 'html_content' => $html])->assertOk();
    $this->postJson('/bio/htmlgood/links', ['type' => 'text', 'title' => 'n', 'content' => $html])->assertOk();
    expect($page->links()->where('type', 'html')->value('html_content'))->toBe($html)
        ->and($page->links()->where('type', 'text')->value('content'))->toBe($html);
})->with([
    'ordinary markup' => '<h3 class="x">Hello &amp; welcome</h3><p>It&#39;s <b>fine</b>, don\'t worry &nbsp;<a href="https://example.com/a?b=1&amp;c=2#top">here</a></p>',
    'plain note link' => '<a href="https://note.hel.ink/AbC123xyz0">read my note</a>',
    'bare note link' => 'read note.hel.ink/AbC123xyz0 now',
    'other fragment on a note' => '<a href="https://note.hel.ink/AbC123xyz0#section">read</a>',
    'empty key parameter' => 'https://note.hel.ink/AbC123xyz0#k=',
    'same fragment on another host' => '<a href="https://example.com/#k=abc&amp;a=def">anchor</a>',
    'hash entity on another host' => 'see https://example.com/page&#35;k=abc',
    'two lines' => "https://note.hel.ink/AbC123xyz0\nhttps://example.com/#k=abc",
]);

test('social links on every platform refuse a key in any of its forms', function (string $url) {
    [$user] = notePage('socialkey');
    $this->actingAs($user);
    foreach (['instagram', 'tiktok', 'website', 'no-such-platform'] as $platform) {
        $this->putJson('/bio/socialkey', ['social_links' => [['platform' => $platform, 'value' => $url, 'enabled' => true]]])
            ->assertStatus(422)->assertJsonValidationErrors('social_links.0.value');
    }
    $this->postJson('/bio/wizard', ['slug' => 'socialwiz', 'title' => 't', 'platforms' => [['platform' => 'instagram', 'value' => $url]]])->assertStatus(422);
    expect(storedAnywhere(FAKE_NOTE_KEY))->toBe([]);
})->with(fn () => noteKeyUrls());

test('social links keep plain handles, addresses and links to notes without a key', function () {
    [$user, $page] = notePage('socialgood');
    $this->actingAs($user)->putJson('/bio/socialgood', ['social_links' => [
        ['platform' => 'instagram', 'value' => 'helena.studio', 'enabled' => true],
        ['platform' => 'tiktok', 'value' => 'https://tiktok.com/@helena#k=not-a-note', 'enabled' => true],
        ['platform' => 'website', 'value' => plainNoteLink(), 'enabled' => true],
    ]])->assertOk();
    expect(collect($page->fresh()->social_links)->pluck('value')->all())->toContain('helena.studio', plainNoteLink());
});

test('the bio refuses a key in any of its forms on save, on the form and in the wizard', function (string $text) {
    [$user] = notePage('biokey');
    $this->actingAs($user);
    $this->putJson('/bio/biokey', ['bio' => $text])->assertStatus(422)->assertJsonValidationErrors('bio');
    $this->postJson('/bio', ['slug' => 'biokeyform', 'title' => 't', 'bio' => $text])->assertStatus(422)->assertJsonValidationErrors('bio');
    $this->postJson('/bio/wizard', ['slug' => 'biokeywiz', 'title' => 't', 'bio' => $text])->assertStatus(422)->assertJsonValidationErrors('bio');
    expect(storedAnywhere(FAKE_NOTE_KEY))->toBe([]);
})->with(fn () => noteKeyTexts());

test('a refused form does not put the key back into the session', function () {
    [$user] = notePage('flashkey');
    $this->actingAs($user)->from('/bio/create')->post('/bio', ['slug' => 'flashnew', 'title' => 't', 'bio' => 'see '.FAKE_NOTE_URL])
        ->assertRedirect('/bio/create')->assertSessionHasErrors('bio');
    expect(json_encode(session()->all()))->not->toContain(FAKE_NOTE_KEY)
        ->and(session()->getOldInput('title'))->toBe('t');
});

test('every other field of a block refuses a key', function (array $body, string $field) {
    [$user] = notePage('blockkey');
    $this->actingAs($user)->postJson('/bio/blockkey/links', $body)->assertStatus(422)->assertJsonValidationErrors($field);
    expect(storedAnywhere(FAKE_NOTE_KEY))->toBe([]);
})->with(function () {
    $text = 'read '.FAKE_NOTE_URL;
    $short = 'n.hel.ink/x#k='.FAKE_NOTE_KEY;

    return [
        'carousel image' => [['type' => 'carousel', 'title' => 'n', 'settings' => ['images' => [['url' => FAKE_NOTE_URL]]]], 'settings.images.0.url'],
        'carousel caption' => [['type' => 'carousel', 'title' => 'n', 'settings' => ['images' => [['url' => 'https://example.com/a.png', 'caption' => $short]]]], 'settings.images.0.caption'],
        'settings text' => [['type' => 'link', 'title' => 'n', 'url' => 'https://example.com/', 'settings' => ['text' => $text]], 'settings.text'],
        'form thank-you' => [['type' => 'form', 'title' => 'n', 'settings' => ['thanks' => $text]], 'settings.thanks'],
        'form consent' => [['type' => 'form', 'title' => 'n', 'settings' => ['consent' => $text]], 'settings.consent'],
        'poll option' => [['type' => 'poll', 'title' => 'n', 'settings' => ['options' => [['text' => $short]]]], 'settings.options.0.text'],
        'faq answer' => [['type' => 'faq', 'title' => 'n', 'faq_items' => [['question' => 'q', 'answer' => $text]]], 'faq_items.0.answer'],
        'faq stored as JSON' => [['type' => 'faq', 'title' => 'n', 'content' => json_encode([['question' => 'q', 'answer' => '<a href="'.FAKE_NOTE_URL.'">x</a>']])], 'content'],
        'map address' => [['type' => 'map', 'title' => 'n', 'map_address' => $text], 'map_address'],
        'countdown label' => [['type' => 'countdown', 'title' => 'n', 'countdown_label' => $short], 'countdown_label'],
        'contact card' => [['type' => 'vcard', 'title' => 'n', 'vcard_company' => $short], 'vcard_company'],
        'title' => [['type' => 'header', 'title' => $short], 'title'],
    ];
});

test('every other field of a page refuses a key', function (array $body, string $field) {
    [$user] = notePage('pagekey');
    $this->actingAs($user)->putJson('/bio/pagekey', $body)->assertStatus(422)->assertJsonValidationErrors($field);
    expect(storedAnywhere(FAKE_NOTE_KEY))->toBe([]);
})->with(function () {
    $text = 'read '.FAKE_NOTE_URL;
    $short = 'n.hel.ink/x#k='.FAKE_NOTE_KEY;

    return [
        'footer note' => [['footer_note' => $text], 'footer_note'],
        'warning reason' => [['warning_reason' => $text, 'content_warning' => 'sensitive'], 'warning_reason'],
        'status' => [['status_text' => 'n.hel.ink/x#k='.substr(FAKE_NOTE_KEY, 0, 30)], 'status_text'],
        'custom css' => [['custom_css' => "a{background:url('".FAKE_NOTE_URL."')}"], 'custom_css'],
        'legacy socials' => [['socials' => [['platform' => 'website', 'value' => FAKE_NOTE_URL]]], 'socials.0.value'],
        'qr settings' => [['qr_settings' => ['url' => FAKE_NOTE_URL, 'logo_url' => $short]], 'qr_settings.url'],
        'deep in qr settings' => [['qr_settings' => ['any' => ['deep' => $text]]], 'qr_settings.any.deep'],
        'a qr setting name' => [['qr_settings' => [FAKE_NOTE_URL => 'x']], 'qr_settings'],
        'seo description' => [['seo_description' => $short], 'seo_description'],
    ];
});

test('qr settings sent as JSON on the classic form refuse a key', function () {
    [$user] = notePage('qrjson');
    $this->actingAs($user)->postJson('/bio', ['slug' => 'qrjsonnew', 'title' => 't', 'qr_settings' => json_encode(['url' => FAKE_NOTE_URL])])
        ->assertStatus(422)->assertJsonValidationErrors('qr_settings');
    expect(storedAnywhere(FAKE_NOTE_KEY))->toBe([]);
});

test('/bio/shorten refuses a key hidden in the title', function () {
    [$user] = notePage('shorttitle');
    $this->actingAs($user)->postJson('/bio/shorten', ['url' => 'https://example.com/', 'title' => 'note:note.hel.ink/x#k='.FAKE_NOTE_KEY])->assertStatus(422);
});

test('the fields that now look for a key still save everyday content', function () {
    [$user, $page] = notePage('everyday');
    $this->actingAs($user);
    $this->putJson('/bio/everyday', [
        'bio' => "Illustrator. Notes: note.hel.ink/AbC123xyz0 #art #k=1 & more: time 12:30\tsharp",
        'footer_note' => 'See '.plainNoteLink(),
        'custom_css' => "a{color:#abc;background:url('https://example.com/a.png#k=1')}",
        'qr_settings' => ['url' => plainNoteLink(), 'color' => '#000000'],
    ])->assertOk();
    expect($page->fresh()->footer_note)->toBe('See '.plainNoteLink());
    $this->postJson('/bio/everyday/links', ['type' => 'link', 'title' => 'My note', 'url' => plainNoteLink(), 'settings' => ['text' => 'Open '.plainNoteLink()]])->assertOk();
});

test('bio:scrub-note-keys removes keys from every column the rule guards', function () {
    [$user, $page] = notePage('scrubcols');
    $text = 'read '.FAKE_NOTE_URL.' now';
    DB::table('bio_pages')->where('id', $page->id)->update([
        'fab_url' => FAKE_NOTE_URL, 'fab_label' => 'n.hel.ink/x#k='.FAKE_NOTE_KEY, 'footer_note' => $text, 'warning_reason' => $text, 'status_text' => $text,
        'socials' => json_encode([['platform' => 'website', 'value' => FAKE_NOTE_URL]]),
        'qr_settings' => json_encode(['url' => FAKE_NOTE_URL, 'nested' => ['a' => $text]]),
        'social_links' => json_encode([['platform' => 'instagram', 'value' => 'note.hel.ink/AbC#k='.FAKE_NOTE_KEY, 'enabled' => true]]),
        'cover_image' => FAKE_NOTE_URL, 'background_image' => FAKE_NOTE_URL, 'background_value' => 'url('.FAKE_NOTE_URL.')', 'og_image' => FAKE_NOTE_URL, 'favicon' => FAKE_NOTE_URL,
    ]);
    $block = BioLink::create(['bio_page_id' => $page->id, 'type' => 'html', 'title' => 'x', 'order' => 0, 'is_active' => true]);
    DB::table('bio_links')->where('id', $block->id)->update([
        'html_content' => '<p>keep</p><a href="'.FAKE_NOTE_URL.'">read</a>', 'map_address' => $text, 'countdown_label' => $text, 'vcard_name' => $text, 'vcard_company' => $text,
        'custom_icon' => FAKE_NOTE_URL, 'faq_items' => json_encode([['question' => 'q', 'answer' => '<a href="'.FAKE_NOTE_URL.'">x</a>']]),
        'content' => json_encode([['question' => 'q', 'answer' => $text]]),
        'settings' => json_encode(['images' => [['url' => FAKE_NOTE_URL, 'caption' => $text]], 'thanks' => $text, 'price' => 1.5]),
    ]);

    $this->artisan('bio:scrub-note-keys', ['--apply' => true])->expectsOutputToContain('scrubbed rows: 2')->assertSuccessful();

    foreach (['bio_pages', 'bio_links'] as $table) {
        expect(json_encode(DB::table($table)->get()))->not->toContain(FAKE_NOTE_KEY)->not->toContain(substr(FAKE_NOTE_KEY, 0, 12));
    }
    expect($block->fresh()->html_content)->toBe('<p>keep</p><a href="https://note.hel.ink/AbC123xyz0">read</a>')
        ->and($block->fresh()->settings['price'])->toBe(1.5)
        ->and($page->fresh()->fab_url)->toBe('https://note.hel.ink/AbC123xyz0');
    $this->artisan('bio:scrub-note-keys')->expectsOutputToContain('dry run, rows with a key: 0')->assertSuccessful();
});

test('bio:scrub-note-keys removes a key in any of its forms from the bio', function (string $text) {
    [, $page] = notePage('scrubforms');
    DB::table('bio_pages')->where('id', $page->id)->update(['bio' => $text]);
    $this->artisan('bio:scrub-note-keys')->expectsOutputToContain('dry run, rows with a key: 1')->assertSuccessful();
    $this->artisan('bio:scrub-note-keys', ['--apply' => true])->expectsOutputToContain('scrubbed rows: 1')->assertSuccessful();
    $bio = (string) $page->fresh()->bio;
    expect($bio)->not->toContain(substr(FAKE_NOTE_KEY, 0, 9))->not->toContain(substr(FAKE_NOTE_KEY, 9));
    $this->artisan('bio:scrub-note-keys')->expectsOutputToContain('dry run, rows with a key: 0')->assertSuccessful();
})->with(fn () => noteKeyTexts());

test('bio:scrub-note-keys keeps what is around a key and leaves content without one alone', function () {
    [, $page] = notePage('scrubkeep');
    $html = '<p>Hi &amp; welcome</p><a href="https://note.hel.ink/AbC123xyz0">note</a> <a href="https://example.com/#k=abc">other</a>';
    DB::table('bio_pages')->where('id', $page->id)->update([
        'bio' => 'before https://note.hel.ink/AbC123xyz0#new=1&k='.FAKE_NOTE_KEY.' after',
        'footer_note' => 'see note.hel.ink/AbC123xyz0 and https://example.com/#a=1',
        // written the way Laravel and the versions write it: rewriting it would count as a change
        'qr_settings' => json_encode(['url' => 'https://example.com/ünï', 'size' => 2.0], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ]);
    $block = BioLink::create(['bio_page_id' => $page->id, 'type' => 'html', 'title' => 'x', 'order' => 0, 'is_active' => true]);
    DB::table('bio_links')->where('id', $block->id)->update(['html_content' => $html]);
    DB::table('bio_revisions')->insert(['bio_page_id' => $page->id, 'blocks_count' => 0, 'snapshot' => json_encode(['page' => ['title' => 'https://example.com/a']], JSON_UNESCAPED_SLASHES), 'created_at' => now()]);
    $revision = DB::table('bio_revisions')->value('snapshot');

    $this->artisan('bio:scrub-note-keys', ['--apply' => true])->expectsOutputToContain('scrubbed rows: 1')->assertSuccessful();

    expect($page->fresh()->bio)->toBe('before https://note.hel.ink/AbC123xyz0#new=1 after')
        ->and($page->fresh()->footer_note)->toBe('see note.hel.ink/AbC123xyz0 and https://example.com/#a=1')
        ->and($block->fresh()->html_content)->toBe($html)
        ->and(DB::table('bio_revisions')->value('snapshot'))->toBe($revision);
});

test('bio:scrub-note-keys counts an owner token in a stored session', function () {
    DB::table('sessions')->insert(['id' => 'scrub-session', 'user_id' => null, 'ip_address' => null, 'user_agent' => null, 'last_activity' => time(),
        'payload' => base64_encode(serialize(['_old_input' => ['url' => 'https://note.hel.ink/AbC#a='.FAKE_NOTE_KEY]]))]);
    $this->artisan('bio:scrub-note-keys')->expectsOutputToContain('counted only (not edited): 1')->assertSuccessful();
});

test('bio:scrub-note-keys removes a key from an attribute with a line break in it', function () {
    [, $page] = notePage('scrubbreak');
    DB::table('bio_pages')->where('id', $page->id)->update(['bio' => '<a href="https://note.hel.ink/AbC'."\n".'#k='.FAKE_NOTE_KEY.'">x</a>']);
    $this->artisan('bio:scrub-note-keys', ['--apply' => true]);
    expect((string) $page->fresh()->bio)->not->toContain(FAKE_NOTE_KEY);
});

test('a Linktree page with a key in its title, bio or a block title is imported without the key', function () {
    app()->bind(UrlGuard::class, fn () => new class extends UrlGuard
    {
        protected function resolveHost(string $host): array
        {
            return ['93.184.216.34'];
        }
    });
    $bare = 'note.hel.ink/AbC#k='.FAKE_NOTE_KEY;
    $html = '<script id="__NEXT_DATA__" type="application/json">'.json_encode(['props' => ['pageProps' => [
        'username' => 'helena', 'pageTitle' => $bare, 'description' => 'read '.FAKE_NOTE_URL.' or note:'.$bare,
        'links' => [
            ['type' => 'CLASSIC', 'title' => 'open '.$bare, 'url' => 'https://example.com/'],
            ['type' => 'CLASSIC', 'title' => 'My note', 'url' => 'https://note.hel.ink/AbC'],
            ['type' => 'CLASSIC', 'title' => 'keyed', 'url' => FAKE_NOTE_URL],
            ['type' => 'HEADER', 'title' => $bare],
        ],
        'socialLinks' => [['type' => 'INSTAGRAM', 'url' => 'https://instagram.com/helena'], ['type' => 'WEBSITE', 'url' => FAKE_NOTE_URL]],
    ]]]).'</script>';
    Http::fake(['linktr.ee/*' => Http::response($html)]);
    $user = User::factory()->create();
    $this->actingAs($user)->post('/bio/import', ['linktree' => 'linktr.ee/helena', 'slug' => 'keyimport'])->assertRedirect();
    expect(storedAnywhere(FAKE_NOTE_KEY))->toBe([]);
    $page = BioPage::where('slug', 'keyimport')->firstOrFail();
    expect($page->bio)->toContain('read https://note.hel.ink/AbC123xyz0')
        ->and($page->links()->pluck('title')->all())->toContain('My note')
        ->and($page->links()->pluck('url')->all())->toContain('https://example.com/', 'https://note.hel.ink/AbC')
        ->and(collect($page->social_links)->pluck('value')->all())->toContain('https://instagram.com/helena');
});
