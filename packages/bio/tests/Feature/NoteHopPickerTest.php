<?php

use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Support\NoteHopNotes;

// "Choose from NoteHop" is retired: a note is shared from NoteHop itself, and the Text block is the way to write on a
// page. The door is gone whatever the old flag says; a link block that already points to a note keeps working.
// NoteHopNotes::isNoteUrl stays, because the address checks of the other blocks still use NoteHop's note shape.

test('the NoteHop route, button and dialog do not exist, even when the old flag is switched on', function () {
    config(['bio.notehop_picker' => true, 'bio.notehop_url' => 'https://note.test']);
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'nhoff', 'title' => 'T']);
    expect(NoteHopNotes::enabled())->toBeFalse();
    $this->actingAs($user)->getJson('/bio/media/notehop')->assertNotFound();
    $html = $this->actingAs($user)->get('/bio/nhoff/edit')->assertOk()->getContent();
    expect($html)->not->toContain('Choose from NoteHop')->not->toContain('notehop-picker')->not->toContain('openNoteHop');
    preg_match('/<script type="application\/json" id="bio-editor-config">(.*?)<\/script>/s', $html, $m);
    expect(json_decode($m[1], true))->not->toHaveKey('notehop');
    // the script the editor loads has no picker either
    expect(file_get_contents(base_path('resources/js/editor.js')))->not->toContain('openNoteHop')->not->toContain('notehop');
    // PixelHop's picture picker is a different thing and stays
    expect(file_get_contents(base_path('resources/js/editor.js')))->toContain('openPixelHop');
});

test('a link block that already points to a NoteHop note still shows on the page and can be saved', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'nhold', 'title' => 'T', 'is_published' => true]);
    $block = BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'My menu note', 'url' => 'https://note.hel.ink/menu-warung', 'order' => 0, 'is_active' => true]);
    $this->get('/b/nhold')->assertOk()->assertSee('My menu note');
    $this->actingAs($user)->putJson('/bio/nhold/links/'.$block->id, ['type' => 'link', 'title' => 'Renamed note', 'url' => 'https://note.hel.ink/menu-warung'])->assertOk();
    expect($block->fresh()->title)->toBe('Renamed note')->and($block->fresh()->url)->toBe('https://note.hel.ink/menu-warung');
    $this->get('/b/nhold')->assertOk()->assertSee('Renamed note');
});

test('isNoteUrl accepts only NoteHop\'s own note paths', function () {
    config(['bio.notehop_url' => 'https://note.test']);
    expect(NoteHopNotes::isNoteUrl('https://note.test/abc'))->toBeTrue()
        ->and(NoteHopNotes::isNoteUrl('https://note.test/ab'))->toBeFalse()
        ->and(NoteHopNotes::isNoteUrl('https://note.test/abc#k'))->toBeFalse()
        ->and(NoteHopNotes::isNoteUrl('http://note.test/abc'))->toBeFalse()
        ->and(NoteHopNotes::isNoteUrl('https://note.test@evil.test/abc'))->toBeFalse()
        ->and(NoteHopNotes::isNoteUrl(null))->toBeFalse();
});
