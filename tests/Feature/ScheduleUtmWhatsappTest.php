<?php

use App\Models\User;
use Hop\Bio\Http\Controllers\PublicBioController;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;

function smallPage(string $slug, array $extra = []): array
{
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => $slug, 'title' => 'Small', 'is_published' => true] + $extra);

    return [$user, $page];
}

test('a scheduled block is hidden before it goes live and after it ends, and its address stops working', function () {
    [$user, $page] = smallPage('sched');
    $this->actingAs($user);
    $this->postJson("/bio/{$page->slug}/links", ['type' => 'link', 'title' => 'Flash sale', 'url' => 'https://shop.example/sale', 'publish_at' => now()->addDay()->toIso8601String()])->assertOk();
    $this->postJson("/bio/{$page->slug}/links", ['type' => 'link', 'title' => 'Old promo', 'url' => 'https://shop.example/old', 'publish_at' => now()->subDays(2)->toIso8601String(), 'expire_at' => now()->subDay()->toIso8601String()])->assertOk();
    $this->postJson("/bio/{$page->slug}/links", ['type' => 'link', 'title' => 'Always', 'url' => 'https://shop.example/always'])->assertOk();
    $this->postJson("/bio/{$page->slug}/links", ['type' => 'link', 'title' => 'Bad', 'url' => 'https://x.example', 'publish_at' => now()->addDay()->toIso8601String(), 'expire_at' => now()->toIso8601String()])->assertStatus(422);
    $html = $this->get('/b/sched')->assertOk()->getContent();
    expect($html)->toContain('Always')->not->toContain('Flash sale')->not->toContain('Old promo');
    $future = BioLink::where('title', 'Flash sale')->firstOrFail();
    expect($future->scheduleState())->toBe('scheduled')->and(BioLink::where('title', 'Old promo')->first()->scheduleState())->toBe('expired');
    $this->get(route('bio.public.click', $future->public_id))->assertNotFound();
    $this->get(route('bio.public.click', BioLink::where('title', 'Always')->first()->public_id))->assertRedirect('https://shop.example/always');
    $this->travel(2)->days();
    $this->get('/b/sched')->assertSee('Flash sale');
});

test('auto UTM adds parameters only to http links that have none', function () {
    expect(PublicBioController::withUtm('https://a.example/p?x=1#top', 'me'))->toBe('https://a.example/p?x=1&utm_source=biohop&utm_medium=bio&utm_campaign=me#top')
        ->and(PublicBioController::withUtm('https://a.example/?utm_source=tiktok', 'me'))->toBe('https://a.example/?utm_source=tiktok')
        ->and(PublicBioController::withUtm('mailto:a@b.co', 'me'))->toBe('mailto:a@b.co');
    [$user, $page] = smallPage('utm');
    $link = BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Site', 'url' => 'https://a.example/', 'order' => 0, 'is_active' => true]);
    $this->get(route('bio.public.click', $link->public_id))->assertRedirect('https://a.example/');
    $this->actingAs($user)->putJson('/bio/utm', ['auto_utm' => true])->assertOk();
    $this->get(route('bio.public.click', $link->public_id))->assertRedirect('https://a.example/?utm_source=biohop&utm_medium=bio&utm_campaign=utm');
});

test('a WhatsApp rotator hands clicks to its numbers in turn, or by weight, with the message filled in', function () {
    [$user, $page] = smallPage('warot');
    $this->actingAs($user);
    $this->postJson("/bio/{$page->slug}/links", ['type' => 'whatsapp', 'title' => 'Chat', 'brand' => 'whatsapp', 'settings' => ['text' => 'Hi from bio', 'mode' => 'round_robin', 'numbers' => [['number' => '+62 812-0000-0001', 'label' => 'Rina'], ['number' => '6281200000002', 'label' => 'Budi']]]])->assertOk();
    $block = BioLink::where('bio_page_id', $page->id)->firstOrFail();
    $this->get('/b/warot')->assertOk()->assertSee('Chat')->assertSee(route('bio.public.click', $block->public_id), false);
    $targets = collect(range(1, 4))->map(fn () => $this->get(route('bio.public.click', $block->public_id))->headers->get('Location'))->all();
    expect($targets)->toBe(['https://wa.me/6281200000001?text=Hi%20from%20bio', 'https://wa.me/6281200000002?text=Hi%20from%20bio', 'https://wa.me/6281200000001?text=Hi%20from%20bio', 'https://wa.me/6281200000002?text=Hi%20from%20bio']);

    $this->putJson("/bio/{$page->slug}/links/{$block->id}", ['settings' => ['text' => '', 'mode' => 'weighted', 'numbers' => [['number' => '6281200000001', 'weight' => 1], ['number' => '6281200000002', 'weight' => 100]]]])->assertOk();
    $seen = collect(range(1, 30))->map(fn () => $this->get(route('bio.public.click', $block->public_id))->headers->get('Location'))->unique();
    expect($seen->contains('https://wa.me/6281200000002'))->toBeTrue()->and($seen->every(fn ($t) => str_starts_with($t, 'https://wa.me/62812')))->toBeTrue();
    $this->putJson("/bio/{$page->slug}/links/{$block->id}", ['settings' => ['numbers' => [['number' => 'abc']]]])->assertStatus(422);
});
