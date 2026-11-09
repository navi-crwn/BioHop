<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;

/** Five icons is the one number: the wizard, the editor save and the public page agree (report 2026-11-08, bio-r51/r66). */
function sixSocials(): array
{
    return collect(['instagram', 'tiktok', 'youtube', 'threads', 'x', 'facebook'])
        ->map(fn ($p) => ['platform' => $p, 'value' => 'helena', 'enabled' => true])->all();
}

test('the wizard refuses more than five social icons, and tells the page the number', function () {
    $this->actingAs(User::factory()->create());
    $this->get('/bio/create')->assertOk()->assertSee('"maxSocials":'.BioPage::SOCIAL_MAX, false);
    $this->postJson('/bio/wizard', ['slug' => 'toomany', 'title' => 'Too many', 'platforms' => sixSocials()])
        ->assertStatus(422)->assertJsonValidationErrors('platforms');
    $this->postJson('/bio/wizard', ['slug' => 'justfive', 'title' => 'Five', 'platforms' => array_slice(sixSocials(), 0, 5)])->assertOk();
    expect(BioPage::where('slug', 'justfive')->first()->social_links)->toHaveCount(5);
});

test('the editor save refuses a sixth social icon', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'sixsoc', 'title' => 'Six']);
    $this->actingAs($user)->putJson('/bio/sixsoc', ['social_links' => sixSocials()])
        ->assertStatus(422)->assertJsonValidationErrors('social_links');
    $this->putJson('/bio/sixsoc', ['social_links' => array_slice(sixSocials(), 0, 5)])->assertOk();
    expect($page->fresh()->social_links)->toHaveCount(5);
});

test('a page that still holds more than five icons shows five, and the wizard builds a pasted address correctly', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'oldsix', 'title' => 'Old six', 'is_published' => true, 'social_links' => sixSocials()]);
    $html = $this->get('/b/oldsix')->assertOk()->getContent();
    expect(substr_count($html, 'class="social-icon-btn'))->toBe(5);

    $this->actingAs($user)->postJson('/bio/wizard', ['slug' => 'pasted', 'title' => 'Pasted',
        'platforms' => [['platform' => 'youtube', 'value' => 'youtube.com/@helena'], ['platform' => 'telegram', 'value' => 't.me/helena']]])->assertOk();
    $html = $this->get('/b/pasted')->assertOk()->getContent();
    expect($html)->toContain('href="https://youtube.com/@helena"')->toContain('href="https://t.me/helena"')
        ->not->toContain('youtube.com/@youtube.com')->not->toContain('t.me/t.me');
});
