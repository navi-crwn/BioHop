<?php

use App\Models\User;
use Hop\Auth\Ticket;

function ssoTicket(User $user, string $state, array $extra = []): string
{
    $payload = json_encode(array_merge([
        'sub' => (string) $user->id, 'email' => $user->email, 'aud' => 'biohop', 'state' => $state,
        'iat' => time(), 'exp' => time() + 120, 'jti' => uniqid(), 'status' => $user->status,
    ], $extra));
    $body = Ticket::b64($payload);

    return $body.'.'.Ticket::b64(hash_hmac('sha256', $body, config('hop-auth.secret'), true));
}

test('guests are sent to the hub to sign in and come back to the page they asked for', function () {
    $this->get('/bio')->assertRedirect('/auth/sso?next=%2Fbio');
    $this->get('/login')->assertRedirect('/auth/sso');
    $response = $this->get('/auth/sso?next=/bio/create');
    $response->assertRedirect();
    expect($response->headers->get('Location'))
        ->toStartWith('https://hub.test/sso/biohop?state=')
        ->toContain('return_to='.rawurlencode('http://localhost/auth/sso/callback'));
    $this->get('/register')->assertRedirect('https://hub.test/register');
});

test('a hub ticket signs the shared user in here', function () {
    $user = User::factory()->create();
    $this->withSession(['hop_sso' => ['state' => 'abc', 'next' => '/bio']])
        ->get('/auth/sso/callback?ticket='.ssoTicket($user, 'abc'))
        ->assertRedirect('/bio');
    $this->assertAuthenticatedAs($user);
    $this->get('/bio')->assertOk();
});

test('a ticket for a suspended account or with the wrong state is refused', function () {
    $user = User::factory()->suspended()->create();
    $this->withSession(['hop_sso' => ['state' => 'abc', 'next' => '/bio']])
        ->get('/auth/sso/callback?ticket='.ssoTicket($user, 'abc'))
        ->assertForbidden();
    $this->assertGuest();
    $active = User::factory()->create();
    $this->withSession(['hop_sso' => ['state' => 'abc', 'next' => '/bio']])
        ->get('/auth/sso/callback?ticket='.ssoTicket($active, 'other'))
        ->assertForbidden();
});

test('signing out ends the session here and continues at the hub', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post('/logout')->assertRedirect('https://hub.test/logout');
    $this->assertGuest();
});

test('a suspended member is signed out on the next request', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/bio')->assertOk();
    $user->forceFill(['status' => User::STATUS_SUSPENDED])->save();
    $this->actingAs($user->fresh())->get('/bio')->assertForbidden();
});

test('there is no local registration or password login', function () {
    $this->post('/login', ['email' => 'a@b.c', 'password' => 'x'])->assertStatus(405);
    $this->post('/register', [])->assertStatus(405);
});
