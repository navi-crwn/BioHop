<?php

use App\Models\User;
use Hop\Auth\SilentRecheck;
use Hop\Auth\Ticket;

/*
| The silent account re-check (hop/auth): the test suite turns it off (HOP_RECHECK=false in phpunit.xml) so
| actingAs() stays simple; here it is on. A session the hub has not vouched for lately is confirmed with one
| prompt=none trip, on protected pages and on the public pages that show the account, never on a public bio page.
*/

beforeEach(function () {
    config(['hop-auth.recheck' => ['enabled' => true, 'max_age' => 300, 'cooldown' => 30, 'retry_after' => 300, 'probe' => true, 'probe_timeout' => 1.5]]);
    SilentRecheck::$probe = fn () => true;
});

afterEach(function () {
    SilentRecheck::$probe = null;
});

function recheckTicketFor(User $user, string $state): string
{
    $payload = ['sub' => (string) $user->id, 'aud' => 'biohop', 'state' => $state, 'iat' => time(), 'exp' => time() + 120, 'jti' => bin2hex(random_bytes(8)), 'status' => $user->status];
    $body = Ticket::b64(json_encode($payload));

    return $body.'.'.Ticket::b64(hash_hmac('sha256', $body, config('hop-auth.secret'), true));
}

function recheckState($response): string
{
    parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

    return $query['state'] ?? '';
}

test('a protected page with a stale session asks the hub silently (prompt=none)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/bio');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toStartWith('https://hub.test/sso/biohop?')->toContain('prompt=none');
});

test('the public pages that show the account re-check too, a public bio page never does', function () {
    $user = User::factory()->create();

    foreach (['/', '/faq', '/changelog', '/status'] as $path) {
        $location = $this->actingAs($user)->get($path)->assertRedirect()->headers->get('Location');
        expect($location)->toContain('hub.test')->toContain('prompt=none');
        session()->flush();
    }
    $this->flushSession();
    $this->actingAs($user)->get('/b/does-not-exist')->assertStatus(404);
});

test('a guest passes untouched', function () {
    $this->get('/')->assertOk();
    $this->get('/faq')->assertOk();
});

test('a fresh session and a JSON call do not leave the page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->withSession(['hop_verified_at' => time()])->get('/bio')->assertOk();
    $this->flushSession();
    $this->actingAs($user)->getJson('/bio')->assertStatus(200);
});

test('account A in the app, account B at the hub: the silent answer switches the session to B', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();

    $start = $this->actingAs($a)->get('/bio');
    $state = recheckState($start);
    expect($state)->not->toBe('');

    $this->get('/auth/sso/callback?ticket='.recheckTicketFor($b, $state))->assertRedirect('/bio');
    $this->assertAuthenticatedAs($b);
    expect(session('hop_verified_at'))->toBeGreaterThan(time() - 5);
});

test('login_required from the hub signs the app session out', function () {
    $a = User::factory()->create();
    $start = $this->actingAs($a)->get('/bio');

    $this->get('/auth/sso/callback?error=login_required&state='.recheckState($start))->assertRedirect();
    $this->assertGuest();
});
