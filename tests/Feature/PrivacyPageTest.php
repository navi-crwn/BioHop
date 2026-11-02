<?php

use Hop\Ui\Locales;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const PRIVACY_OWNER_SENTENCE = 'The page owner chooses to add them and is responsible for them; BioHop only provides the platform.';

test('the privacy page says the page owner is responsible for pixels they add', function () {
    $this->get('/privacy')
        ->assertOk()
        ->assertSee(PRIVACY_OWNER_SENTENCE)
        ->assertSee('BioHop does not receive that data.')
        ->assertDontSee('No selling or sharing of data.');
});

test('the privacy page renders the same claim in Indonesian', function () {
    $this->get('/privacy?lang=id')
        ->assertOk()
        ->assertSee('lang="id"', false)
        ->assertSee('Pemilik halaman sendiri yang memilih untuk menambahkannya dan bertanggung jawab atasnya; BioHop hanya menyediakan platformnya.')
        ->assertSee('BioHop tidak menerima data tersebut.')
        ->assertDontSee(PRIVACY_OWNER_SENTENCE);
});

test('the privacy page is translated in every language, with the pixel and sharing wording', function (string $code) {
    $catalog = json_decode(file_get_contents(base_path("lang/{$code}.json")), true);
    $keys = array_values(array_filter(array_keys($catalog), fn ($key) => str_starts_with($key, 'A page owner can add Google Analytics') || str_starts_with($key, 'We do not sell data')));

    expect($keys)->toHaveCount(2);

    $response = $this->get('/privacy?lang='.$code)->assertOk()->assertSee('lang="'.$code.'"', false);

    foreach ($keys as $key) {
        $response->assertSee($catalog[$key]);
        if ($code !== 'en') {
            expect($catalog[$key])->not->toBe($key);
        }
    }
})->with(fn () => Locales::codes());
