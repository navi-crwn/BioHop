<?php

use App\Models\User;
use App\Support\BioLimits;
use Hop\Bio\Models\BioPage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

test('without a stored value each limit is the configured default', function () {
    expect(BioLimits::maxPages())->toBe(3)
        ->and(BioLimits::maxBlocks())->toBe(100)
        ->and(BioLimits::allowCustomCss())->toBeTrue()
        ->and(BioLimits::get('bio.slug_min_length'))->toBe(5);
});

test('a saved limit survives a cache clear', function () {
    BioLimits::save(['bio.max_pages_per_user' => 7]);
    expect(BioLimits::maxPages())->toBe(7);
    Cache::flush();
    expect(BioLimits::maxPages())->toBe(7);
});

test('a lowered page limit stops a new page and lifts again when raised', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'first1', 'title' => 'First', 'is_published' => true]);
    $this->actingAs($user);
    BioLimits::save(['bio.max_pages_per_user' => 1]);
    $this->get(route('bio.create'))->assertOk()->assertSee('Your pages are full');
    $this->postJson(route('bio.wizard'), ['slug' => 'second1'])->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'maximum limit of 1 bio pages'));
    BioLimits::save(['bio.max_pages_per_user' => 2]);
    $this->get(route('bio.create'))->assertOk()->assertDontSee('Your pages are full');
});

test('a raised page limit lets an account go past the old three', function () {
    $user = User::factory()->create();
    foreach (range(1, 3) as $i) {
        BioPage::create(['user_id' => $user->id, 'slug' => 'many'.$i, 'title' => 'Many '.$i, 'is_published' => true]);
    }
    $this->actingAs($user);
    $this->get(route('bio.create'))->assertSee('Your pages are full');
    BioLimits::save(['bio.max_pages_per_user' => 4]);
    $this->get(route('bio.create'))->assertDontSee('Your pages are full');
});

test('the block limit is read from the setting', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'blocks1', 'title' => 'Blocks']);
    $this->actingAs($user);
    BioLimits::save(['bio.max_blocks_per_page' => 1]);
    $this->postJson('/bio/blocks1/links', ['type' => 'divider'])->assertOk();
    $this->postJson('/bio/blocks1/links', ['type' => 'divider'])->assertStatus(422)->assertJsonValidationErrors('type');
    expect($page->links()->count())->toBe(1);
});

test('the shortest address for members follows the setting, admins keep three', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    BioLimits::save(['bio.slug_min_length' => 8]);
    $this->postJson('/bio/wizard', ['slug' => 'abcdefg', 'title' => 'T'])->assertStatus(422)->assertJsonValidationErrors('slug');
    expect(BioLimits::slugMin($user))->toBe(8);
    $admin = User::factory()->create(['email' => config('auth.superadmin_email', 'admin@hel.ink')]);
    expect(BioLimits::slugMin($admin))->toBe(3);
});

test('custom css can be switched off: new css is refused, saved css is not sent to visitors', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'csspage', 'title' => 'Css', 'is_published' => true, 'custom_css' => '.bio-name { letter-spacing: 3px; }']);
    expect($this->get('/b/csspage')->getContent())->toContain('letter-spacing: 3px');

    BioLimits::save(['bio.allow_custom_css' => false]);
    expect($this->get('/b/csspage')->getContent())->not->toContain('letter-spacing: 3px');

    $this->actingAs($user);
    $this->putJson('/bio/csspage', ['custom_css' => '.bio-name { color: red; }'])->assertStatus(422)->assertJsonValidationErrors('custom_css');
    // saving the page again with the CSS it already has, or without any, still works
    $this->putJson('/bio/csspage', ['custom_css' => '.bio-name { letter-spacing: 3px; }', 'title' => 'Renamed'])->assertOk();
    $this->putJson('/bio/csspage', ['custom_css' => null])->assertOk();
    expect($page->fresh()->custom_css)->toBeNull();

    BioLimits::save(['bio.allow_custom_css' => true]);
    $this->putJson('/bio/csspage', ['custom_css' => '.bio-name { color: red; }'])->assertOk();
    expect($this->get('/b/csspage')->getContent())->toContain('color: red');
});

test('the bio_settings table can be missing without breaking a request', function () {
    Schema::drop('bio_settings');
    expect(BioLimits::maxPages())->toBe(3);
});
