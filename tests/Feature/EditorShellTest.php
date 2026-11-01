<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;

test('the editor knows the member\'s other pages for the switcher and the palette, and offers the block catalogue', function () {
    $user = User::factory()->create();
    $a = BioPage::create(['user_id' => $user->id, 'slug' => 'shell-a', 'title' => 'Alpha', 'is_published' => true]);
    BioPage::create(['user_id' => $user->id, 'slug' => 'shell-b', 'title' => 'Beta', 'is_published' => true]);
    $other = User::factory()->create();
    BioPage::create(['user_id' => $other->id, 'slug' => 'shell-c', 'title' => 'Secret', 'is_published' => true]);
    $html = $this->actingAs($user)->get('/bio/shell-a/edit')->assertOk()->getContent();
    expect($html)->toContain('"pages":[')->toContain('shell-b')->not->toContain('shell-c')->not->toContain('Secret')
        ->toContain('aria-label="Open the command palette"')->toContain('Switch to another page')->toContain('Command palette');
});
