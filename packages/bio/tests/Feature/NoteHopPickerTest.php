<?php

use App\Models\User;
use App\Support\HopSignature;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Support\NoteHopNotes;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;

// "Choose from NoteHop": the link block lists the member's public NoteHop notes through a server-to-server call
// (GET /api/v1/me/notes, signed as this app for that member) and keeps the picked note's address. NoteHop never
// lists an encrypted note; BioHop checks every address again and never keeps a fragment.

const NOTEHOP_SECRET = 'test-secret-test-secret-test-secret-1234'; // HOP_SSO_SECRET in phpunit.xml

function notehopOn(): void
{
    config(['bio.notehop_picker' => true, 'bio.notehop_url' => 'https://note.test']);
}

function notehopAnswers(array $items, string $next = ''): void
{
    Http::fake(['https://note.test/api/v1/me/notes*' => Http::response(['ok' => true, 'data' => ['items' => $items, 'next' => $next]])]);
}

test('the NoteHop route, button and dialog do not exist while the flag is off', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'nhoff', 'title' => 'T']);
    $this->actingAs($user)->getJson('/bio/media/notehop')->assertNotFound();
    $html = $this->actingAs($user)->get('/bio/nhoff/edit')->assertOk()->getContent();
    expect($html)->not->toContain('Choose from NoteHop')->not->toContain('notehop-picker');
    preg_match('/<script type="application\/json" id="bio-editor-config">(.*?)<\/script>/s', $html, $m);
    expect(json_decode($m[1], true)['notehop'])->toBeNull();
});

test('the editor offers the NoteHop picker when the flag is on, without NoteHop\'s API address', function () {
    notehopOn();
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'nhon', 'title' => 'T']);
    $html = $this->actingAs($user)->get('/bio/nhon/edit')->assertOk()->getContent();
    expect($html)->toContain('Choose from NoteHop')->toContain('notehop-picker')->not->toContain('note.test/api');
    preg_match('/<script type="application\/json" id="bio-editor-config">(.*?)<\/script>/s', $html, $m);
    expect(json_decode($m[1], true)['notehop']['url'])->toBe('http://localhost/bio/media/notehop');
});

test('the proxy lists the member\'s notes through a call signed as this app for that member', function () {
    notehopOn();
    notehopAnswers([
        ['id' => 'menu-warung', 'title' => 'Menu warung', 'url' => 'https://note.test/menu-warung', 'updated' => '2026-11-08T01:00:00Z'],
    ], '41');
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/bio/media/notehop?q=menu&limit=5&cursor=99')
        ->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertExactJson(['items' => [['id' => 'menu-warung', 'title' => 'Menu warung', 'url' => 'https://note.test/menu-warung', 'updated' => '2026-11-08T01:00:00Z']], 'next' => '41']);

    Http::assertSent(function (ClientRequest $request) use ($user) {
        $path = parse_url($request->url(), PHP_URL_PATH).'?'.parse_url($request->url(), PHP_URL_QUERY);
        $sts = HopSignature::stringToSign('biohop', $request->header('X-HOP-Timestamp')[0], $request->header('X-HOP-Nonce')[0], 'GET', $path, '');

        return str_starts_with($request->url(), 'https://note.test/api/v1/me/notes?')
            && $request['q'] === 'menu' && $request['cursor'] === '99' && (int) $request['limit'] === 5
            && $request->header('X-HOP-App')[0] === 'biohop'
            && $request->header('X-HOP-Actor')[0] === (string) $user->id
            && hash_equals(HopSignature::b64(hash_hmac('sha256', $sts, NOTEHOP_SECRET, true)), $request->header('X-HOP-Signature')[0]);
    });
});

test('an address with a key, a fragment, a query or another host is never offered', function () {
    notehopOn();
    $key = str_repeat('A', 43);
    notehopAnswers([
        ['id' => 'ok-note', 'title' => 'Fine', 'url' => 'https://note.test/ok-note'],
        ['id' => 'enc', 'title' => 'Encrypted', 'url' => "https://note.test/enc#{$key}"],
        ['id' => 'frag', 'title' => 'Fragment', 'url' => 'https://note.test/frag#x'],
        ['id' => 'qs', 'title' => 'Query', 'url' => 'https://note.test/qs?k=1'],
        ['id' => 'evil', 'title' => 'Elsewhere', 'url' => 'https://evil.test/evil'],
        ['id' => 'deep', 'title' => 'Deep path', 'url' => 'https://note.test/a/b'],
        ['id' => 'sub', 'title' => 'Look-alike', 'url' => 'https://note.test.evil.test/x1y'],
    ], 'not-a-number');

    $res = $this->actingAs(User::factory()->create())->getJson('/bio/media/notehop')->assertOk();
    expect(collect($res->json('items'))->pluck('id')->all())->toBe(['ok-note'])
        ->and($res->json('next'))->toBe('')
        ->and($res->getContent())->not->toContain('#');
});

test('a member without a NoteHop account is told to sign in there once', function () {
    notehopOn();
    Http::fake(['https://note.test/*' => Http::response(['ok' => false, 'error' => 'no_account', 'message' => 'x'], 404)]);
    $this->actingAs(User::factory()->create())->getJson('/bio/media/notehop')
        ->assertNotFound()->assertJson(['code' => 'no_account', 'message' => __('Sign in to NoteHop once with your HOP ID; your public notes will then show up here.')]);
});

test('NoteHop down, refusing the signature or answering garbage is a polite 503, never a 500', function ($fake) {
    notehopOn();
    Http::fake(['https://note.test/*' => $fake]);
    $this->actingAs(User::factory()->create())->getJson('/bio/media/notehop')
        ->assertStatus(503)->assertHeader('Retry-After', '60')
        ->assertExactJson(['code' => 'unavailable', 'message' => __('NoteHop is not reachable right now. You can still paste a note address.')]);
})->with([
    'connection' => fn () => fn () => throw new ConnectionException('timeout'),
    'bad signature' => fn () => Http::response(['ok' => false, 'error' => 'bad_signature'], 401),
    'server error' => fn () => Http::response('oops', 500),
    'not json' => fn () => Http::response('<html>', 200),
    'redirect' => fn () => Http::response('', 302, ['Location' => 'https://note.test/login']),
]);

test('bad input is a 422 and a guest cannot use the proxy', function () {
    notehopOn();
    notehopAnswers([]);
    $user = User::factory()->create();
    $this->actingAs($user)->getJson('/bio/media/notehop?cursor=abc')->assertStatus(422);
    $this->actingAs($user)->getJson('/bio/media/notehop?limit=500')->assertStatus(422);
    $this->actingAs($user)->getJson('/bio/media/notehop?q[]=x')->assertStatus(422);
    auth()->logout();
    $this->getJson('/bio/media/notehop')->assertUnauthorized();
});

test('isNoteUrl accepts only NoteHop\'s own note paths', function () {
    notehopOn();
    expect(NoteHopNotes::isNoteUrl('https://note.test/abc'))->toBeTrue()
        ->and(NoteHopNotes::isNoteUrl('https://note.test/ab'))->toBeFalse()
        ->and(NoteHopNotes::isNoteUrl('https://note.test/abc#k'))->toBeFalse()
        ->and(NoteHopNotes::isNoteUrl('http://note.test/abc'))->toBeFalse()
        ->and(NoteHopNotes::isNoteUrl('https://note.test@evil.test/abc'))->toBeFalse()
        ->and(NoteHopNotes::isNoteUrl(null))->toBeFalse();
});
