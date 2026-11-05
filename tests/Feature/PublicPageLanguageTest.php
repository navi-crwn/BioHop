<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;

function languagePage(array $attributes = []): BioPage
{
    return BioPage::create(['user_id' => User::factory()->create()->id, 'slug' => 'langpage', 'title' => 'T', 'is_published' => true] + $attributes);
}

test('the public page names the language it is shown in', function (string $query, string $lang) {
    languagePage();
    $this->get('/b/langpage?lang='.$query)->assertOk()->assertSee('<html lang="'.$lang.'"', false);
})->with([['ru', 'ru'], ['de', 'de'], ['zh-CN', 'zh-CN'], ['ja', 'ja'], ['pt-BR', 'pt-BR'], ['en', 'en']]);

test('the language is English when nothing asks for another one', function () {
    languagePage();
    $this->get('/b/langpage')->assertOk()->assertSee('<html lang="en"', false);
});

test('the password page names its language too', function () {
    languagePage(['password_enabled' => true, 'password' => bcrypt('secret-word')]);
    $this->get('/b/langpage?lang=de')->assertOk()->assertSee('<html lang="de"', false);
});

test('the editor preview names its language too', function () {
    $page = languagePage();
    $this->actingAs($page->user)->get('/bio/langpage/preview?lang=de')->assertOk()->assertSee('<html lang="de"', false);
});
