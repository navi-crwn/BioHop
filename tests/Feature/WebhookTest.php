<?php

use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Models\BioWebhookDelivery;
use Hop\Bio\Support\Webhooks;
use Hop\Safety\UrlGuard;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    app()->bind(UrlGuard::class, fn () => new class extends UrlGuard
    {
        protected function resolveHost(string $host): array
        {
            return in_array($host, ['localhost', 'internal.example'], true) ? ['127.0.0.1'] : ['93.184.216.34'];
        }
    });
});

/** The first try runs after the response; outside a request, run what was deferred. */
function runDeferred(): void
{
    app(DeferredCallbackCollection::class)->invoke();
}

function hookPage(string $slug, string $url = 'https://hooks.example/bio'): array
{
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => $slug, 'title' => 'Hooks', 'is_published' => true]);
    $page->forceFill(['webhook_url' => $url, 'webhook_secret' => 'whsec_test'])->save();
    $form = BioLink::create(['bio_page_id' => $page->id, 'type' => 'form', 'title' => 'Join', 'order' => 0, 'is_active' => true, 'settings' => ['ask_name' => true, 'ask_email' => true]]);

    return [$user, $page, $form];
}

test('a new form entry is sent signed to the owner and the signature can be checked', function () {
    Http::fake(['hooks.example/*' => Http::response('ok', 200)]);
    [, $page, $form] = hookPage('hookp');
    $this->post(route('bio.public.form', $form->public_id), ['name' => 'Rina', 'email' => 'rina@example.com', 'consent' => '1'])->assertRedirect();
    Http::assertSent(function (HttpRequest $r) {
        $ts = $r->header('X-BioHop-Timestamp')[0];
        $expected = 'sha256='.hash_hmac('sha256', $ts.'.'.$r->body(), 'whsec_test');
        $body = json_decode($r->body(), true);

        return $r->url() === 'https://hooks.example/bio' && $r->header('X-BioHop-Event')[0] === 'form.submitted'
            && hash_equals($expected, $r->header('X-BioHop-Signature')[0]) && $body['entry']['email'] === 'rina@example.com' && $body['form']['title'] === 'Join'
            && $body['id'] === $r->header('X-BioHop-Delivery')[0] && $body['page']['slug'] === 'hookp';
    });
    $d = BioWebhookDelivery::firstOrFail();
    expect($d->status)->toBe('sent')->and($d->http_code)->toBe(200)->and($d->attempts)->toBe(1);
});

test('a failing receiver is tried again after 1, 5 and 30 minutes, then given up on, and can be sent again by hand', function () {
    // four failures, then the receiver is back
    Http::fake(['hooks.example/*' => Http::sequence()->push('down', 500)->push('down', 500)->push('down', 500)->push('down', 500)->push('ok', 200)]);
    [$user, $page] = hookPage('hookr');
    $d = Webhooks::queue($page, 'ping', ['message' => 'x']);
    runDeferred();
    $d->refresh();
    expect($d->status)->toBe('pending')->and($d->attempts)->toBe(1)->and($d->next_attempt_at->diffInMinutes(now(), true))->toBeGreaterThanOrEqual(0)->and($d->error)->toContain('500');
    expect(Webhooks::retryDue())->toBe(0);
    foreach ([2, 6, 31] as $i => $minutes) {
        $this->travel($minutes)->minutes();
        expect(Webhooks::retryDue())->toBe(1);
    }
    $d->refresh();
    expect($d->status)->toBe('failed')->and($d->attempts)->toBe(4)->and($d->next_attempt_at)->toBeNull();
    $this->actingAs($user)->postJson("/bio/hookr/webhook/{$d->id}/resend")->assertOk();
    expect($d->fresh()->status)->toBe('sent');
});

test('only https addresses on public networks are called', function () {
    Http::fake();
    foreach (['https://internal.example/hook', 'https://localhost/hook'] as $url) {
        [, $page] = hookPage('hookb'.mt_rand(100, 999), $url);
        $d = Webhooks::queue($page, 'ping', []);
        runDeferred();
        expect($d->fresh()->status)->toBe('failed')->and($d->fresh()->error)->toBe('Address not allowed');
    }
    Http::assertNothingSent();
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'hookv', 'title' => 'V', 'is_published' => true]);
    $this->actingAs($user)->putJson('/bio/hookv', ['webhook_url' => 'http://insecure.example/x'])->assertStatus(422);
    $this->putJson('/bio/hookv', ['webhook_url' => 'https://localhost/x'])->assertStatus(422);
});

test('the secret is made when the address is saved, stays out of the page data, and only the owner can read or change it', function () {
    Http::fake(['hooks.example/*' => Http::response('ok', 200)]);
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'hooks', 'title' => 'S', 'is_published' => true]);
    $this->actingAs($user)->putJson('/bio/hooks', ['webhook_url' => 'https://hooks.example/bio'])->assertOk()->assertJsonMissing(['webhook_secret']);
    $secret = $page->fresh()->webhook_secret;
    expect($secret)->toStartWith('whsec_');
    expect($this->get('/bio/hooks/edit')->assertOk()->getContent())->not->toContain($secret);
    $this->getJson('/bio/hooks/webhook')->assertOk()->assertJsonPath('secret', $secret);
    $this->postJson('/bio/hooks/webhook/test')->assertOk();
    expect($page->webhookDeliveries()->where('event', 'ping')->count())->toBe(1);
    $new = $this->postJson('/bio/hooks/webhook/secret')->assertOk()->json('secret');
    expect($new)->not->toBe($secret);
    $other = User::factory()->create();
    $this->actingAs($other)->getJson('/bio/hooks/webhook')->assertForbidden();
    $this->postJson('/bio/hooks/webhook/secret')->assertForbidden();
});
