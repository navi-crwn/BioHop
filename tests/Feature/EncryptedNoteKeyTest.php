<?php

use App\Models\AbuseReport;
use App\Models\User;
use Hop\Bio\Contracts\ShortlinkCreator;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Hop\Safety\EncryptedNoteLink;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\DB;
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
