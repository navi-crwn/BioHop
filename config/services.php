<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // GeoIP for click analytics (country only). The local database is tried first, then the free web APIs.
    // Put GeoLite2-City.mmdb (and optionally GeoLite2-ASN.mmdb) from MaxMind or DB-IP Lite in storage/app.
    'geolite' => [
        // false: never ask a web service; the country stays empty unless the local database answers.
        'web_providers' => (bool) env('GEOIP_WEB_PROVIDERS', true),
        'city_db' => env('GEOLITE_CITY_DB') ?: storage_path('app/GeoLite2-City.mmdb'),
        'asn_db' => env('GEOLITE_ASN_DB') ?: storage_path('app/GeoLite2-ASN.mmdb'),
    ],

    'ipinfo' => [
        'api_key' => env('IPINFO_API_KEY'),
    ],

    'abstractapi' => [
        'api_key' => env('ABSTRACTAPI_KEY'),
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret' => env('TURNSTILE_SECRET_KEY'),
    ],

    // Purge-by-URL of public bio pages at Cloudflare's edge when a page changes (Hop\Bio\Support\EdgeCache).
    // Both empty: pages still carry their cache headers and leave the edge when their TTL ends.
    'cloudflare' => [
        'zone_id' => env('CLOUDFLARE_ZONE_ID'),
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
    ],

];
