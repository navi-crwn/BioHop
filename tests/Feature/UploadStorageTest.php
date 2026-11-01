<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Support\UploadStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('low storage blocks every bio upload but keeps text editing and existing files available', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'storage-test', 'title' => 'Before', 'avatar_url' => 'avatars/original.png']);
    Storage::disk('public')->put('avatars/original.png', 'original');
    $this->mock(UploadStorage::class, fn ($mock) => $mock->shouldReceive('available')->andReturn(false));
    $this->actingAs($user);
    foreach (['image' => 'image', 'avatar' => 'avatar', 'thumbnail' => 'thumbnail', 'icon' => 'icon', 'qr-logo' => 'logo'] as $route => $field) {
        $this->postJson('/bio/storage-test/upload-'.$route, [$field => UploadedFile::fake()->image('test.png')])
            ->assertStatus(507)->assertJsonPath('code', 'storage_unavailable')->assertHeader('Retry-After', '60');
    }
    $this->putJson('/bio/storage-test', ['avatar' => UploadedFile::fake()->image('test.png')])->assertStatus(507);
    $qrFile = UploadedFile::fake()->image('qr.png');
    $qr = 'data:image/png;base64,'.base64_encode(file_get_contents($qrFile->getRealPath()));
    $this->putJson('/bio/storage-test', ['qr_settings' => ['image' => $qr]])->assertStatus(507);
    $this->putJson('/bio/storage-test', ['title' => 'Text still works'])->assertOk();
    expect($page->fresh()->avatar_url)->toBe('avatars/original.png');
    Storage::disk('public')->assertExists('avatars/original.png');
    expect(Storage::disk('public')->allFiles())->toHaveCount(1);
});

test('upload status recovers without a restart and an avatar can then be uploaded', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'storage-recovery', 'title' => 'Recovery']);
    $this->actingAs($user);
    $this->partialMock(UploadStorage::class, fn ($mock) => $mock->shouldReceive('available')->andReturn(false, true, true));
    $this->getJson('/bio/upload-status')->assertOk()->assertJsonPath('available', false);
    $this->getJson('/bio/upload-status')->assertOk()->assertJsonPath('available', true)->assertHeader('Cache-Control', 'no-store, private');
    $this->postJson('/bio/storage-recovery/upload-avatar', ['avatar' => UploadedFile::fake()->image('avatar.png')])->assertOk();
});
