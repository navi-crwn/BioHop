<?php

return [
    // Off: the package registers no routes, views, migrations or policy; the host app hides its bio menus
    // and never queries the bio tables, so they need not exist.
    'enabled' => (bool) env('BIO_ENABLED', true),

    // Bio slugs (hel.ink/b/{slug}) for members; admin accounts may go down to 3.
    'slug_min_length' => (int) env('BIO_SLUG_MIN_LENGTH', 5),

    // Pages per account.
    'max_pages' => (int) env('BIO_MAX_PAGES', 3),

    // Seconds to wait for LinkHop's API when a block asks for a short link.
    'linkhop_timeout' => (int) env('BIO_LINKHOP_TIMEOUT', 8),

    // Bio slugs nobody can take (lowercase): the HOP family's own words, staff-looking names, brands.
    // The path prefixes l, b and api are always reserved (App\Support\ReservedSlugs).
    'reserved_slugs' => [
        'hop', 'hopid', 'hop-id', 'helink', 'hel-ink', 'hub', 'linkhop', 'biohop', 'pixelhop', 'notehop', 'imperium',
        'admin', 'administrator', 'root', 'system', 'moderator', 'staff', 'team', 'official', 'support', 'help', 'abuse',
        'report', 'dmca', 'security', 'verify', 'verified', 'legal', 'billing', 'owner', 'login', 'logout', 'register',
        'signup', 'signin', 'dashboard', 'settings', 'password', 'reset', 'confirm', 'email', 'sso', 'auth', 'oauth',
        'callback', 'health', 'status', 'contact', 'terms', 'privacy', 'faq', 'blog', 'news', 'shop', 'store', 'pricing',
        'plans', 'download', 'share', 'embed', 'sitemap', 'robots', 'assets', 'static', 'public', 'storage', 'upload',
        'uploads', 'avatar', 'profile', 'account', 'user', 'users', 'bio', 'link', 'links', 'analytics', 'qr', 'click',
        'null', 'undefined', 'none', 'anonymous', 'guest', 'test', 'demo', 'www', 'cdn', 'app', 'apps', 'dev',
        // Brands (anti-impersonation)
        'google', 'gmail', 'youtube', 'facebook', 'instagram', 'whatsapp', 'messenger', 'twitter', 'tiktok', 'linkedin',
        'pinterest', 'snapchat', 'telegram', 'microsoft', 'apple', 'icloud', 'amazon', 'netflix', 'spotify', 'twitch',
        'discord', 'slack', 'zoom', 'adobe', 'dropbox', 'paypal', 'stripe', 'cashapp', 'venmo', 'zelle',
        'wallet', 'crypto', 'bitcoin', 'ethereum', 'bank', 'banking', 'payment', 'invoice', 'receipt',
    ],
];
