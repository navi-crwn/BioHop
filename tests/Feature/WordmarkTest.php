<?php

test('the name is real text in the app typeface next to the mark, and the font files are vendored', function () {
    $html = $this->get('/about')->assertOk()->getContent();
    expect($html)->toContain('hop-name hop-name-biohop');
    expect(file_get_contents(public_path('hop-brand/hop-brand.css')))->toContain('biohop-name.woff2');
    foreach (['linkhop-name.woff2', 'biohop-name.woff2'] as $font) {
        expect(public_path('hop-brand/fonts/brand/'.$font))->toBeFile();
    }
});
