<?php

use App\Bio\LinkHopShortlinks;
use App\Models\LinkHopToken;
use App\Models\User;
use Hop\Auth\Models\ApiToken;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

test('shortening a block url asks LinkHop with a service token made on first use', function () {
    Http::fake(['https://link.test/api/shorten' => Http::sequence()
        ->push(['short_url' => 'https://hel.ink/l/abc123', 'url' => 'https://example.com/page', 'title' => 'Page'], 201)
        ->push(['short_url' => 'https://hel.ink/l/abc123', 'url' => 'https://example.com/page'], 200)]);
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/bio/shorten', ['url' => 'example.com/page', 'title' => 'Page'])
        ->assertCreated()
        ->assertJson(['short_url' => 'https://hel.ink/l/abc123', 'url' => 'https://example.com/page', 'reused' => false]);

    $token = ApiToken::where('user_id', $user->id)->sole();
    expect($token->name)->toBe(LinkHopShortlinks::TOKEN_NAME)
        ->and($token->can('link:create'))->toBeTrue()
        ->and($token->can('bio:write'))->toBeFalse();
    $plain = LinkHopToken::where('user_id', $user->id)->sole()->token;
    expect(hash('sha256', $plain))->toBe($token->token);
    Http::assertSent(fn ($request) => $request->url() === 'https://link.test/api/shorten'
        && $request->hasHeader('Authorization', 'Bearer '.$plain)
        && $request['url'] === 'https://example.com/page');

    // The second call reuses the token, and LinkHop's 200 means "already had one".
    $this->actingAs($user)->postJson('/bio/shorten', ['url' => 'https://example.com/page'])->assertOk()->assertJson(['reused' => true]);
    expect(ApiToken::where('user_id', $user->id)->count())->toBe(1);
});

test("LinkHop's older answer shape (url = short, target_url = target) is understood too", function () {
    Http::fake(['https://link.test/api/shorten' => Http::response(['success' => true, 'data' => ['url' => 'https://hel.ink/l/zz', 'slug' => 'zz', 'target_url' => 'https://example.com/x']], 201)]);
    $this->actingAs(User::factory()->create())->postJson('/bio/shorten', ['url' => 'https://example.com/x'])
        ->assertCreated()->assertJson(['short_url' => 'https://hel.ink/l/zz', 'url' => 'https://example.com/x', 'id' => 'zz']);
});

test('a token revoked at the hub is replaced once and the call retried, without leaving the old one behind', function () {
    $user = User::factory()->create();
    $mine = ApiToken::generate($user->id, 'My CLI', 100, null, ['link:read']);
    Http::fake(['https://link.test/api/shorten' => Http::sequence()
        ->push(['error' => 'Unauthorized'], 401)
        ->push(['short_url' => 'https://hel.ink/l/new1', 'url' => 'https://example.com/a'], 201)]);
    $this->actingAs($user)->postJson('/bio/shorten', ['url' => 'https://example.com/a'])->assertCreated();
    // The refused BioHop token is gone; the member's own tokens are not touched.
    expect(ApiToken::where('user_id', $user->id)->where('name', LinkHopShortlinks::TOKEN_NAME)->count())->toBe(1)
        ->and(ApiToken::whereKey($mine['model']->id)->exists())->toBeTrue()
        ->and(LinkHopToken::where('user_id', $user->id)->count())->toBe(1);
    Http::assertSentCount(2);
});

test('a token without the link:read scope (403) is replaced once, and LinkHop down stays one token', function () {
    $user = User::factory()->create();
    Http::fake(['https://link.test/api/me/links*' => Http::sequence()
        ->push(['success' => false, 'error' => 'Forbidden', 'message' => 'This token does not have the link:read scope'], 403)
        ->push(['data' => [], 'next' => ''])
        ->push('down', 502)
        ->push('down', 502)]);
    $this->actingAs($user)->getJson('/bio/links/search?query=a')->assertOk()->assertExactJson([]);
    $this->actingAs($user)->getJson('/bio/links/search?query=b')->assertStatus(503);
    $this->actingAs($user)->getJson('/bio/links/search?query=c')->assertStatus(503);
    expect(ApiToken::where('user_id', $user->id)->count())->toBe(1)
        ->and(LinkHopToken::where('user_id', $user->id)->count())->toBe(1);
});

test('a suspended account (403 without a scope) is not answered with a new token every time', function () {
    $user = User::factory()->create();
    Http::fake(['https://link.test/api/me/links*' => Http::response(['success' => false, 'error' => 'Forbidden', 'message' => 'Account suspended'], 403)]);
    $this->actingAs($user)->getJson('/bio/links/search?query=a')->assertStatus(503);
    $this->actingAs($user)->getJson('/bio/links/search?query=a')->assertStatus(503);
    expect(ApiToken::where('user_id', $user->id)->count())->toBe(1);
});

test('when LinkHop is down the editor is told and nothing else breaks', function () {
    Http::fake(['https://link.test/*' => Http::response('oops', 503)]);
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/bio/shorten', ['url' => 'https://example.com/a'])
        ->assertStatus(503)
        ->assertJsonPath('message', fn ($m) => str_contains($m, 'original URL'));
    $this->actingAs($user)->getJson('/bio/links/search?query=ex')
        ->assertStatus(503)
        ->assertExactJson(['message' => __('LinkHop is not reachable right now. You can still paste any URL.')]);
    Http::fake(fn () => throw new ConnectionException('timeout'));
    $this->actingAs($user)->postJson('/bio/shorten', ['url' => 'https://example.com/a'])->assertStatus(503);
});

test('the shortener being down is said politely, without the upstream error text', function () {
    Http::fake(fn () => throw new ConnectionException('cURL error 7: Failed to connect to 10.0.0.5 port 8080'));
    $res = $this->actingAs(User::factory()->create())->postJson('/bio/shorten', ['url' => 'https://example.com/a'])->assertStatus(503);
    expect($res->json('message'))->toBe(__('The shortener could not make a short link right now. Your original URL was kept.'))
        ->not->toContain('10.0.0.5');
});

test('searching my short links goes through LinkHop too', function () {
    Http::fake(['https://link.test/api/me/links*' => Http::response(['data' => [
        ['id' => 1, 'title' => 'Mine', 'url' => 'https://example.com/m', 'short_url' => 'https://hel.ink/l/m1'],
        ['id' => 2, 'url' => 'https://example.com/n'], // no short_url: skipped
    ]])]);
    $this->actingAs(User::factory()->create())->getJson('/bio/links/search?query=m')
        ->assertOk()->assertExactJson([['id' => 1, 'title' => 'Mine', 'url' => 'https://example.com/m', 'short_url' => 'https://hel.ink/l/m1']]);
});

test('the search asks the token route of LinkHop with the words typed and a limit', function () {
    Http::fake(['https://link.test/api/me/links*' => Http::response(['data' => [], 'next' => ''])]);
    $this->actingAs(User::factory()->create())->getJson('/bio/links/search?query=Toko%20Ana')->assertOk();
    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://link.test/api/me/links?')
        && $request['search'] === 'Toko Ana' && (int) $request['limit'] === 20
        && str_starts_with($request->header('Authorization')[0] ?? '', 'Bearer '));
});
