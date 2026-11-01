<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a public view counts without invalidating an open editor', function () {
    $this->travelTo(now()->startOfSecond());
    $owner = User::factory()->create();
    $page = BioPage::create(['user_id' => $owner->id, 'slug' => 'revision-view', 'title' => 'Before', 'is_published' => true]);
    $base = $page->updated_at->toJSON();

    $this->travel(5)->seconds();
    $this->get('/b/revision-view')->assertOk();
    expect($page->fresh()->view_count)->toBe(1);
    $this->actingAs($owner)->putJson('/bio/revision-view', ['title' => 'After', 'base_updated_at' => $base])->assertOk();
    expect($page->fresh()->title)->toBe('After');
});

test('avatar upload returns the revision used by the next save', function () {
    Storage::fake('public');
    $this->travelTo(now()->startOfSecond());
    $owner = User::factory()->create();
    $page = BioPage::create(['user_id' => $owner->id, 'slug' => 'revision-avatar', 'title' => 'Before']);
    $base = $page->updated_at->toJSON();

    $this->travel(5)->seconds();
    $uploaded = $this->actingAs($owner)->postJson('/bio/revision-avatar/upload-avatar', ['avatar' => UploadedFile::fake()->image('avatar.png')])
        ->assertOk()->assertJsonStructure(['page_updated_at']);
    $revision = $uploaded->json('page_updated_at');
    expect($revision)->not->toBe($base);
    $this->putJson('/bio/revision-avatar', ['title' => 'After', 'base_updated_at' => $revision])->assertOk();
    Storage::disk('public')->assertExists($page->fresh()->avatar_url);
});

test('a real change from another editor still rejects the stale save', function () {
    $this->travelTo(now()->startOfSecond());
    $owner = User::factory()->create();
    $page = BioPage::create(['user_id' => $owner->id, 'slug' => 'revision-tabs', 'title' => 'Before']);
    $base = $page->updated_at->toJSON();
    $this->travel(5)->seconds();
    $this->actingAs($owner)->putJson('/bio/revision-tabs', ['title' => 'First editor', 'base_updated_at' => $base])->assertOk();
    $this->travel(5)->seconds();
    $this->putJson('/bio/revision-tabs', ['title' => 'Stale editor', 'base_updated_at' => $base])
        ->assertConflict()->assertJsonPath('conflict', true);
    expect($page->fresh()->title)->toBe('First editor');
});
