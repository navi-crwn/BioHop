<?php

namespace Hop\Bio\Support;

use Hop\Bio\Models\BioLead;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Models\BioWebhookDelivery;
use Hop\Safety\UrlGuard;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

use function Illuminate\Support\defer;

/**
 * Sends a page's events (a new form entry, or a test) to the owner's address as signed JSON.
 *
 * Each request carries X-BioHop-Event, X-BioHop-Delivery (a unique id), X-BioHop-Timestamp and
 * X-BioHop-Signature: sha256=HMAC_SHA256(secret, "<timestamp>.<body>"). The receiver recomputes it and rejects old timestamps.
 * A failed try is repeated after 1, 5 and 30 minutes (four tries in all) by the scheduler; the owner can send any of them
 * again by hand. Only https addresses on public networks are called, without following redirects.
 */
class Webhooks
{
    public const BACKOFF_MINUTES = [1, 5, 30];

    public const MAX_ATTEMPTS = 4;

    public const KEEP = 50;

    public static function newSecret(): string
    {
        return 'whsec_'.Str::random(40);
    }

    public static function leadCreated(BioLink $form, BioLead $lead): void
    {
        $page = $form->bioPage;
        if (! $page || ! $page->webhook_url) {
            return;
        }
        self::queue($page, 'form.submitted', [
            'form' => ['id' => $form->public_id, 'title' => $form->title],
            'entry' => ['name' => $lead->name, 'email' => $lead->email, 'phone' => $lead->phone, 'sent_at' => $lead->created_at?->toIso8601String()],
        ]);
    }

    public static function queue(BioPage $page, string $event, array $data): BioWebhookDelivery
    {
        $uuid = (string) Str::uuid();
        $payload = json_encode([
            'id' => $uuid, 'event' => $event, 'created_at' => now()->toIso8601String(),
            'page' => ['slug' => $page->slug, 'title' => $page->title, 'url' => public_url('b/'.$page->slug)],
        ] + $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $delivery = $page->webhookDeliveries()->create(['uuid' => $uuid, 'event' => $event, 'payload' => $payload, 'status' => 'pending']);
        $old = $page->webhookDeliveries()->orderByDesc('id')->skip(self::KEEP)->take(200)->pluck('id');
        if ($old->isNotEmpty()) {
            BioWebhookDelivery::whereIn('id', $old)->delete();
        }
        // the first try runs after the response has gone out, so a slow receiver never slows the visitor
        defer(fn () => self::attempt($delivery));

        return $delivery;
    }

    public static function attempt(BioWebhookDelivery $d): void
    {
        $page = $d->bioPage;
        $d->attempts++;
        $url = (string) $page?->webhook_url;
        try {
            if (! $page || ! preg_match('#^https://#i', $url) || ! app(UrlGuard::class)->isSafe($url)) {
                throw new \RuntimeException('Address not allowed');
            }
            $ts = (string) time();
            $sig = hash_hmac('sha256', $ts.'.'.$d->payload, (string) $page->webhook_secret);
            $res = Http::timeout(5)->connectTimeout(3)->withOptions(['allow_redirects' => false])->withBody($d->payload, 'application/json')
                ->withHeaders(['User-Agent' => 'BioHop-Webhook/1', 'X-BioHop-Event' => $d->event, 'X-BioHop-Delivery' => $d->uuid, 'X-BioHop-Timestamp' => $ts, 'X-BioHop-Signature' => 'sha256='.$sig])
                ->post($url);
            $d->http_code = $res->status();
            if (! $res->successful()) {
                throw new \RuntimeException('The receiver answered '.$res->status());
            }
            $d->fill(['status' => 'sent', 'error' => null, 'next_attempt_at' => null]);
        } catch (\Throwable $e) {
            $blocked = $e->getMessage() === 'Address not allowed';
            $final = $blocked || $d->attempts >= self::MAX_ATTEMPTS;
            $d->fill([
                'status' => $final ? 'failed' : 'pending',
                'error' => Str::limit($blocked ? 'Address not allowed' : ($e instanceof ConnectionException ? 'Could not connect' : $e->getMessage()), 190, ''),
                'next_attempt_at' => $final ? null : now()->addMinutes(self::BACKOFF_MINUTES[$d->attempts - 1] ?? 30),
            ]);
        }
        $d->save();
    }

    /** Called by the scheduler every minute: the tries that are due. */
    public static function retryDue(int $limit = 50): int
    {
        $due = BioWebhookDelivery::where('status', 'pending')->whereNotNull('next_attempt_at')->where('next_attempt_at', '<=', now())->orderBy('next_attempt_at')->limit($limit)->get();
        foreach ($due as $d) {
            self::attempt($d);
        }

        return $due->count();
    }
}
