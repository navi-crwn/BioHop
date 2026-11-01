<?php

use App\Models\User;
use Hop\Bio\Http\Controllers\BioPageController;

test('a link with no known brand gets a readable name from its address', function (string $url, string $title) {
    expect(BioPageController::hostTitle($url))->toBe($title);
})->with([
    ['https://example.com/shop', 'Example'],
    ['https://nadia-draws.carrd.co', 'Nadia Draws'],
    ['https://www.studio_nusantara.id/about', 'Studio Nusantara'],
    ['https://shop.kopi.co.id', 'Kopi'],
    ['https://blog.example.com', 'Example'],
    ['not a url', 'Link'],
]);

test('the wizard says why Continue is greyed out', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('bio.create'))->assertOk()
        ->assertSee('Add a display name to continue.')->assertSee('Choose a free address to continue.');
});
