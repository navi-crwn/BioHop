<?php

use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('the create page is the wizard, and the plain form stays one query away', function () {
    $this->actingAs(User::factory()->create());
    $this->get('/bio/create')->assertOk()->assertSee('id="bio-wizard-config"', false)->assertSee('Pick your address');
    $this->get('/bio/create?classic=1')->assertOk()->assertSee('name="slug"', false)->assertDontSee('bio-wizard-config');
});

test('one wizard request builds a page with socials, branded links, profile and colours', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $this->actingAs($user);
    $res = $this->post('/bio/wizard', [
        'slug' => 'Wizardly', 'title' => 'Helena Studio', 'bio' => 'Illustrator in Bandung', 'goal' => 'creator', 'palette' => 2,
        'platforms' => [['platform' => 'instagram', 'value' => 'helena'], ['platform' => 'youtube', 'value' => ''], ['platform' => 'nope', 'value' => 'x']],
        'links' => ['https://www.youtube.com/@helena', 'example.com/portfolio', ''],
        'avatar' => UploadedFile::fake()->image('me.jpg', 300, 300),
    ], ['Accept' => 'application/json'])->assertOk()->json();
    expect($res['redirect'])->toContain('/bio/wizardly/edit');
    $page = BioPage::where('slug', 'wizardly')->firstOrFail();
    expect($page->title)->toBe('Helena Studio')
        ->and($page->social_links)->toBe([['platform' => 'instagram', 'value' => 'helena', 'enabled' => true]])
        ->and($page->background_type)->toBe('solid')->and($page->background_value)->toBe('#f6d6e8')->and($page->button_bg_color)->toBe('#5b1f47')
        ->and($page->avatar_url)->toStartWith('avatars/');
    $links = BioLink::where('bio_page_id', $page->id)->orderBy('order')->get();
    expect($links)->toHaveCount(2)
        ->and($links[0]->brand)->toBe('youtube')->and($links[0]->title)->toBe('YouTube')->and($links[0]->url)->toBe('https://www.youtube.com/@helena')
        ->and($links[1]->brand)->toBeNull()->and($links[1]->title)->toBe('Example')->and($links[1]->url)->toBe('https://example.com/portfolio');
    $this->get('/b/wizardly')->assertOk()->assertSee('Helena Studio')->assertSee('YouTube');
});

test('the wizard refuses a reserved or taken address and a dangerous link', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $this->postJson('/bio/wizard', ['slug' => 'shop', 'title' => 'x'])->assertStatus(422)->assertJsonValidationErrors('slug');
    BioPage::create(['user_id' => $user->id, 'slug' => 'taken1', 'title' => 'T']);
    $this->postJson('/bio/wizard', ['slug' => 'taken1', 'title' => 'x'])->assertStatus(422)->assertJsonValidationErrors('slug');
    $this->postJson('/bio/wizard', ['slug' => 'fine12', 'title' => 'x', 'links' => ['javascript:alert(1)']])->assertStatus(422);
});
