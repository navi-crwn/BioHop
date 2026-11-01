<?php

use App\Services\UserAgentService;
use Illuminate\Support\Facades\Cache;

test('a parsed user agent is kept in the cache so the next request does not parse it again', function () {
    $ua = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Mobile Safari/537.36';
    $key = 'ua:'.sha1($ua);
    Cache::forget($key);
    $first = (new UserAgentService)->parse($ua);
    expect(Cache::has($key))->toBeTrue()->and($first)->toMatchArray(['device' => 'Mobile', 'bot' => false]);
    // a new service instance (a new request) reads it back unchanged
    expect((new UserAgentService)->parse($ua))->toBe($first);
    expect((new UserAgentService)->parse(null))->toMatchArray(['device' => 'Unknown']);
});
