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

test('a token revoked at the hub is replaced once and the call retried', function () {
    $user = User::factory()->create();
    Http::fake(['https://link.test/api/shorten' => Http::sequence()
        ->push(['error' => 'Unauthorized'], 401)
        ->push(['short_url' => 'https://hel.ink/l/new1', 'url' => 'https://example.com/a'], 201)]);
    $this->actingAs($user)->postJson('/bio/shorten', ['url' => 'https://example.com/a'])->assertCreated();
    expect(ApiToken::where('user_id', $user->id)->count())->toBe(2)
        ->and(LinkHopToken::where('user_id', $user->id)->count())->toBe(1);
    Http::assertSentCount(2);
});

test('when LinkHop is down the editor is told and nothing else breaks', function () {
    Http::fake(['https://link.test/*' => Http::response('oops', 503)]);
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/bio/shorten', ['url' => 'https://example.com/a'])
        ->assertStatus(503)
        ->assertJsonPath('message', fn ($m) => str_contains($m, 'original URL'));
    $this->actingAs($user)->getJson('/bio/links/search?query=ex')->assertOk()->assertExactJson([]);
    Http::fake(fn () => throw new ConnectionException('timeout'));
    $this->actingAs($user)->postJson('/bio/shorten', ['url' => 'https://example.com/a'])->assertStatus(503);
});

test('searching my short links goes through LinkHop too', function () {
    Http::fake(['https://link.test/api/links*' => Http::response(['data' => [
        ['id' => 1, 'title' => 'Mine', 'url' => 'https://example.com/m', 'short_url' => 'https://hel.ink/l/m1'],
        ['id' => 2, 'url' => 'https://example.com/n'], // no short_url: skipped
    ]])]);
    $this->actingAs(User::factory()->create())->getJson('/bio/links/search?query=m')
        ->assertOk()->assertExactJson([['id' => 1, 'title' => 'Mine', 'url' => 'https://example.com/m', 'short_url' => 'https://hel.ink/l/m1']]);
});
