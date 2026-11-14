<?php

use App\Models\User;
use App\Support\HopSignature;
use Hop\Bio\Support\PickerSecret;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;

// HOP_PICKER_SECRET: the picker calls to PixelHop and NoteHop are signed with a secret of their own, so those apps
// never need to hold this app's SSO secret. Without it the SSO secret signs, as before.

const PICKER_SSO_SECRET = 'test-secret-test-secret-test-secret-1234'; // HOP_SSO_SECRET in phpunit.xml

function signedWith(ClientRequest $request, string $secret): bool
{
    $path = parse_url($request->url(), PHP_URL_PATH).'?'.parse_url($request->url(), PHP_URL_QUERY);
    $sts = HopSignature::stringToSign('biohop', $request->header('X-HOP-Timestamp')[0], $request->header('X-HOP-Nonce')[0], 'GET', $path, '');

    return hash_equals(HopSignature::b64(hash_hmac('sha256', $sts, $secret, true)), $request->header('X-HOP-Signature')[0]);
}

function pickersOn(): void
{
    config(['bio.pixelhop_picker' => true, 'bio.pixelhop_url' => 'https://pixel.test', 'bio.pixelhop_hosts' => ['pixel.test']]);
    Http::fake([
        'https://pixel.test/*' => Http::response(['success' => true, 'data' => ['items' => [], 'next' => '', 'total' => 0, 'hotlinks_blocked' => false]]),
    ]);
}

test('without HOP_PICKER_SECRET the picker signs with the SSO secret, as before', function () {
    pickersOn();
    config(['bio.picker_secret' => null]);
    $user = User::factory()->create();
    $this->actingAs($user)->getJson('/bio/media/pixelhop')->assertOk();
    expect(PickerSecret::dedicated())->toBeFalse();
    Http::assertSentCount(1);
    Http::assertSent(fn (ClientRequest $r) => signedWith($r, PICKER_SSO_SECRET));
});

test('with HOP_PICKER_SECRET the picker signs with it and never with the SSO secret', function () {
    pickersOn();
    $picker = str_repeat('p', 40);
    config(['bio.picker_secret' => $picker]);
    $user = User::factory()->create();
    $this->actingAs($user)->getJson('/bio/media/pixelhop')->assertOk();
    expect(PickerSecret::dedicated())->toBeTrue();
    Http::assertSentCount(1);
    Http::assertNotSent(fn (ClientRequest $r) => signedWith($r, PICKER_SSO_SECRET));
    Http::assertSent(fn (ClientRequest $r) => str_starts_with($r->url(), 'https://pixel.test/') && signedWith($r, $picker));
});

test('a HOP_PICKER_SECRET shorter than 32 characters is ignored', function () {
    config(['bio.picker_secret' => 'short']);
    expect(PickerSecret::dedicated())->toBeFalse()->and(PickerSecret::signing())->toBe(PICKER_SSO_SECRET);
});
