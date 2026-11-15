<?php

use App\Models\User;
use App\Support\FamilyAnnouncements;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/*
| The family announcement card on the dashboard home (hub contract: GET {hub}/announcements.json?app=biohop).
| A fake hub stands in for the real one.
*/

function annItem(string $id, array $extra = []): array
{
    return $extra + ['id' => $id, 'level' => 'info', 'title' => 'Maintenance on Sunday', 'body' => 'The site is slow for an hour.', 'url' => 'https://hel.ink/announcements/'.$id,
        'published_at' => now()->subHour()->toIso8601String(), 'expires_at' => null, 'apps' => ['*']];
}

function fakeHub(array $items): void
{
    config(['hop.hub_url' => 'https://hub.test', 'hop.announcements' => true]);
    Cache::flush();
    Http::swap(new Factory);
    Http::fake(['https://hub.test/announcements.json*' => Http::response(['version' => 1, 'generated_at' => now()->toIso8601String(), 'items' => $items], 200, ['Cache-Control' => 'public, max-age=300'])]);
}

function annMember(): User
{
    return User::factory()->create();
}

test('a live announcement shows as one card on the dashboard home, escaped, with the title linking to its page', function () {
    fakeHub([annItem('12', ['title' => 'Update <b>now</b>', 'body' => '<script>alert(1)</script>Plain <i>words</i>'])]);
    $html = $this->actingAs(annMember())->get(route('bio.index'))->assertOk()->getContent();
    expect($html)->toContain('data-announcement')->toContain('data-hop-remind="ann-12"')->toContain('href="https://hel.ink/announcements/12"')
        ->toContain('Update now')->toContain('Plain words')->not->toContain('<script>alert(1)')->not->toContain('<b>now</b>');
    // the request is the plain public one: the app slug only, no cookie, no credentials, no member data
    Http::assertSent(fn (Request $r) => $r->url() === 'https://hub.test/announcements.json?app=biohop' && ! $r->hasHeader('Cookie') && ! $r->hasHeader('Authorization'));
    Http::assertSentCount(1);
});

test('the card is on the dashboard home only', function () {
    fakeHub([annItem('12')]);
    $user = annMember();
    $user->bioPages()->create(['slug' => 'annpage', 'title' => 'A']);
    $this->actingAs($user);
    foreach (['/bio/annpage/edit', route('settings'), '/bio/annpage/analytics', route('bio.manage')] as $url) {
        $this->get($url)->assertDontSee('data-announcement', false);
    }
    $this->get(route('bio.index'))->assertSee('data-announcement', false);
});

test('at most one card shows: a critical one first, else the newest', function () {
    fakeHub([
        annItem('1', ['title' => 'Old news', 'published_at' => now()->subDays(3)->toIso8601String()]),
        annItem('2', ['title' => 'Newest news', 'published_at' => now()->subMinutes(5)->toIso8601String()]),
        annItem('3', ['title' => 'Urgent matter', 'level' => 'critical', 'published_at' => now()->subDays(2)->toIso8601String()]),
    ]);
    $html = $this->actingAs(annMember())->get(route('bio.index'))->assertOk()->getContent();
    expect(substr_count($html, 'data-announcement-level'))->toBe(1) // one card
        ->and($html)->toContain('Urgent matter')->not->toContain('Newest news')->not->toContain('Old news');
    fakeHub([annItem('1', ['title' => 'Old news', 'published_at' => now()->subDays(3)->toIso8601String()]), annItem('2', ['title' => 'Newest news', 'published_at' => now()->subMinutes(5)->toIso8601String()])]);
    $this->get(route('bio.index'))->assertSee('Newest news')->assertDontSee('Old news');
});

test('a hub that does not answer gives no card, no error and is asked only once a minute', function () {
    config(['hop.hub_url' => 'https://hub.test', 'hop.announcements' => true]);
    Cache::flush();
    $calls = 0;
    Http::swap(new Factory);
    Http::fake(function () use (&$calls) {
        $calls++;
        throw new ConnectionException('timed out');
    });
    $user = annMember();
    $this->actingAs($user)->get(route('bio.index'))->assertOk()->assertDontSee('data-announcement', false);
    $this->get(route('bio.index'))->assertOk()->assertDontSee('data-announcement', false);
    expect($calls)->toBe(1);
    expect(FamilyAnnouncements::TIMEOUT)->toBeLessThanOrEqual(2);
    // a server error and a page that is not a list are the same as no answer
    Cache::flush();
    Http::swap(new Factory);
    Http::fake(['*' => Http::response('<html>oops</html>', 502)]);
    $this->get(route('bio.index'))->assertOk()->assertDontSee('data-announcement', false);
    Cache::flush();
    Http::swap(new Factory);
    Http::fake(['*' => Http::response(['version' => 2, 'items' => [annItem('9')]], 200)]);
    $this->get(route('bio.index'))->assertOk()->assertDontSee('data-announcement', false);
});

test('when the hub goes away the last good copy keeps showing', function () {
    fakeHub([annItem('12')]);
    $user = annMember();
    $this->actingAs($user)->get(route('bio.index'))->assertSee('Maintenance on Sunday');
    Cache::forget('family-announcements:biohop'); // the five minutes are over
    Http::swap(new Factory);
    Http::fake(fn () => throw new ConnectionException('timed out'));
    $this->get(route('bio.index'))->assertOk()->assertSee('Maintenance on Sunday');
});

test('without a hub address, or with the switch off, nothing is fetched', function () {
    config(['hop.hub_url' => '', 'hop.announcements' => true]);
    Cache::flush();
    Http::swap(new Factory);
    Http::fake();
    $this->actingAs(annMember())->get(route('bio.index'))->assertOk()->assertDontSee('data-announcement', false);
    Http::assertNothingSent();
    config(['hop.hub_url' => 'https://hub.test', 'hop.announcements' => false]);
    $this->get(route('bio.index'))->assertOk()->assertDontSee('data-announcement', false);
    Http::assertNothingSent();
});

test('hiding it stays for a week and the card comes back after', function () {
    fakeHub([annItem('12')]);
    $user = annMember();
    $this->actingAs($user)->get(route('bio.index'))->assertSee('data-announcement', false);
    $this->postJson(route('reminders.dismiss'), ['key' => 'ann-12'])->assertOk();
    $this->get(route('bio.index'))->assertDontSee('data-announcement', false);
    // another member still sees it
    $this->actingAs(annMember())->get(route('bio.index'))->assertSee('data-announcement', false);
    $this->actingAs($user);
    $this->travel(6)->days();
    $this->get(route('bio.index'))->assertDontSee('data-announcement', false);
    $this->travel(2)->days();
    Cache::forget('family-announcements:biohop');
    $this->get(route('bio.index'))->assertSee('data-announcement', false);
    $this->travelBack();
});

test('a critical announcement has no Hide and cannot be hidden', function () {
    fakeHub([annItem('7', ['level' => 'critical', 'title' => 'Security notice'])]);
    $user = annMember();
    $html = $this->actingAs($user)->get(route('bio.index'))->assertOk()->getContent();
    expect($html)->toContain('Security notice')->toContain('data-announcement-level="critical"')->not->toContain('data-hop-remind="ann-7"');
    $this->postJson(route('reminders.dismiss'), ['key' => 'ann-7'])->assertStatus(422);
    $this->get(route('bio.index'))->assertSee('Security notice');
});

test('only keys of real announcements can be hidden, nothing else is stored', function () {
    fakeHub([annItem('12')]);
    $this->actingAs(annMember());
    foreach (['ann-99', 'ann-', 'ann-12/../x', 'made-up', 'whatsapp-rotator-retired', str_repeat('a', 80)] as $key) {
        $this->postJson(route('reminders.dismiss'), ['key' => $key])->assertStatus(422);
    }
    expect(DB::table('bio_reminders')->count())->toBe(0);
    $this->postJson(route('reminders.dismiss'), ['key' => 'setup-checklist'])->assertOk();
    $this->postJson(route('reminders.dismiss'), ['key' => 'ann-12'])->assertOk();
    expect(DB::table('bio_reminders')->pluck('key')->sort()->values()->all())->toBe(['ann-12', 'setup-checklist']);
});

test('items that are expired, not yet live, for another app or odd are skipped', function () {
    fakeHub([
        annItem('1', ['expires_at' => now()->subMinute()->toIso8601String(), 'title' => 'Gone']),
        annItem('2', ['published_at' => now()->addHour()->toIso8601String(), 'title' => 'Later']),
        annItem('3', ['apps' => ['payhop'], 'title' => 'Other app']),
        annItem('bad id!', ['title' => 'Odd id']),
        annItem('5', ['title' => '']),
        annItem('6', ['url' => 'javascript:alert(1)', 'title' => 'No link', 'body' => str_repeat('x ', 400)]),
    ]);
    $html = $this->actingAs(annMember())->get(route('bio.index'))->assertOk()->getContent();
    expect($html)->not->toContain('Gone')->not->toContain('Later')->not->toContain('Other app')->not->toContain('Odd id')
        ->toContain('No link')->not->toContain('javascript:');
    expect(mb_strlen(FamilyAnnouncements::cardFor(1)['body']))->toBeLessThanOrEqual(FamilyAnnouncements::BODY_LIMIT);
});

test('the old WhatsApp rotator card is gone from the dashboard', function () {
    fakeHub([]);
    $this->actingAs(annMember())->get(route('bio.index'))->assertOk()->assertDontSee('rotator')->assertDontSee('data-announcement', false);
});

test('the badge words exist in all nine languages', function () {
    foreach (['en', 'zh-CN', 'de', 'id', 'ja', 'pt-BR', 'es', 'fr', 'ru'] as $lang) {
        $d = json_decode(file_get_contents(base_path("lang/{$lang}.json")), true);
        foreach (['Notice', 'Warning', 'Important'] as $k) {
            expect(isset($d[$k]) && $d[$k] !== '')->toBeTrue("{$lang}: {$k}");
        }
    }
});
