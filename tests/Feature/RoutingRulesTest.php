<?php

use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;

function routedLink(array $rules): array
{
    $user = User::factory()->create(['timezone' => 'UTC']);
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'route'.mt_rand(1000, 9999), 'title' => 'R', 'is_published' => true]);
    $link = BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Shop', 'url' => 'https://default.example/', 'order' => 0, 'is_active' => true, 'settings' => ['rules' => $rules]]);

    return [$user, $page, $link];
}

test('rules decide by device, system, hours and country, first match wins', function () {
    [, , $link] = routedLink([
        ['country' => 'id, sg', 'device' => 'any', 'os' => 'any', 'from' => '', 'to' => '', 'url' => 'https://local.example/'],
        ['country' => '', 'device' => 'mobile', 'os' => 'ios', 'from' => '', 'to' => '', 'url' => 'https://ios.example/'],
        ['country' => '', 'device' => 'any', 'os' => 'any', 'from' => '22', 'to' => '6', 'url' => 'https://night.example/'],
    ]);
    $day = now()->setTime(12, 0);
    $night = now()->setTime(23, 30);
    $early = now()->setTime(3, 0);
    $none = fn () => null;
    expect($link->routedUrl(['device' => 'Desktop', 'os' => 'Windows'], fn () => 'ID', $day))->toBe('https://local.example/')
        ->and($link->routedUrl(['device' => 'Mobile', 'os' => 'iOS'], fn () => 'US', $day))->toBe('https://ios.example/')
        ->and($link->routedUrl(['device' => 'Desktop', 'os' => 'Windows'], fn () => 'US', $night))->toBe('https://night.example/')
        ->and($link->routedUrl(['device' => 'Desktop', 'os' => 'Windows'], fn () => 'US', $early))->toBe('https://night.example/')
        ->and($link->routedUrl(['device' => 'Desktop', 'os' => 'Windows'], $none, $day))->toBeNull();
});

test('the country is only looked up when a rule asks for it', function () {
    [, , $link] = routedLink([['country' => '', 'device' => 'mobile', 'os' => 'any', 'from' => '', 'to' => '', 'url' => 'https://m.example/']]);
    $called = false;
    $link->routedUrl(['device' => 'Mobile', 'os' => 'Android'], function () use (&$called) {
        $called = true;

        return 'ID';
    }, now());
    expect($called)->toBeFalse();
});

test('the click route follows a matching rule and falls back to the link address', function () {
    [$user, $page, $link] = routedLink([['country' => '', 'device' => 'any', 'os' => 'android', 'from' => '', 'to' => '', 'url' => 'https://play.example/x']]);
    $android = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Mobile Safari/537.36';
    $this->withHeader('User-Agent', $android)->get(route('bio.public.click', $link->public_id))->assertRedirect('https://play.example/x');
    $mac = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15';
    $this->withHeader('User-Agent', $mac)->get(route('bio.public.click', $link->public_id))->assertRedirect('https://default.example/');
});

test('rules are validated and checked for safety when saved', function () {
    [$user, $page, $link] = routedLink([]);
    $this->actingAs($user);
    $ok = ['settings' => ['rules' => [['country' => 'ID,SG', 'device' => 'mobile', 'os' => 'ios', 'from' => 9, 'to' => 17, 'url' => 'https://ok.example/']]]];
    $this->putJson("/bio/{$page->slug}/links/{$link->id}", $ok)->assertOk();
    expect($link->fresh()->routingRules())->toHaveCount(1);
    foreach ([
        ['url' => 'javascript:alert(1)'],
        ['url' => 'https://ok.example/', 'country' => 'Indonesia'],
        ['url' => 'https://ok.example/', 'device' => 'fridge'],
        ['url' => 'https://ok.example/', 'from' => 30, 'to' => 2],
    ] as $rule) {
        $this->putJson("/bio/{$page->slug}/links/{$link->id}", ['settings' => ['rules' => [$rule]]])->assertStatus(422);
    }
    $this->putJson("/bio/{$page->slug}/links/{$link->id}", ['settings' => ['rules' => array_fill(0, 11, ['url' => 'https://ok.example/'])]])->assertStatus(422);
});
