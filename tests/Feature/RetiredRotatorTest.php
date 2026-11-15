<?php

use App\Models\User;
use App\Support\Reminders;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Models\BioRevision;
use Hop\Bio\Support\BioRevisions;

/*
| The WhatsApp rotator block is gone from the code. A row of that type can still sit in an old database: it must
| not break the public page or the editor, the public page skips it, and its owner can delete it.
*/

function rotatorPage(string $slug): array
{
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => $slug, 'title' => 'Rot', 'is_published' => true]);
    $old = BioLink::create(['bio_page_id' => $page->id, 'type' => 'whatsapp', 'title' => 'Old chat', 'brand' => 'whatsapp', 'order' => 0, 'is_active' => true,
        'settings' => ['mode' => 'round_robin', 'numbers' => [['number' => '6281200000001']]]]);
    BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Plain link', 'url' => 'https://example.com/', 'order' => 1, 'is_active' => true]);

    return [$user, $page, $old];
}

test('a page that still has an old rotator row shows its other blocks and skips the rotator', function () {
    [, , $old] = rotatorPage('rot-pub');
    $this->get('/b/rot-pub')->assertOk()->assertSee('Plain link')->assertDontSee('Old chat');
    $this->get('/b/shop/rot-pub')->assertRedirect('/b/rot-pub');
    // the click address of the old block no longer sends anyone anywhere
    $this->get(route('bio.public.click', $old->public_id))->assertNotFound();
});

test('the editor opens with an old rotator row, offers no rotator form, and the owner can delete it', function () {
    [$user, $page, $old] = rotatorPage('rot-edit');
    $html = $this->actingAs($user)->get('/bio/rot-edit/edit')->assertOk()->getContent();
    expect($html)->toContain('Old chat')->toContain('retiredTypes')
        ->not->toContain('Add number')->not->toContain("editingBlock?.type === 'whatsapp'");
    // it cannot be made again, nor saved as that type
    $this->postJson('/bio/rot-edit/links', ['type' => 'whatsapp', 'title' => 'New'])->assertStatus(422)->assertJsonValidationErrors('type');
    $this->putJson("/bio/rot-edit/links/{$old->id}", ['type' => 'whatsapp', 'title' => 'Renamed'])->assertStatus(422);
    expect($old->fresh()->title)->toBe('Old chat');
    $this->deleteJson("/bio/rot-edit/links/{$old->id}")->assertOk();
    expect(BioLink::find($old->id))->toBeNull();
});

test('a saved version that holds a rotator can still be restored without an error', function () {
    [$user, $page] = rotatorPage('rot-restore');
    $revision = BioRevisions::snapshot($page, 'with rotator');
    $page->links()->where('type', 'whatsapp')->delete();
    BioRevisions::restore($page->fresh(), BioRevision::find($revision->id));
    $this->get('/b/rot-restore')->assertOk()->assertSee('Plain link')->assertDontSee('Old chat');
    $this->actingAs($user)->get('/bio/rot-restore/edit')->assertOk();
});

test('no rotator setting is left in the configuration or the example environment', function () {
    expect(config('hop.whatsapp_retire_on'))->toBeNull();
    expect(file_get_contents(base_path('.env.example')))->not->toContain('WHATSAPP_RETIRE_ON');
    expect(Reminders::KEYS)->not->toContain('whatsapp-rotator-retired');
});
