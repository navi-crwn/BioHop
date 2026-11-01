<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Illuminate\Support\Facades\Storage;

function headerPage(string $slug, array $extra = []): array
{
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => $slug, 'title' => 'Helena', 'bio' => 'Hello there', 'is_published' => true] + $extra);

    return [$user, $page];
}

test('the editor saves one of the four header layouts and rejects anything else', function () {
    [$user, $page] = headerPage('head-save');
    $this->actingAs($user);
    foreach (BioPage::HEADER_LAYOUTS as $layout) {
        $this->putJson("/bio/{$page->slug}", ['header_layout' => $layout])->assertOk();
        expect($page->fresh()->header_layout)->toBe($layout);
    }
    $this->putJson("/bio/{$page->slug}", ['header_layout' => 'sidebar'])->assertStatus(422);
    $this->putJson("/bio/{$page->slug}", ['cover_image' => 'javascript:alert(1)'])->assertStatus(422);
    $this->putJson("/bio/{$page->slug}", ['cover_image' => 'bio-images/cover.jpg'])->assertOk();
    expect($page->fresh()->cover_image)->toBe('bio-images/cover.jpg');
});

test('the public page renders each header layout, falling back to classic without a picture', function () {
    Storage::fake('public');
    [, $page] = headerPage('head-public', ['header_layout' => 'hero']);
    // no avatar yet: hero needs one
    $this->get('/b/head-public')->assertOk()->assertSee('bio-head-classic')->assertDontSee('class="bio-hero-img"', false);

    Storage::disk('public')->put('avatars/helena.png', 'png');
    $page->update(['avatar_url' => 'avatars/helena.png']);
    $this->get('/b/head-public')->assertOk()->assertSee('bio-head-hero')->assertSee('class="bio-hero-img"', false)->assertSee('Hello there');

    $page->update(['header_layout' => 'cutout']);
    $this->get('/b/head-public')->assertOk()->assertSee('bio-head-cutout');

    $page->update(['header_layout' => 'banner', 'cover_image' => 'bio-images/cover.jpg']);
    $html = $this->get('/b/head-public')->assertOk()->getContent();
    expect($html)->toContain('bio-head-banner')->toContain('class="bio-cover"')->toContain('/storage/bio-images/cover.jpg');

    $page->update(['header_layout' => 'classic']);
    $this->get('/b/head-public')->assertOk()->assertSee('bio-head-classic')->assertDontSee('class="bio-cover"', false);
});
