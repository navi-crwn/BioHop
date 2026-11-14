<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Hop\Ui\Locales;

/** The sidebar, the top bar, the Settings page and the editor tabs speak the member's language, not only the content pages. */
test('the sidebar and Settings page are translated', function (string $locale) {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'chrome'.$locale, 'title' => 'Chrome']);
    $catalog = json_decode(file_get_contents(base_path("lang/{$locale}.json")), true);
    $pages = $locale === 'en' ? 'My bio pages' : $catalog['My bio pages'];
    $settings = $locale === 'en' ? 'Settings' : $catalog['Settings'];
    $export = $locale === 'en' ? 'Export my bio pages' : $catalog['Export my bio pages'];
    $profile = $locale === 'en' ? 'Profile' : $catalog['Profile'];

    $this->actingAs($user)->get('/bio?lang='.$locale)->assertOk()->assertSee($pages)->assertSee($settings);
    $this->get('/settings?lang='.$locale)->assertOk()->assertSee($export)->assertSee($settings);
    $editor = $this->get('/bio/chrome'.$locale.'/edit?lang='.$locale)->assertOk()->getContent();
    expect($editor)->toMatch('~id="editor-tab-profile"[^>]*>\s*<svg.*?</svg>\s*'.preg_quote(e($profile), '~').'\s*</button>~su');
})->with(fn () => Locales::codes());

test('the eight translations differ from English where the words do', function () {
    $en = json_decode(file_get_contents(base_path('lang/en.json')), true);
    foreach (['id', 'de', 'ja', 'ru', 'zh-CN'] as $lang) {
        $data = json_decode(file_get_contents(base_path("lang/{$lang}.json")), true);
        foreach (['My bio pages', 'Manage', 'Settings', 'Export my bio pages', 'Shorten this URL', 'Add question', 'Copied'] as $key) {
            expect($data[$key])->not->toBe($en[$key], "{$lang}: {$key} is still English");
        }
    }
});
