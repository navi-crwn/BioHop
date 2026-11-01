<?php

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Hop\Bio\Support\BioFonts;

test('the public page serves its own font from our domain and asks nobody else', function () {
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'fontp', 'title' => 'Fonts', 'is_published' => true, 'font_family' => 'poppins']);
    $html = $this->get('/b/fontp')->assertOk()->getContent();
    $asset = bio_asset_origin();
    expect($html)->toContain('rel="preload" as="font" type="font/woff2" href="'.$asset.'/fonts/bio/poppins-')
        ->toContain("font-family: 'Poppins'")->toContain('url('.$asset.'/fonts/bio/poppins-')
        ->not->toContain('fonts.googleapis.com')->not->toContain('fonts.gstatic.com')->not->toContain('fonts.bunny.net');
    $preload = BioFonts::forFamily('Poppins')['preload'];
    expect(is_file(public_path(parse_url($preload, PHP_URL_PATH))))->toBeTrue();
});

test('every font the editor offers has files on disk, and system fonts need none', function () {
    foreach (['Inter', 'Poppins', 'Roboto', 'Montserrat', 'Nunito', 'Open Sans', 'Lato', 'Raleway', 'Quicksand', 'DM Sans', 'Manrope', 'Plus Jakarta Sans', 'Lexend', 'Space Grotesk', 'Work Sans', 'Oswald', 'Playfair Display', 'Merriweather', 'Source Sans Pro', 'Ubuntu'] as $family) {
        $font = BioFonts::forFamily($family);
        expect($font)->not->toBeNull("{$family} is missing");
        expect(is_file(public_path(parse_url($font['preload'], PHP_URL_PATH))))->toBeTrue("{$family} file is missing");
    }
    expect(BioFonts::forFamily('Georgia'))->toBeNull();
    $user = User::factory()->create();
    BioPage::create(['user_id' => $user->id, 'slug' => 'fontq', 'title' => 'Serif', 'is_published' => true, 'font_family' => 'serif']);
    $this->get('/b/fontq')->assertOk()->assertDontSee('as="font"', false);
});
