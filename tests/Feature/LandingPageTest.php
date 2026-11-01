<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('home page translations cover every rendered string', function (string $locale) {
    $missing = [];
    app('translator')->handleMissingKeysUsing(function ($key) use (&$missing) {
        $missing[] = $key;

        return $key;
    });
    $this->get('/?lang='.$locale)->assertOk();
    expect(array_values(array_unique($missing)))->toBe([], implode(', ', array_unique($missing)));
})->with(['en', 'id', 'zh', 'es', 'ar', 'pt', 'fr', 'ja']);

test('guests see the public landing page instead of being redirected to login', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('BioHop')
        ->assertSee('One link-in-bio page for everything you do.')
        ->assertSee('hel.ink/b/')
        ->assertSee('Claim my page')
        ->assertSee('LinkHop')
        ->assertSee('PixelHop')
        ->assertSee('NoteHop');
});

test('authenticated users see dashboard link on landing page', function () {
    $user = User::factory()->create([
        'email' => 'bio-user@example.com',
        'name' => 'Bio User',
    ]);

    $response = $this->actingAs($user)->get('/');

    $response->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('One link-in-bio page for everything you do.');
});

test('public slug check endpoint returns availability status', function () {
    // 1. Available slug
    $res1 = $this->getJson('/check-slug-public?slug=fresh-creator');
    $res1->assertOk()
        ->assertJson([
            'slug' => 'fresh-creator',
            'available' => true,
        ]);

    // 2. Reserved slug
    $res2 = $this->getJson('/check-slug-public?slug=admin');
    $res2->assertOk()
        ->assertJson([
            'slug' => 'admin',
            'available' => false,
        ]);

    // 3. Already taken slug
    $user = User::factory()->create();
    BioPage::create([
        'user_id' => $user->id,
        'slug' => 'taken-slug',
        'title' => 'Taken Page',
        'theme' => 'default',
        'is_published' => true,
    ]);

    $res3 = $this->getJson('/check-slug-public?slug=taken-slug');
    $res3->assertOk()
        ->assertJson([
            'slug' => 'taken-slug',
            'available' => false,
        ]);

    // 4. Invalid format
    $res4 = $this->getJson('/check-slug-public?slug=ab');
    $res4->assertStatus(422);
});

test('dashboard route redirects to hub biohop dashboard', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('dashboard#biohop');
});
