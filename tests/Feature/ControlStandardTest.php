<?php

/**
 * The HOP control standard (2026-11-09): one definition in resources/css/controls.css, imported last, then the coss surfaces (a utility left on a
 * control in a view cannot change it). The member's public bio page is exempt.
 */
test('controls.css holds the standard and then coss.css are the last imports', function () {
    $css = file_get_contents(resource_path('css/controls.css'));

    expect($css)
        ->toContain('--hop-ctl-radius: 8px')
        ->toContain('--hop-ctl-h: 36px')
        ->toContain('--hop-ctl-h-sm: 32px')
        ->toContain('--hop-ctl-h-lg: 40px')
        ->toContain('--hop-field-max: 28rem')
        ->toContain('--hop-hit: 44px');

    $imports = array_values(preg_grep('/^@import/', explode("\n", file_get_contents(resource_path('css/app.css')))));
    expect(array_slice($imports, -2))->toBe(["@import './controls.css';", "@import './coss.css';"]);
});
