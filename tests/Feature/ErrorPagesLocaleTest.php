<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Hop\Ui\Locales;
use Illuminate\Support\Facades\Route;

// The 404 / 429 / 500 pages speak the visitor's language (?lang= or the hop_lang cookie), like the rest of the app.

beforeEach(function () {
    Route::get('/__error/{code}', fn (int $code) => abort($code))->middleware('web');
});

dataset('locales', fn () => Locales::codes());

test('the 404 page of an unknown address follows the language in ?lang=', function (string $locale) {
    $html = $this->get('/no-such-page-at-all?lang='.$locale)->assertNotFound()->getContent();
    app()->setLocale($locale);

    expect($html)->toContain('<html lang="'.$locale.'"')
        ->toContain(e(__('Link not found')))
        ->toContain(e(__('Back to homepage')));
    if ($locale !== 'en') {
        expect($html)->not->toContain('>Link not found<')->not->toContain('>Back to homepage<');
    }
})->with('locales');

test('the 404 page follows the language cookie', function () {
    $html = $this->withUnencryptedCookie('hop_lang', 'de')->get('/no-such-page-at-all')->assertNotFound()->getContent();
    app()->setLocale('de');

    expect($html)->toContain('<html lang="de"')->toContain(e(__('Back to homepage')))->not->toContain('>Back to homepage<');
});

test('the public bio not-found and unpublished pages follow the language in ?lang=', function (string $locale) {
    $owner = User::factory()->create();
    BioPage::create(['user_id' => $owner->id, 'slug' => 'draftone', 'title' => 'Draft', 'is_published' => false]);

    foreach (['/b/nothing-here-at-all', '/b/draftone'] as $url) {
        $html = $this->get($url.'?lang='.$locale)->assertNotFound()->getContent();
        app()->setLocale($locale);

        expect($html)->toContain('<html lang="'.$locale.'"')
            ->toContain(e(__('Go to Homepage')))
            ->toContain(e(__('Create Your Bio Page')));
        if ($locale !== 'en') {
            expect($html)->not->toContain('Go to Homepage')->not->toContain('Create Your Bio Page')->not->toContain('Powered by BioHop')->not->toContain('Looking for something specific?');
        }
    }
})->with('locales');

test('the 429 page follows the language in ?lang=', function (string $locale) {
    $html = $this->get('/__error/429?lang='.$locale)->assertStatus(429)->getContent();
    app()->setLocale($locale);

    expect($html)->toContain('<html lang="'.$locale.'"')
        ->toContain(e(__('Please wait a moment, then refresh this page or open it again.')))
        ->toContain(e(__('Try again')));
    if ($locale !== 'en') {
        expect($html)->not->toContain('Please wait a moment')->not->toContain('>Try again<')->not->toContain('>Go back<');
    }
})->with('locales');

test('the 500 page follows the language in ?lang=', function (string $locale) {
    config(['app.debug' => false]);
    $html = $this->get('/__error/500?lang='.$locale)->assertStatus(500)->getContent();
    app()->setLocale($locale);

    expect($html)->toContain('<html lang="'.$locale.'"')
        ->toContain(e(__('Something went wrong')))
        ->toContain(e(__('Go to Homepage')));
    if ($locale !== 'en') {
        expect($html)->not->toContain('Something went wrong')->not->toContain('Go to Homepage');
    }
})->with('locales');

test('every new error-page string has a translation in each of the nine languages', function () {
    $keys = [
        'Page not found', 'Link not found', 'Back to homepage', 'Too Many Requests', 'Try again', 'Reloading…', 'You can try again now.',
        'You can try again in :seconds seconds.', 'Something went wrong', 'The page could not be loaded.', 'Go to Homepage', 'Need help? Contact us at',
        'Please wait a moment, then refresh this page or open it again.',
        'Too many requests or attempts came from your connection in a short time. This helps keep BioHop stable for everyone.',
        'The page you opened doesn’t exist anymore, is unpublished, or never was. Double-check the address or head back home.',
        'Try again in a moment. If it keeps happening, send a message to the address below.',
        'Page Not Available', 'This bio page is currently not published. The owner may have temporarily disabled it.',
        "The bio page :slug doesn't exist or may have been removed.", 'Create Your Bio Page', 'Looking for something specific?',
        'Search bio pages...', 'Search', 'Powered by BioHop',
    ];
    foreach (Locales::codes() as $locale) {
        $file = json_decode((string) file_get_contents(base_path("lang/$locale.json")), true);
        foreach ($keys as $key) {
            expect($file)->toHaveKey($key);
            if ($locale !== 'en') {
                expect($file[$key])->not->toBe($key);
            }
            expect(substr_count($file[$key], ':seconds').substr_count($file[$key], ':slug'))->toBe(substr_count($key, ':seconds').substr_count($key, ':slug'));
        }
    }
});
