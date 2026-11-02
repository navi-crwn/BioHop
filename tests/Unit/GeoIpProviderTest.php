<?php

use App\Services\GeoIP\Providers\AbstractApiProvider;
use App\Services\GeoIP\Providers\IpInfoProvider;
use App\Services\GeoIP\Providers\LocalMmdbProvider;
use App\Services\GeoIpService;

test('the local database is read from config and defaults to storage/app', function () {
    expect(config('services.geolite.city_db'))->toBe(storage_path('app/GeoLite2-City.mmdb'))
        ->and(config('services.geolite.asn_db'))->toBe(storage_path('app/GeoLite2-ASN.mmdb'));
});

test('the local provider is available only when the database file exists', function () {
    config(['services.geolite.city_db' => storage_path('app/no-such-file.mmdb')]);
    expect((new LocalMmdbProvider)->isAvailable())->toBeFalse();

    $file = tempnam(sys_get_temp_dir(), 'mmdb');
    config(['services.geolite.city_db' => $file]);
    expect((new LocalMmdbProvider)->isAvailable())->toBeTrue();
    unlink($file);

    config(['services.geolite.city_db' => null]);
    expect((new LocalMmdbProvider)->isAvailable())->toBeFalse();
});

test('a missing or broken database never breaks a lookup', function () {
    config(['services.geolite.city_db' => storage_path('app/no-such-file.mmdb')]);
    expect((new LocalMmdbProvider)->lookup('8.8.8.8'))->toBeNull();

    $file = tempnam(sys_get_temp_dir(), 'mmdb');
    file_put_contents($file, 'not a database');
    config(['services.geolite.city_db' => $file]);
    expect((new LocalMmdbProvider)->lookup('8.8.8.8'))->toBeNull();
    unlink($file);
});

test('the web provider keys come from config', function () {
    config(['services.ipinfo.api_key' => null, 'services.abstractapi.api_key' => null]);
    expect((new AbstractApiProvider)->isAvailable())->toBeFalse();
    config(['services.abstractapi.api_key' => 'key']);
    expect((new AbstractApiProvider)->isAvailable())->toBeTrue();
    expect(config('services.ipinfo'))->toHaveKey('api_key');
    expect(new IpInfoProvider)->toBeInstanceOf(IpInfoProvider::class);
});

test('the lookup service lists the local provider as unavailable when the file is absent', function () {
    config(['services.geolite.city_db' => storage_path('app/no-such-file.mmdb')]);
    $local = collect((new GeoIpService)->getProviders())->firstWhere('name', 'LocalMmdb');
    expect($local['available'])->toBeFalse();
});
