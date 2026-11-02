<?php

function sessionConfigWith(?string $appUrl, ?string $secure): mixed
{
    $old = [getenv('APP_URL'), getenv('SESSION_SECURE_COOKIE')];
    foreach (['APP_URL' => $appUrl, 'SESSION_SECURE_COOKIE' => $secure] as $key => $value) {
        $value === null ? putenv($key) : putenv("{$key}={$value}");
        unset($_ENV[$key], $_SERVER[$key]);
    }
    try {
        return (require config_path('session.php'))['secure'];
    } finally {
        foreach (['APP_URL' => $old[0], 'SESSION_SECURE_COOKIE' => $old[1]] as $key => $value) {
            $value === false ? putenv($key) : putenv("{$key}={$value}");
        }
    }
}

test('the session cookie is secure by default on an https address', function () {
    expect(sessionConfigWith('https://bio.hel.ink', null))->toBeTrue();
});

test('the session cookie is not forced on a plain http address', function () {
    expect(sessionConfigWith('http://localhost:8002', null))->toBeFalse()
        ->and(sessionConfigWith(null, null))->toBeFalse();
});

test('an explicit SESSION_SECURE_COOKIE wins over the address', function () {
    expect(sessionConfigWith('https://bio.hel.ink', 'false'))->toBeFalse()
        ->and(sessionConfigWith('http://localhost', 'true'))->toBeTrue();
});
