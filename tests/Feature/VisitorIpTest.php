<?php

use App\Models\User;
use App\Services\GeoIpService;
use Hop\Bio\Contracts\GeoLookup;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

// The privacy text says visitors are counted with a keyed hash, never the address. The counters, the ban check,
// the rate limits and the geolocation cache used to put the plain address in the cache key.
const VISITOR_IP = '93.184.216.34';

function cacheKeys(): array
{
    $store = Cache::store()->getStore();

    return array_keys((fn () => $this->storage)->call($store));
}

function keysWithIp(): array
{
    return array_values(array_filter(cacheKeys(), fn ($key) => str_contains($key, VISITOR_IP)));
}

function visitorPage(array $attributes = []): BioPage
{
    return BioPage::create(array_merge([
        'user_id' => User::factory()->create()->id,
        'slug' => 'visitor-ip',
        'title' => 'Visitor',
        'is_published' => true,
    ], $attributes));
}

beforeEach(function () {
    config(['services.turnstile.secret' => null, 'services.turnstile.site_key' => null]);
});

test('counting a page view keeps no plain address in the cache', function () {
    $page = visitorPage();

    $this->withServerVariables(['REMOTE_ADDR' => VISITOR_IP])->get('/b/visitor-ip')->assertOk();

    expect(keysWithIp())->toBe([]);
    expect($page->fresh()->view_count)->toBe(1);
});

test('a visitor still counts once a day and two visitors count twice', function () {
    $page = visitorPage();

    $this->withServerVariables(['REMOTE_ADDR' => VISITOR_IP])->get('/b/visitor-ip')->assertOk();
    $this->withServerVariables(['REMOTE_ADDR' => VISITOR_IP])->get('/b/visitor-ip')->assertOk();
    expect($page->fresh()->view_count)->toBe(1);

    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.9'])->get('/b/visitor-ip')->assertOk();
    expect($page->fresh()->view_count)->toBe(2);
});

test('the ban check, a counted click and the click cap keep no plain address in the cache', function () {
    $page = visitorPage();
    $link = BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'x', 'url' => 'https://example.com/', 'order' => 1, 'is_active' => true]);

    $this->withServerVariables(['REMOTE_ADDR' => VISITOR_IP])->get('/b/click/'.$link->public_id)->assertRedirect('https://example.com/');

    expect(keysWithIp())->toBe([]);
});

test('password attempts are limited per visitor without a plain address in the cache', function () {
    visitorPage(['password_enabled' => true, 'password' => bcrypt('right-one')]);

    $this->withServerVariables(['REMOTE_ADDR' => VISITOR_IP])->post('/b/visitor-ip', ['password' => 'wrong'])->assertOk();

    expect(keysWithIp())->toBe([]);
    expect(array_filter(cacheKeys(), fn ($key) => str_contains($key, 'bio-password:')))->not->toBe([]);
});

test('the geolocation cache is keyed by a hash, and the lookup still gets the real address', function () {
    Http::fake(['*' => Http::response(['countryCode' => 'DE', 'country' => 'Germany', 'city' => 'Berlin', 'country_code' => 'DE'], 200)]);

    $country = app(GeoLookup::class)->country(VISITOR_IP);

    expect($country)->toBe('DE');
    expect(keysWithIp())->toBe([]);
    Http::assertSent(fn ($request) => str_contains($request->url(), VISITOR_IP));
    // Asked again, the answer comes from the cache and no second request goes out.
    $sent = count(Http::recorded());
    expect(app(GeoLookup::class)->country(VISITOR_IP))->toBe('DE')->and(count(Http::recorded()))->toBe($sent);
});

test('web geolocation can be turned off, and an address is then not sent to any outside service', function () {
    config(['services.geolite.web_providers' => false]);
    Http::fake();

    app(GeoLookup::class)->country(VISITOR_IP);

    Http::assertNothingSent();
});

test('no geolocation provider talks to its service over plain http', function () {
    $service = app(GeoIpService::class);
    foreach ($service->getProviders() as $provider) {
        expect($provider['name'])->not->toBe('IpApi'); // ip-api.com answers only over http in its free plan
    }
    foreach (glob(app_path('Services/GeoIP/Providers/*.php')) as $file) {
        expect(file_get_contents($file))->not->toContain("'http://");
    }
});
