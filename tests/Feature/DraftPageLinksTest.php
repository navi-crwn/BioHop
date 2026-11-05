<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;

function draftAndLive(): array
{
    $user = User::factory()->create();
    $draft = BioPage::create(['user_id' => $user->id, 'slug' => 'mydraft', 'title' => 'D', 'is_published' => false]);
    $live = BioPage::create(['user_id' => $user->id, 'slug' => 'mylive', 'title' => 'L', 'is_published' => true]);

    return [$user, $draft, $live];
}

test('a draft answers 404 at its public address, so nothing below may link there', function () {
    [$user] = draftAndLive();
    $this->actingAs($user)->get('/b/mydraft')->assertNotFound();
    $this->get('/b/mydraft/qr')->assertNotFound();
});

test('the page list links a published page and only names the address of a draft', function () {
    [$user] = draftAndLive();
    $this->actingAs($user)->get('/bio')->assertOk()
        ->assertSee('href="'.public_url('b/mylive').'"', false)
        ->assertDontSee('href="'.public_url('b/mydraft').'"', false)
        ->assertSee(public_host().'/b/mydraft');
});

test('analytics offers View live and QR code only for a published page', function () {
    [$user] = draftAndLive();
    $this->actingAs($user);
    $this->get('/bio/mylive/analytics')->assertOk()
        ->assertSee('href="'.public_url('b/mylive').'"', false)->assertSee('href="'.public_url('b/mylive/qr').'"', false);
    $this->get('/bio/mydraft/analytics')->assertOk()
        ->assertDontSee('href="'.public_url('b/mydraft').'"', false)->assertDontSee('href="'.public_url('b/mydraft/qr').'"', false)
        ->assertDontSee('View live');
});

test('the editor links to the address only while the page is published', function () {
    $views = file_get_contents(base_path('packages/bio/resources/views/editor/index.blade.php'))
        .file_get_contents(base_path('packages/bio/resources/views/editor/appearance-tab.blade.php'));
    expect($views)->not->toContain(":href=\"'{{ public_url('b') }}/' + bioPage.slug\"")
        ->not->toContain(':href="publicUrl"')
        ->toContain(':href="visitUrl()"');
    expect(file_get_contents(base_path('resources/js/editor.js')))
        ->toContain("visitUrl() {\n            return this.bioPage.is_published ?")
        ->toContain("if (this.bioPage.is_published) cmds.push({ group: 'Page', label: 'Open the page in a new tab'");
});
