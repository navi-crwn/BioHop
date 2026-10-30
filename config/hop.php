<?php

/*
|--------------------------------------------------------------------------
| HOP: the family of apps behind hel.ink
|--------------------------------------------------------------------------
| This copy is BioHop's (`current` = biohop). The hub (hub.hel.ink) signs
| people in (SSO tickets, hop/auth) and every app shares its database.
| hop/ui reads `apps` to draw the cross-app navigation; the shortener door
| in the bio editor reads `apps.linkhop.url`. Keep the `apps` list the same
| as the hub's config/hop.php.
*/
return [
    'brand' => env('HOP_BRAND', 'HOP'),
    'tagline' => env('HOP_TAGLINE', 'Hop Easy Link'),

    // Which app this is (a key of `apps`, or "hub").
    'current' => 'biohop',

    // Where the public bio pages are reached (hel.ink/b/{slug}): PUBLIC_URL, default APP_URL. See public_url().
    'public_url' => rtrim((string) (env('PUBLIC_URL') ?: env('APP_URL', 'http://localhost')), '/'),
    // The hub (HOP ID). hop/auth reads the same value as hop-auth.hub_url.
    'hub_url' => rtrim((string) env('HOP_HUB_URL', 'https://hub.hel.ink'), '/'),

    // Imperium, the admin control panel. Admins who sign in at the hub land there
    // (the hub dashboard stays one click away). citadel.hel.ink works from anywhere
    // through Cloudflare Access; imperium.hel.ink only over Tailscale.
    'control_url' => rtrim((string) env('HOP_CONTROL_URL', ''), '/'),
    'control_name' => env('HOP_CONTROL_NAME', 'Control center'),

    // The apps, in launcher order. `url` empty: the app is not configured and is
    // skipped everywhere (launcher, SSO, watchdog).
    'apps' => [
        'linkhop' => [
            'name' => 'LinkHop',
            'tagline' => 'Short links, QR codes and click analytics.',
            'url' => env('LINKHOP_URL', 'https://link.hel.ink'),
            'public_prefix' => '/l/',
            'callback' => env('LINKHOP_SSO_CALLBACK', '/auth/sso/callback'),
            'logout' => env('LINKHOP_LOGOUT_PATH', '/auth/sso/logout'),
            'export' => '/settings#backup',
            'health' => '/up',
            'check' => env('LINKHOP_CHECK_URL'),
            'secret' => env('HOP_SSO_SECRET_LINKHOP'),
            'icon' => 'link',
            'color' => 'indigo',
            'repo' => 'https://github.com/navi-crwn/LinkHop',
            'enabled' => (bool) env('LINKHOP_ENABLED', true),
        ],
        'biohop' => [
            'name' => 'BioHop',
            'tagline' => 'One link-in-bio page for everything you do.',
            'url' => env('BIOHOP_URL', 'https://bio.hel.ink'),
            'public_prefix' => '/b/',
            'callback' => env('BIOHOP_SSO_CALLBACK', '/auth/sso/callback'),
            'logout' => env('BIOHOP_LOGOUT_PATH', '/auth/sso/logout'),
            'export' => '/settings#export',
            'health' => '/up',
            'check' => env('BIOHOP_CHECK_URL'),
            'secret' => env('HOP_SSO_SECRET_BIOHOP'),
            'icon' => 'bio',
            'color' => 'fuchsia',
            'repo' => 'https://github.com/navi-crwn/BioHop',
            'enabled' => (bool) env('BIOHOP_ENABLED', true),
        ],
        'pixelhop' => [
            'name' => 'PixelHop',
            'tagline' => 'Image hosting with albums and direct links.',
            'url' => env('PIXELHOP_URL', 'https://p.hel.ink'),
            'public_prefix' => null,
            'callback' => env('PIXELHOP_SSO_CALLBACK', '/auth/helink/callback'),
            'start' => env('PIXELHOP_SSO_START', '/auth/helink'),
            'logout' => env('PIXELHOP_LOGOUT_PATH'),
            'export' => null,
            'health' => '/up',
            'check' => env('PIXELHOP_CHECK_URL'),
            'secret' => env('HOP_SSO_SECRET_PIXELHOP', env('PIXELHOP_SSO_SECRET')),
            'icon' => 'image',
            'color' => 'sky',
            'repo' => null,
            'enabled' => (bool) env('PIXELHOP_ENABLED', true),
        ],
    ],

    // Watchdog (hop:watch): alert after this many failures in a row.
    'watch' => [
        'failures_before_alert' => 3,
        'timeout' => 8,
        'keep_days' => 7,
        'alert_email' => env('WATCH_ALERT_EMAIL', env('ADMIN_EMAIL')),
        'telegram_token' => env('TELEGRAM_BOT_TOKEN'),
        'telegram_chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

    // Days a released username stays unavailable to other people.
    'username_hold_days' => 90,

    // Usernames nobody can take: site routes of every app, staff-looking names,
    // and the brands. Lowercase. Prefixes end with "-".
    'reserved_usernames' => [
        'hop', 'hopid', 'hop-id', 'imperium', 'citadel', 'linkhop', 'pixelhop', 'notehop', 'remotehop', 'biohop',
        'helink', 'hel', 'hel-ink', 'hub', 'admin', 'administrator', 'root', 'system', 'moderator', 'mod',
        'staff', 'team', 'official', 'support', 'help', 'abuse', 'report', 'dmca', 'security', 'verify',
        'verified', 'legal', 'billing', 'owner', 'sysadmin', 'webmaster', 'postmaster', 'hostmaster',
        'noreply', 'no-reply', 'mailer-daemon',
        'api', 'app', 'apps', 'about', 'settings', 'login', 'logout', 'register', 'signup', 'signin',
        'dashboard', 'tools', 'docs', 'static', 'assets', 'public', 'storage', 'avatar', 'avatars',
        'profile', 'account', 'accounts', 'user', 'users', 'u', 'b', 'l', 'n', 'i', 'new', 'home', 'explore',
        'search', 'upload', 'uploads', 'album', 'albums', 'image', 'images', 'note', 'notes', 'link',
        'links', 'bio', 'folders', 'tags', 'analytics', 'password', 'reset', 'confirm', 'email', 'sso',
        'auth', 'oauth', 'callback', 'health', 'healthz', 'metrics', 'status', 'contact', 'terms', 'privacy',
        'faq', 'blog', 'news', 'shop', 'store', 'pricing', 'plans', 'download', 'downloads', 'share',
        'embed', 'oembed', 'sitemap', 'robots', 'manifest', 'sw', 'offline', 'raw', 'mock', 'nearby',
        'vault', 'diff', 'in', 'inbox', 'live', 'render', 'i18n', 'legacy', 'announcements', 'launcher',
        'null', 'undefined', 'none', 'nobody', 'anonymous', 'guest', 'me', 'you', 'everyone', 'test', 'demo',
    ],
    'reserved_username_prefixes' => ['hop-', 'hop_', 'admin-', 'admin_', 'official-', 'official_', 'staff-', 'staff_',
        'support-', 'support_', 'team-', 'team_', 'security-', 'security_', 'verify-', 'verified-', 'linkhop-',
        'pixelhop-', 'notehop-', 'remotehop-', 'biohop-', 'helink-', 'imperium-'],
];
