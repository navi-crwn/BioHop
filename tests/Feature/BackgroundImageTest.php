<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('an uploaded picture becomes the page background and shows on the public page', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'bgpic', 'title' => 'Bg', 'is_published' => true, 'theme' => 'galaxy']);
    $this->actingAs($user);

    $up = $this->postJson('/bio/bgpic/upload-image', ['image' => UploadedFile::fake()->image('wall.jpg', 1200, 800)])->assertOk()->json();
    expect($up['success'])->toBeTrue()->and($up['path'] ?? $up['image_url'] ?? null)->toStartWith('bio-images/');
    Storage::disk('public')->assertExists($up['path']);

    // what the editor sends after the upload
    $this->putJson('/bio/bgpic', ['background_type' => 'image', 'background_image' => $up['path'], 'background_value' => $up['url']])->assertOk();
    $page = BioPage::where('slug', 'bgpic')->firstOrFail();
    expect($page->background_type)->toBe('image')->and($page->background_image)->toBe($up['path']);

    $this->get('/b/bgpic')->assertOk()->assertSee("url('".Storage::disk('public')->url($up['path'])."')", false);
});

test('a replaced background picture is deleted and an outside address is still checked', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'bgswap', 'title' => 'Bg']);
    $this->actingAs($user);
    $first = $this->postJson('/bio/bgswap/upload-image', ['image' => UploadedFile::fake()->image('a.jpg', 400, 300)])->json('path');
    $this->putJson('/bio/bgswap', ['background_type' => 'image', 'background_image' => $first, 'background_value' => '/storage/'.$first])->assertOk();
    $second = $this->postJson('/bio/bgswap/upload-image', ['image' => UploadedFile::fake()->image('b.jpg', 400, 300)])->json('path');
    $this->putJson('/bio/bgswap', ['background_type' => 'image', 'background_image' => $second, 'background_value' => '/storage/'.$second])->assertOk();
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);

    $this->putJson('/bio/bgswap', ['background_type' => 'image', 'background_image' => 'javascript:alert(1)'])->assertStatus(422);
    $this->putJson('/bio/bgswap', ['background_type' => 'image', 'background_image' => '', 'background_value' => ''])->assertOk();
    expect(BioPage::where('slug', 'bgswap')->first()->background_image)->toBeEmpty();
});
