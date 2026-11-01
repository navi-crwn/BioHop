<?php

namespace Hop\Bio\Http\Controllers;

use App\Http\Controllers\Controller;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Models\BioWebhookDelivery;
use Hop\Bio\Support\Webhooks;

class BioWebhookController extends Controller
{
    public function index(BioPage $bioPage)
    {
        $this->authorize('update', $bioPage);

        return response()->json([
            'url' => $bioPage->webhook_url,
            'secret' => $bioPage->webhook_url ? $bioPage->webhook_secret : null,
            'deliveries' => $bioPage->webhookDeliveries()->latest('id')->limit(20)->get()->map(fn ($d) => [
                'id' => $d->id, 'event' => $d->event, 'status' => $d->status, 'attempts' => $d->attempts, 'http_code' => $d->http_code, 'error' => $d->error,
                'next_attempt_at' => $d->next_attempt_at?->toIso8601String(), 'created_at' => $d->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function test(BioPage $bioPage)
    {
        $this->authorize('update', $bioPage);
        abort_unless($bioPage->webhook_url, 422, 'Save a webhook address first.');
        $d = Webhooks::queue($bioPage, 'ping', ['message' => 'A test from BioHop.']);

        return response()->json(['success' => true, 'id' => $d->id]);
    }

    public function resend(BioPage $bioPage, BioWebhookDelivery $delivery)
    {
        $this->authorize('update', $bioPage);
        abort_unless($bioPage->webhook_url, 422, 'Save a webhook address first.');
        $delivery->fill(['status' => 'pending', 'attempts' => 0, 'error' => null, 'next_attempt_at' => null])->save();
        Webhooks::attempt($delivery->fresh());

        return response()->json(['success' => true]);
    }

    public function rotate(BioPage $bioPage)
    {
        $this->authorize('update', $bioPage);
        abort_unless($bioPage->webhook_url, 422, 'Save a webhook address first.');
        $bioPage->forceFill(['webhook_secret' => Webhooks::newSecret()])->save();

        return response()->json(['success' => true, 'secret' => $bioPage->webhook_secret]);
    }
}
