<?php

use App\Models\User;
use App\Support\HopSignature;
use Hop\Auth\Ticket;
use Hop\Bio\Models\BioPage;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/*
| OP-03 option B: when someone signs out at the hub (or an admin ends their sessions), the hub
| queues a signed session.revoked to every app, so the sign-out does not depend on the browser
| reaching each app. BioHop signs people in with remember:true (30 days), so deleting the session
| rows is not enough: the remember cookie must stop working too (audit A2-S5-10).
*/
const BIO_REVOKE_SECRET = 'shared-hub-sso-secret-that-is-long-enough-99';

/** A request signed the way the hub's SendHopEvent job signs it (HOP App Protocol hop1), written out here on purpose. */
function bioHubEvent(array $event, ?string $nonce = null)
{
    $body = json_encode($event, JSON_UNESCAPED_SLASHES);
    $ts = (string) time();
    $nonce ??= HopSignature::b64(random_bytes(16));
    $sig = HopSignature::b64(hash_hmac('sha256', implode('|', ['hop1', 'helink', $ts, $nonce, 'POST', '/hop/events', hash('sha256', $body)]), BIO_REVOKE_SECRET, true));

    return test()->call('POST', '/hop/events', [], [], [], [
        'HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json', 'HTTP_X_HOP_APP' => 'helink', 'HTTP_X_HOP_TIMESTAMP' => $ts,
        'HTTP_X_HOP_NONCE' => $nonce, 'HTTP_X_HOP_ACTOR' => 'system', 'HTTP_X_HOP_SIGNATURE' => $sig,
    ], $body);
}

function bioRevoked(int $userId, string $id = 'evt-revoke-1'): array
{
    return ['id' => $id, 'type' => 'session.revoked', 'at' => now()->toIso8601String(), 'data' => ['sub' => (string) $userId, 'hop_id' => (string) $userId]];
}

/** Signs in through the real SSO callback (remember:true) and returns the remember cookie as the browser would keep it. */
function bioSignInKeepRememberCookie(User $user): array
{
    $state = str_repeat('r', 40);
    $payload = ['sub' => (string) $user->id, 'aud' => 'biohop', 'state' => $state, 'iat' => time(), 'exp' => time() + 120, 'jti' => bin2hex(random_bytes(8)), 'status' => $user->status];
    $body = Ticket::b64(json_encode($payload));
    $ticket = $body.'.'.Ticket::b64(hash_hmac('sha256', $body, BIO_REVOKE_SECRET, true));
    $response = test()->withSession(['hop_sso' => ['state' => $state, 'next' => '/bio']])->get('/auth/sso/callback?ticket='.$ticket);
    $response->assertRedirect('/bio');
    $name = 'remember_web_'.sha1(SessionGuard::class);
    $cookie = collect($response->headers->getCookies())->first(fn ($c) => $c->getName() === $name);
    expect($cookie)->not->toBeNull();

    return [$name, $cookie->getValue()];
}

/** A different browser: no session, only the remember cookie it kept. */
function bioBrowserWithOnlyRememberCookie(string $name, string $value)
{
    app('auth')->forgetGuards();
    test()->flushSession();

    return test()->withUnencryptedCookie($name, $value);
}

beforeEach(function () {
    config(['hop-auth.secret' => BIO_REVOKE_SECRET, 'hop-auth.app' => 'biohop']);
    Cache::flush();
});

test('a person who is signed in only by the remember cookie stays signed in after the hub revokes their sessions: the bug', function () {
    $user = User::factory()->create();
    [$name, $value] = bioSignInKeepRememberCookie($user);
    bioBrowserWithOnlyRememberCookie($name, $value)->get('/bio')->assertOk(); // control: the cookie works before the revoke

    bioHubEvent(bioRevoked($user->id))->assertOk();

    // After the fix the cookie is dead: the guest is sent to the hub's sign-in instead of the page.
    bioBrowserWithOnlyRememberCookie($name, $value)->get('/bio')->assertRedirect('/auth/sso?next=%2Fbio');
});

test('session.revoked deletes every session row of that person and nobody else’s', function () {
    config(['session.driver' => 'database']);
    $user = User::factory()->create();
    $other = User::factory()->create();
    foreach ([['a', $user->id], ['b', $user->id], ['c', $other->id], ['d', null]] as [$id, $uid]) {
        DB::table('sessions')->insert(['id' => $id, 'user_id' => $uid, 'ip_address' => '127.0.0.1', 'user_agent' => 't', 'payload' => 'x', 'last_activity' => time()]);
    }

    bioHubEvent(bioRevoked($user->id))->assertOk()->assertJsonPath('data.handled', true);

    // (the event request itself opens a session row of its own: look only at the four seeded ones)
    expect(DB::table('sessions')->pluck('id')->intersect(['a', 'b', 'c', 'd'])->sort()->values()->all())->toBe(['c', 'd']);
});

test('the revoke changes the remember token of that person only', function () {
    $user = User::factory()->create(['remember_token' => 'token-of-the-person']);
    $other = User::factory()->create(['remember_token' => 'token-of-somebody-else']);

    bioHubEvent(bioRevoked($user->id))->assertOk();

    expect($user->fresh()->remember_token)->not->toBe('token-of-the-person')
        ->and($other->fresh()->remember_token)->toBe('token-of-somebody-else');
});

test('an event the hub retries is acknowledged and not run twice, a replayed request is refused, an unsigned one is refused', function () {
    $user = User::factory()->create(['remember_token' => 'first']);
    $event = bioRevoked($user->id, 'evt-retry-1');

    bioHubEvent($event, nonce: 'nonce-number-one-abcdef')->assertOk()->assertJsonPath('data.handled', true);
    $token = $user->fresh()->remember_token;
    bioHubEvent($event, nonce: 'nonce-number-two-abcdef')->assertOk()->assertJsonPath('data.duplicate', true);
    bioHubEvent($event, nonce: 'nonce-number-one-abcdef')->assertStatus(401); // same nonce: replay
    test()->call('POST', '/hop/events', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($event))->assertStatus(401);

    expect($user->fresh()->remember_token)->toBe($token);
});

test('the same event on /api/hop/events is handled the same way', function () {
    $user = User::factory()->create(['remember_token' => 'api-old']);
    $body = json_encode(bioRevoked($user->id, 'evt-api-1'));
    $ts = (string) time();
    $nonce = HopSignature::b64(random_bytes(16));
    $sig = HopSignature::b64(hash_hmac('sha256', implode('|', ['hop1', 'helink', $ts, $nonce, 'POST', '/api/hop/events', hash('sha256', $body)]), BIO_REVOKE_SECRET, true));

    $this->call('POST', '/api/hop/events', [], [], [], [
        'HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json', 'HTTP_X_HOP_APP' => 'helink', 'HTTP_X_HOP_TIMESTAMP' => $ts,
        'HTTP_X_HOP_NONCE' => $nonce, 'HTTP_X_HOP_SIGNATURE' => $sig,
    ], $body)->assertOk();

    expect($user->fresh()->remember_token)->not->toBe('api-old');
});

test('account.deleted still removes the account’s bio pages and now also ends its sessions', function () {
    config(['session.driver' => 'database']);
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'gonepage', 'title' => 'G', 'is_published' => true]);
    DB::table('sessions')->insert(['id' => 'del', 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 't', 'payload' => 'x', 'last_activity' => time()]);

    bioHubEvent(['id' => 'evt-del-1', 'type' => 'account.deleted', 'data' => ['sub' => (string) $user->id, 'hop_id' => (string) $user->id, 'email' => $user->email]])->assertOk();

    expect(BioPage::whereKey($page->id)->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
});

test('account.status suspended keeps the existing suspend behaviour: the next request goes to the hub’s notice', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/bio')->assertOk();
    app('auth')->forgetGuards();

    $user->forceFill(['status' => User::STATUS_SUSPENDED])->save(); // the hub writes this to the shared table
    bioHubEvent(['id' => 'evt-status-1', 'type' => 'account.status', 'data' => ['sub' => (string) $user->id, 'hop_id' => (string) $user->id, 'status' => 'suspended']])
        ->assertOk()->assertJsonPath('data.handled', false);

    $this->actingAs($user->fresh())->get('/bio')->assertRedirect(rtrim(config('hop.hub_url'), '/').'/suspended');
});

test('events this app does not know are acknowledged with 200', function () {
    bioHubEvent(['id' => 'evt-new-1', 'type' => 'something.new', 'data' => []])->assertOk()->assertJsonPath('data.handled', false);
    bioHubEvent(['id' => 'evt-prof-1', 'type' => 'profile.updated', 'data' => ['sub' => '1']])->assertOk();
});
