<?php

return [
    // Off: the package registers no routes, views, migrations or policy; the host app hides its bio menus
    // and never queries the bio tables, so they need not exist.
    'enabled' => (bool) env('BIO_ENABLED', true),

    // Bio slugs (hel.ink/b/{slug}) for members; admin accounts may go down to 3.
    'slug_min_length' => (int) env('BIO_SLUG_MIN_LENGTH', 5),

    // Pages per account.
    'max_pages' => (int) env('BIO_MAX_PAGES', 3),

    // Blocks per page.
    'max_blocks' => (int) env('BIO_MAX_BLOCKS', 100),

    // Custom CSS on a page. Off: new CSS is refused and what pages already have is not sent to visitors.
    'allow_custom_css' => (bool) env('BIO_ALLOW_CUSTOM_CSS', true),

    // The admin panel can change these four at run time (App\Support\BioLimits); these are the defaults.

    'upload_min_free_mb' => (int) env('BIO_UPLOAD_MIN_FREE_MB', 1024),
    'upload_min_free_percent' => (int) env('BIO_UPLOAD_MIN_FREE_PERCENT', 5),

    // Seconds to wait for LinkHop's API when a block asks for a short link.
    'linkhop_timeout' => (int) env('BIO_LINKHOP_TIMEOUT', 8),

    // "Choose from PixelHop" in the editor: a picture the member already uploaded to PixelHop, for the image block,
    // the carousel, the avatar, the cover and the favicon. The file stays in PixelHop; the page keeps its public
    // address. Off until PixelHop's GET /api/v1/me/media is deployed; off, the editor offers uploads only.
    'pixelhop_picker' => (bool) env('BIO_PIXELHOP_PICKER', false),
    // Where the server-to-server call goes (empty: hop.apps.pixelhop.url). The browser never talks to PixelHop.
    'pixelhop_url' => env('BIO_PIXELHOP_URL'),
    // Hosts a PixelHop file address may use (https). A picked address on any other host is refused when saving.
    // For a PixelHop on your own machine, write the origin in full: http://localhost:8090.
    'pixelhop_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env('BIO_PIXELHOP_HOSTS', 'pixel.hel.ink,p.hel.ink'))))),
    // Seconds to wait for PixelHop's list before the editor is told to upload instead.
    'pixelhop_timeout' => (int) env('BIO_PIXELHOP_TIMEOUT', 6),
    // "Choose from NoteHop" for a link block: one of the member's public notes that has a read-only link. NoteHop
    // never lists an encrypted note (its key stays in the member's browser). Off until NoteHop's
    // GET /api/v1/me/notes is deployed with this app in NOTEHOP_NOTES_CLIENTS.
    'notehop_picker' => (bool) env('BIO_NOTEHOP_PICKER', false),
    // NoteHop's address: the server-to-server call goes there and a picked note must be on it.
    'notehop_url' => env('BIO_NOTEHOP_URL', 'https://note.hel.ink'),
    'notehop_timeout' => (int) env('BIO_NOTEHOP_TIMEOUT', 6),
    // Signs the picker calls to PixelHop and NoteHop (32+ characters). A secret of their own: those apps hold it
    // instead of this app's SSO secret, so a leak there cannot forge a sign-in here. Empty: the SSO secret signs.
    'picker_secret' => env('HOP_PICKER_SECRET'),

    // Bio slugs nobody can take (lowercase): the HOP family's own words, staff-looking names, brands.
    // The path prefixes l, b and api are always reserved (App\Support\ReservedSlugs).
    'reserved_slugs' => [
        'hop', 'hopid', 'hop-id', 'helink', 'hel-ink', 'hub', 'linkhop', 'biohop', 'pixelhop', 'notehop', 'filehop', 'payhop', 'pay', 'imperium',
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
