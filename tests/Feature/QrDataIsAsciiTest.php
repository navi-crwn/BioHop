<?php

use App\Models\User;

/*
| The npm bundle of qr-code-styling writes one byte per character, so raw non-ASCII text in a QR decodes as garbage
| (shown with jsQR; LinkHop fixes it with binaryString because its /qr page encodes free text). BioHop encodes only
| the address of a bio page: the slug is [a-z0-9]+ and the share URL goes through the URL API (percent-encoded), so
| the bundle never sees a non-ASCII byte. This test keeps it so: if a QR ever carries free text, it must go through a
| UTF-8 byte string first (LinkHop resources/js/qr/payloads.js binaryString) and a read-back test must be added.
*/

test('every QR drawn in the browser encodes an address, never free text', function () {
    $files = array_merge(
        glob(base_path('packages/bio/resources/views/*.blade.php')),
        glob(base_path('packages/bio/resources/views/editor/*.blade.php')),
        glob(base_path('resources/views/**/*.blade.php')) ?: [],
    );
    $found = 0;
    foreach ($files as $file) {
        $src = (string) file_get_contents($file);
        preg_match_all('/new QRCodeStyling\(\{(.*?)\}\);?/s', $src, $blocks);
        foreach ($blocks[1] as $block) {
            if (! preg_match('/\bdata:\s*([^,\n]+),/', $block, $data)) {
                continue;
            }
            $found++;
            expect(trim($data[1]))->toBeIn(['url', "shareUrl('qr')"], basename($file).' encodes '.$data[1]);
        }
    }
    expect($found)->toBeGreaterThanOrEqual(5);
});

test('a bio slug is ASCII letters and digits only, so the address in a QR is ASCII', function () {
    $src = (string) file_get_contents(base_path('packages/bio/src/Http/Controllers/BioPageController.php'));
    expect($src)->toContain("'regex:/^[a-z0-9]+\$/'");

    $this->actingAs(User::factory()->create())
        ->postJson('/bio', ['title' => 'T', 'slug' => 'café'])
        ->assertStatus(422)->assertJsonValidationErrors('slug');
});
