<?php

use App\Models\User;
use Hop\Bio\Http\Controllers\BioPageController;
use Hop\Bio\Models\BioPage;

test('no editor class defined twice with and without the editor prefix (a picker once restyled the header this way)', function () {
    $css = file_get_contents(base_path('resources/css/editor.css'));
    // global (unprefixed) rules for classes that the editor shell also styles under .bio-editor
    foreach (['be-head', 'be-title', 'be-url', 'be-main', 'be-grid', 'be-save', 'be-state'] as $class) {
        expect(preg_match('/^\.'.preg_quote($class, '/').'[\s.{:,\[]/m', $css))->toBe(0, ".{$class} must only be defined under .bio-editor");
    }
});

test('the editor preview never loads third-party players or maps: it shows placeholders', function () {
    $preview = file_get_contents(base_path('packages/bio/resources/views/editor/preview.blade.php'));
    expect($preview)->not->toContain('<iframe')->not->toContain('youtube.com/embed')->not->toContain('open.spotify.com/embed')->not->toContain('maps.google.com')->toContain('pv-facade');
});

test('the analytics page keeps its cards in equal-height columns, with recent clicks on its own row', function () {
    $view = file_get_contents(base_path('packages/bio/resources/views/analytics.blade.php'));
    expect($view)->not->toContain('items-start')->toContain('content-start');
});

test('the dashboard home lets its statistics card stretch to the checklist beside it', function () {
    $view = file_get_contents(base_path('packages/bio/resources/views/index.blade.php'));
    expect($view)->not->toContain('md:items-start')->toContain('flex flex-col justify-between');
});

test('a page without an avatar shows a whole letter of its title, also for non-Latin scripts', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'arini', 'title' => 'صفحة اختبار', 'is_published' => true]);
    $html = $this->get('/b/arini')->assertOk()->getContent();
    expect(mb_check_encoding($html, 'UTF-8'))->toBeTrue()->and($html)->toContain('ص');
});

test('the password page can not be dragged sideways by its decorative background', function () {
    $view = file_get_contents(base_path('packages/bio/resources/views/password.blade.php'));
    expect($view)->toContain('position: fixed')->not->toContain('overflow: hidden');
});

test('a very long page title wraps inside the phone screen', function () {
    $view = file_get_contents(base_path('packages/bio/resources/views/show.blade.php'));
    expect($view)->toContain('overflow-wrap: anywhere');
});

test('someone who has used all their pages is told so on the create screen, before filling in the wizard', function () {
    $user = User::factory()->create();
    foreach (range(1, BioPageController::MAX_PAGES) as $i) {
        BioPage::create(['user_id' => $user->id, 'slug' => 'full'.$i, 'title' => 'Full '.$i, 'is_published' => true]);
    }
    $this->actingAs($user)->get(route('bio.create'))->assertOk()->assertSee('Your pages are full')->assertDontSee('Pick your address');
});

test('someone with room left still gets the wizard', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('bio.create'))->assertOk()->assertSee('Pick your address')->assertDontSee('Your pages are full');
});

test('analytics lists only blocks that can be clicked, and counts only those as active links', function () {
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => 'clickable', 'title' => 'Clickable', 'is_published' => true]);
    $page->links()->create(['type' => 'link', 'title' => 'Real link', 'url' => 'https://example.com/a', 'order' => 1, 'is_active' => true]);
    $page->links()->create(['type' => 'text', 'title' => 'Just words', 'content' => 'Hello', 'order' => 2, 'is_active' => true]);
    $page->links()->create(['type' => 'divider', 'title' => 'Line', 'order' => 3, 'is_active' => true]);
    $this->actingAs($user)->get('/bio/clickable/analytics')->assertOk()->assertSee('Real link')->assertDontSee('Just words')->assertViewHas('stats', fn ($stats) => $stats['activeLinks'] === 1);
});

test('inputs that carry an icon keep their side padding over the base input style', function () {
    $css = file_get_contents(base_path('resources/css/editor.css'));
    expect($css)->toContain('.bio-editor .form-input.pl-10')->toContain('.bio-editor .form-input.pr-10');
});
