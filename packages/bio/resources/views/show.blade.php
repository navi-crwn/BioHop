@php
    use App\Helpers\SocialUrlHelper;
@endphp
<!DOCTYPE html>
<html lang="en" @class(['lite' => request()->header('Save-Data') === 'on'])>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script nonce="{{ Vite::cspNonce() }}">
        // Lite mode: a visitor who asked to save data, a phone with little memory or few cores, or ?lite=1 gets a calm page
        // (no background animation, entrance effects or blur). ?lite=0 turns it off.
        (function () {
            var d = document.documentElement, c = navigator.connection || {}, q = /[?&]lite=([01])/.exec(location.search);
            var low = c.saveData === true || (navigator.deviceMemory && navigator.deviceMemory <= 2) || (navigator.hardwareConcurrency && navigator.hardwareConcurrency <= 2)
                || (window.matchMedia && matchMedia('(prefers-reduced-data: reduce)').matches);
            if (q ? q[1] === '1' : low) d.classList.add('lite'); else if (q) d.classList.remove('lite');
        })();
    </script>
    @if(request()->attributes->get('hop.embed'))
        {{-- Shown inside the BioHop home: links open in a new tab and the copy stays out of search. --}}
        <base target="_blank">
        <meta name="robots" content="noindex">
    @endif
    <title>{{ $bioPage->seo_title ?? $bioPage->title }} | By LinkHop</title>
    <meta name="description" content="{{ $bioPage->seo_description ?? $bioPage->bio ?? 'Check out my links' }}">
    @if(!($bioPage->allow_indexing ?? true))
        <meta name="robots" content="noindex, nofollow">
    @endif
    @php
        $pageFile = fn ($v) => $v ? (str_starts_with($v, 'http') || str_starts_with($v, '/') ? $v : Storage::disk('public')->url($v)) : null;
        $cardSource = trim((string) ($bioPage->background_value ?: $bioPage->background_gradient ?: $bioPage->background_color ?: ($themeData['background'] ?? '')));
        preg_match_all('/#[0-9a-fA-F]{6}\b/', $cardSource, $cardMatches);
        $cardColors = array_slice($cardMatches[0] ?? [], 0, 3) ?: ['#6366f1', '#a855f7'];
        $srcTag = in_array(request()->query('src'), \Hop\Bio\Http\Controllers\PublicBioController::SOURCES, true) ? request()->query('src') : null;
        $clickHref = fn ($publicId) => route('bio.public.click', ['bioLink' => $publicId] + ($srcTag ? ['s' => $srcTag] : []));
        $ogImage = $pageFile($bioPage->og_image) ?: ($bioPage->avatar_url ? Storage::disk('public')->url($bioPage->avatar_url) : null);
    @endphp
    <link rel="icon" href="{{ $pageFile($bioPage->favicon) ?: route('brand.favicon') }}">
    <link rel="manifest" href="{{ route('bio.public.manifest', $bioPage->slug) }}">
    @if($bioPage->avatar_url)<link rel="apple-touch-icon" href="{{ Storage::disk('public')->url($bioPage->avatar_url) }}">@endif
    <meta name="apple-mobile-web-app-title" content="{{ \Illuminate\Support\Str::limit($bioPage->title, 12, '') }}">
    <meta property="og:title" content="{{ $bioPage->seo_title ?? $bioPage->title }} | By LinkHop">
    <meta property="og:description" content="{{ $bioPage->seo_description ?? $bioPage->bio ?? 'Check out my links' }}">
    @if($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif
    <meta property="og:url" content="{{ public_url('b/'.$bioPage->slug) }}">
    <meta property="og:type" content="profile">
    @vite(['resources/css/public.css', 'resources/js/public.js'])
    @php
        $themes = config('bio-themes.presets');
        $currentTheme = $bioPage->theme ?? 'none';
        $hasTheme = $currentTheme && $currentTheme !== 'none' && isset($themes[$currentTheme]);
        $themeData = $hasTheme ? $themes[$currentTheme] : null;
        $isDarkTheme = $hasTheme && in_array($currentTheme, ['dark', 'minimal-dark', 'galaxy', 'neon', 'cyberpunk', 'aurora', 'midnight', 'matrix']);
        $bgStyle = '';
        $hasCustomBackground = $bioPage->background_type === 'image' && ($bioPage->background_image || $bioPage->background_value);
        if ($hasCustomBackground) {
            $bgImage = $bioPage->background_image ?: $bioPage->background_value;
            if (str_starts_with($bgImage, 'http') || str_starts_with($bgImage, '/storage/')) {
                $bgUrl = $bgImage;
            } else {
                $bgUrl = Storage::disk('public')->url($bgImage);
            }
            $bgStyle = "background: url('{$bgUrl}') center/cover fixed;";
        } elseif ($hasTheme && $themeData) {
            $bg = $themeData['background'];
            if (str_starts_with($bg, 'linear-gradient') || str_starts_with($bg, 'radial-gradient')) {
                $bgStyle = "background: {$bg};";
            } else {
                $bgStyle = "background-color: {$bg};";
            }
        } elseif ($bioPage->background_type === 'gradient' && $bioPage->background_value) {
            $bgStyle = "background: {$bioPage->background_value};";
        } elseif (($bioPage->background_type === 'solid' || $bioPage->background_type === 'color') && $bioPage->background_value) {
            $bgStyle = "background: {$bioPage->background_value};";
        } else {
            $bgStyle = "background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);";
        }
        if ($hasTheme && $themeData) {
            $titleColor = $themeData['text'];
            $bioColor = $themeData['bio'];
            $linkTextColor = $themeData['link_text'];
            $linkBgColor = $themeData['link_bg'];
            $themeLinkBorder = $themeData['link_border'] ?? null;
        } else {
            $titleColor = $bioPage->title_color ?: config('bio-themes.defaults.title');
            $bioColor = $bioPage->bio_color ?: config('bio-themes.defaults.bio');
            $linkTextColor = $bioPage->link_text_color ?: config('bio-themes.defaults.link_text');
            $linkBgColor = $bioPage->link_bg_color ?: config('bio-themes.defaults.link_bg');
            $themeLinkBorder = null;
        }
        $themeGlowColors = config('bio-themes.glow');
        $themeHueShifts = config('bio-themes.hue');
        $themeGlowColor = $themeGlowColors[$currentTheme] ?? 'rgba(99, 102, 241, 0.4)';
        $themeHueShift = $themeHueShifts[$currentTheme] ?? '30deg';
        $fontFamily = match($bioPage->font_family ?? 'inter') {
            'inter' => "'Inter', sans-serif",
            'poppins' => "'Poppins', sans-serif",
            'roboto' => "'Roboto', sans-serif",
            'montserrat' => "'Montserrat', sans-serif",
            'nunito' => "'Nunito', sans-serif",
            'open-sans' => "'Open Sans', sans-serif",
            'lato' => "'Lato', sans-serif",
            'raleway' => "'Raleway', sans-serif",
            'source-sans-pro' => "'Source Sans Pro', sans-serif",
            'ubuntu' => "'Ubuntu', sans-serif",
            'quicksand' => "'Quicksand', sans-serif",
            'work-sans' => "'Work Sans', sans-serif",
            'dm-sans' => "'DM Sans', sans-serif",
            'manrope' => "'Manrope', sans-serif",
            'plus-jakarta-sans' => "'Plus Jakarta Sans', sans-serif",
            'lexend' => "'Lexend', sans-serif",
            'space-grotesk' => "'Space Grotesk', sans-serif",
            'oswald' => "'Oswald', sans-serif",
            'playfair-display', 'playfair' => "'Playfair Display', serif",
            'merriweather' => "'Merriweather', serif",
            'serif' => 'Georgia, serif',
            'mono' => 'ui-monospace, monospace',
            default => "'Inter', sans-serif"
        };
        // The page's own font is inlined from public/fonts/bio (see BioFonts); nothing is asked from Google.
        $fontName = str_replace(' ', '+', trim(explode(',', $fontFamily)[0], "' "));
        $blockShape = match($bioPage->block_shape ?? 'rounded-sm') {
            'square' => 'border-radius: 0;',
            'pill' => 'border-radius: 9999px;',
            default => 'border-radius: 0.75rem;'
        };
        // Same default and scale as the .shadow-* classes the preview uses (M7).
        $blockShadow = match($bioPage->block_shadow ?? 'md') {
            'none' => 'box-shadow: none;',
            'sm' => 'box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);',
            'lg' => 'box-shadow: 0 10px 20px -3px rgba(0, 0, 0, 0.15);',
            'xl' => 'box-shadow: 0 25px 35px -5px rgba(0, 0, 0, 0.2);',
            default => 'box-shadow: 0 4px 8px -1px rgba(0, 0, 0, 0.12);'
        };
        $cardBg = 'transparent';
        $blockBg = $linkBgColor;
        $blockBorder = $isDarkTheme ? 'rgba(71, 85, 105, 0.3)' : 'rgba(0, 0, 0, 0.05)';
        $socialBg = $isDarkTheme ? 'rgba(51, 65, 85, 0.8)' : 'rgba(241, 245, 249, 0.9)';
        $contentBlockBg = $isDarkTheme ? 'rgba(255, 255, 255, 0.08)' : 'rgba(255, 255, 255, 0.7)';
        $contentBlockBorder = $isDarkTheme ? 'rgba(255, 255, 255, 0.15)' : 'rgba(0, 0, 0, 0.1)';
        $contentBlockHeaderBg = $isDarkTheme ? 'rgba(255, 255, 255, 0.05)' : 'rgba(241, 245, 249, 0.5)';
        $contentBlockText = $titleColor;
        $contentBlockMuted = $bioColor;
        $countdownBg = "linear-gradient(135deg, {$linkBgColor}, {$linkBgColor}cc)";
        $countdownText = $linkTextColor;
        $layout = $bioPage->layout ?? 'centered';
        $containerAlign = match($layout) {
            'left' => 'items-start',
            'wide' => 'items-center',
            default => 'items-center'
        };
        $maxWidth = match($layout) {
            'wide' => 'max-w-2xl',
            default => 'max-w-md'
        };
        $headerBgColor = $bioPage->header_bg_color ?? '';
        $headerLayout = $bioPage->headerLayout();
        $avatarSrc = $bioPage->avatar_url ? Storage::disk('public')->url($bioPage->avatar_url) : null;
        // hero and cutout are built around the picture: without one they fall back to classic
        if (! $avatarSrc && in_array($headerLayout, ['hero', 'cutout'], true)) {
            $headerLayout = 'classic';
        }
        $coverSrc = $bioPage->cover_image ? (str_starts_with($bioPage->cover_image, 'http') || str_starts_with($bioPage->cover_image, '/') ? $bioPage->cover_image : Storage::disk('public')->url($bioPage->cover_image)) : null;
        $avatarRadius = $bioPage->avatar_shape === 'rounded-sm' ? '16px' : ($bioPage->avatar_shape === 'square' ? '0' : '50%');
        $showHeaderBg = !$hasTheme && $layout === 'wide' && $headerBgColor;
        $isLightColor = function($color) {
            if (!$color) return false;
            $r = $g = $b = 0;
            if (preg_match('/rgba?\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/', $color, $matches)) {
                $r = (int)$matches[1];
                $g = (int)$matches[2];
                $b = (int)$matches[3];
            }
            elseif (preg_match('/^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $color)) {
                $hex = ltrim($color, '#');
                if (strlen($hex) === 3) {
                    $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
                }
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
            }
            elseif (strtolower($color) === 'white' || strtolower($color) === '#fff' || strtolower($color) === '#ffffff') {
                return true;
            }
            elseif (strtolower($color) === 'black' || strtolower($color) === '#000' || strtolower($color) === '#000000') {
                return false;
            }
            else {
                return true;
            }
            $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
            return $luminance > 0.5;
        };
        $isInternalUrl = function($url) {
            if (!$url) return false;
            $internalDomains = [
                'hel.ink',
                'hel-ink.com', 
                'helink.id',
                parse_url(config('app.url'), PHP_URL_HOST),
                public_host(),
                request()->getHost(),
            ];
            try {
                $host = parse_url($url, PHP_URL_HOST);
                if (!$host) return false;
                $host = strtolower(preg_replace('/^www\./', '', $host));
                foreach ($internalDomains as $domain) {
                    if ($domain && (strtolower($host) === strtolower($domain) || str_ends_with($host, '.' . strtolower($domain)))) {
                        return true;
                    }
                }
                return false;
            } catch (\Exception $e) {
                return false;
            }
        };
        $getLinkRel = function($url) use ($isInternalUrl) {
            return $isInternalUrl($url) ? 'noopener' : 'nofollow noopener noreferrer';
        };
    @endphp
    @php $pageFont = \Hop\Bio\Support\BioFonts::forFamily(str_replace('+', ' ', (string) $fontName)); @endphp
    @if($pageFont)
    @if($pageFont['preload'])<link rel="preload" as="font" type="font/woff2" href="{{ $pageFont['preload'] }}" crossorigin>@endif
    <style>{!! $pageFont['css'] !!}</style>
    @endif
    <style>
        .bio-container {
            {!! \Hop\Safety\SafeContent::css($bgStyle) !!}
            font-family: {!! $fontFamily !!};
            /* Headers, FAQ and contact cards inherit this, so they stay readable on dark themes. */
            color: {{ $titleColor }};
            min-height: 100vh;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .bio-container::-webkit-scrollbar {
            display: none;
        }
        .bio-card {
            background: {{ $cardBg }};
            backdrop-filter: blur(10px);
        }
        .bio-title {
            color: {{ $titleColor }};
            max-width: 100%;
            overflow-wrap: anywhere;
        }
        .bio-bio {
            color: {{ $bioColor }};
        }
        .bio-block {
            background: {{ $blockBg }};
            border: 1px solid {{ $blockBorder }};
            color: {{ $linkTextColor }};
            {!! $blockShape !!}
            {!! $blockShadow !!}
        }
        .bio-block:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 25px -5px rgb(0 0 0 / 0.15);
        }
        /* Same frame as the editor preview's .avatar-wrapper. */
        .bio-avatar {
            border: 3px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .social-icon-btn {
            background: {{ $socialBg }};
        }
        .social-icon-btn:hover {
            background: {{ $isDarkTheme ? 'rgba(71, 85, 105, 0.9)' : 'rgba(226, 232, 240, 1)' }};
        }
        .social-icon {
            color: {{ $isDarkTheme ? '#f1f5f9' : '#475569' }};
        }
        /* Same colour as the page's own text, so it reads on any theme or wallpaper. */
        .powered-link {
            color: {{ $bioColor }};
            opacity: .8;
        }
        .powered-link:hover {
            opacity: 1;
        }
        /* Header layouts: classic (small avatar), hero (picture fills the top), banner (cover strip), cutout (big fading picture). */
        .bio-head { position: relative; }
        .bio-head-hero { margin: -2rem -2rem 1.25rem; border-radius: 1.5rem 1.5rem 1.25rem 1.25rem; overflow: hidden; aspect-ratio: 4 / 5; max-height: 460px; background: #0f172a; }
        .bio-head-hero .bio-hero-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .bio-head-hero .bio-hero-text { position: absolute; inset: auto 0 0 0; padding: 4rem 1.5rem 1.5rem; background: linear-gradient(180deg, rgba(15, 23, 42, 0) 0%, rgba(15, 23, 42, .55) 45%, rgba(15, 23, 42, .85) 100%); }
        .bio-head-hero .bio-title, .bio-head-hero .bio-bio { color: #fff !important; text-shadow: 0 1px 2px rgba(0, 0, 0, .35); }
        .bio-head-hero .bio-title { font-size: 1.75rem; }
        .bio-head-hero .bio-bio { opacity: .92; }
        .bio-head-banner { margin-top: -2rem; }
        .bio-head-banner .bio-cover { height: 150px; margin: 0 -2rem 0; border-radius: 1.5rem 1.5rem 0 0; background: {{ $coverSrc ? 'url('.e($coverSrc).') center / cover no-repeat' : ($headerBgColor ?: 'linear-gradient(135deg, '.$linkBgColor.', '.$titleColor.')') }}; }
        .bio-head-banner .bio-avatar { margin-top: -48px; position: relative; border-color: {{ $cardBg !== 'transparent' ? $cardBg : 'rgba(255,255,255,.85)' }}; border-width: 4px; }
        .bio-head-cutout { padding-top: .25rem; }
        .bio-head-cutout .bio-avatar { width: 250px; height: 250px; max-width: 78%; border: 0; box-shadow: none; border-radius: 0 !important; margin-bottom: -2.25rem; object-fit: cover; -webkit-mask-image: radial-gradient(ellipse 72% 86% at 50% 36%, #000 42%, transparent 94%); mask-image: radial-gradient(ellipse 72% 86% at 50% 36%, #000 42%, transparent 94%); }
        .bio-head-cutout .bio-title { position: relative; font-size: 1.75rem; text-shadow: 0 1px 12px rgba(0, 0, 0, .18); }
        @media (max-width: 480px) { .bio-head-hero, .bio-head-banner .bio-cover { margin-left: -1rem; margin-right: -1rem; } .bio-head-hero { margin-top: -1rem; } .bio-head-banner { margin-top: -1rem; } }
        @if($showHeaderBg)
        .bio-header-bg {
            background: {{ $headerBgColor }};
            margin: -2rem -2rem 1.5rem -2rem;
            padding: 2rem 2rem 1.5rem 2rem;
            border-radius: 0 0 1.5rem 1.5rem;
        }
        .bio-header-bg .bio-title,
        .bio-header-bg .bio-bio {
            color: #ffffff !important;
        }
        .bio-header-bg .bio-bio {
            opacity: 0.9;
        }
        @endif
    </style>
    <style>
        .blocks-container {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .blocks-container > * {
            margin: 0;
        }
        /* Links · Shop tabs and the product grid */
        .bio-tabs { display: flex; gap: 8px; padding: 4px; border-radius: 999px; background: color-mix(in srgb, var(--tab-text, #111) 10%, transparent); }
        .bio-tab { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 14px; border-radius: 999px; font-weight: 600; font-size: 14px; text-decoration: none; color: inherit; opacity: .75; transition: background .2s, opacity .2s; }
        .bio-tab[aria-selected="true"] { background: var(--tab-bg); color: var(--tab-text); opacity: 1; }
        .bio-tab-count { font-size: 11px; padding: 1px 7px; border-radius: 999px; background: color-mix(in srgb, currentColor 14%, transparent); }
        .bio-shop { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .bio-product { display: flex; flex-direction: column; border-radius: var(--button-radius, 16px); overflow: hidden; background: var(--pbg); color: var(--ptext); text-decoration: none; transition: transform .2s, box-shadow .2s; }
        .bio-product.can-buy:hover { transform: translateY(-2px); box-shadow: 0 12px 30px -16px rgba(0,0,0,.45); }
        .bio-product a { color: inherit; text-decoration: none; }
        .bio-product-name a:hover, .bio-product-cta:hover { text-decoration: underline; }
        .bio-product-pic { position: relative; display: grid; color: inherit; place-items: center; aspect-ratio: 1; background: color-mix(in srgb, var(--ptext) 8%, transparent); }
        .bio-product-pic img { width: 100%; height: 100%; object-fit: cover; }
        .bio-product-pic svg { width: 36px; height: 36px; opacity: .5; }
        .bio-product-flag { position: absolute; left: 8px; top: 8px; padding: 3px 9px; border-radius: 999px; background: #0b1020; color: #fff; font-size: 11px; font-weight: 600; }
        .is-sold-out .bio-product-pic img { filter: grayscale(1); opacity: .7; }
        .bio-product-body { display: grid; gap: 4px; padding: 10px 12px 12px; }
        .bio-product-name { font-size: 14px; font-weight: 600; line-height: 1.3; }
        .bio-product-desc { font-size: 12px; opacity: .8; }
        .bio-product-row { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-top: 4px; }
        .bio-product-price { font-style: normal; font-weight: 700; font-size: 14px; }
        .bio-product-cta { font-style: normal; font-size: 12px; opacity: .85; }
        .bio-form-input { width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid rgba(127,127,127,.4); background: rgba(255,255,255,.85); color: #0f172a; font-size: 14px; }
        .bio-form-btn { padding: 10px 14px; border-radius: 12px; font-weight: 700; font-size: 14px; cursor: pointer; background: rgba(15,23,42,.92); color: #fff; border: 1px solid rgba(255,255,255,.35); }
        .bio-form-btn:focus-visible, .bio-form-input:focus-visible { outline: 3px solid #818cf8; outline-offset: 2px; }
        .bio-poll-opt { width: 100%; padding: 10px 12px; border-radius: 12px; border: 1px solid rgba(127,127,127,.45); background: rgba(255,255,255,.18); color: inherit; font-size: 14px; font-weight: 600; text-align: left; cursor: pointer; }
        .bio-poll-opt:hover { background: rgba(255,255,255,.32); }
        .bio-poll-opt:focus-visible { outline: 3px solid #818cf8; outline-offset: 2px; }
        .bio-poll-res { position: relative; border-radius: 12px; overflow: hidden; border: 1px solid rgba(127,127,127,.35); }
        .bio-poll-bar { position: absolute; inset: 0 auto 0 0; background: rgba(129,140,248,.45); }
        .bio-poll-res.is-mine { border-color: #818cf8; }
        .bio-poll-row { position: relative; display: flex; justify-content: space-between; gap: 8px; padding: 9px 12px; font-size: 14px; font-weight: 600; }
        /* player placeholders: the real iframe is made on tap */
        .bio-facade { position: relative; display: flex; align-items: center; justify-content: center; gap: 12px; width: 100%; min-height: 96px; height: 100%; padding: 14px; color: #fff; text-decoration: none; text-align: left; background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: 12px; overflow: hidden; cursor: pointer; }
        .bio-facade-music, .bio-facade-map { background: linear-gradient(135deg, rgba(30,41,59,.92), rgba(15,23,42,.92)); }
        .bio-facade-play { flex: none; display: grid; place-items: center; width: 54px; height: 54px; border-radius: 50%; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.35); }
        .bio-facade:hover .bio-facade-play { background: rgba(255,255,255,.28); }
        .bio-facade:focus-visible { outline: 3px solid #818cf8; outline-offset: 2px; }
        .bio-facade-text { display: grid; gap: 2px; }
        .bio-facade-text b { font-size: 14px; }
        .bio-facade-text small { font-size: 11px; opacity: .75; }
        .bio-facade-tiktok { max-width: 325px; margin-inline: auto; height: 120px; }
        .bio-facade-video { min-height: 0; border-radius: 0; }
        .bio-embed iframe { display: block; }
        /* long lists: cards that are off screen are not painted until they come close */
        .bio-product, .bio-embed, .bio-feed, .bio-poll, .bio-form { content-visibility: auto; contain-intrinsic-size: auto 220px; }
        .bio-carousel { display: flex; gap: 8px; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: thin; padding-bottom: 4px; border-radius: 16px; }
        .bio-slide { flex: 0 0 86%; scroll-snap-align: center; position: relative; border-radius: 16px; overflow: hidden; aspect-ratio: 4 / 3; background: rgba(127,127,127,.2); }
        .bio-slide img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .bio-slide figcaption { position: absolute; inset: auto 0 0 0; padding: 18px 12px 8px; font-size: 12px; color: #fff; background: linear-gradient(transparent, rgba(0,0,0,.6)); }
        .bio-status { display: inline-flex; align-items: center; gap: 6px; margin-top: 8px; padding: 3px 11px; border-radius: 999px; font-size: 12px; font-weight: 600; background: rgba(34,197,94,.16); color: inherit; border: 1px solid rgba(34,197,94,.45); }
        .bio-status::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,.25); }
        .bio-fab { position: fixed; right: max(16px, env(safe-area-inset-right)); bottom: max(16px, env(safe-area-inset-bottom)); z-index: 60; display: inline-flex; align-items: center; gap: 8px; padding: 12px 16px; border-radius: 999px; font-size: 14px; font-weight: 700; color: #fff; background: #16a34a; box-shadow: 0 10px 30px -10px rgba(0,0,0,.5); text-decoration: none; }
        .bio-fab.custom { background: #4f46e5; }
        .bio-fab:focus-visible { outline: 3px solid #fff; outline-offset: 2px; }
        @media (prefers-reduced-motion: no-preference) { .bio-fab { transition: transform .2s; } .bio-fab:hover { transform: translateY(-2px); } }
        .bio-product-was { font-weight: 500; opacity: .6; margin-right: 4px; }
        .bio-product-low { font-size: 11px; font-weight: 600; color: #d97706; }
        .bio-variants { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 2px; }
        .bio-variant { font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 999px; border: 1px solid color-mix(in srgb, var(--ptext) 35%, transparent); color: inherit; text-decoration: none; }
        a.bio-variant:hover { background: color-mix(in srgb, var(--ptext) 12%, transparent); }
        .bio-variant.is-out { opacity: .5; text-decoration: line-through; }
        @media (max-width: 360px) { .bio-shop { grid-template-columns: minmax(0, 1fr); } }
        /* a collection: a titled group of blocks, stacked or as a grid */
        .bio-collection { display: block; margin-block: 6px; }
        .bio-collection-title { margin: 14px 0 8px; font-size: 13px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; opacity: .8; text-align: center; }
        .bio-shop .bio-collection-title { grid-column: 1 / -1; margin-top: 8px; }
        .bio-collection-body { display: grid; gap: 12px; }
        .layout-grid .bio-collection-body { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .layout-grid .bio-collection-body .link-block { padding: 12px 10px; font-size: 13px; }
        .link-block {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 14px 20px;
            border-radius: var(--button-radius, 12px);
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }
        .link-block:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }
        .link-block.has-thumbnail {
            justify-content: flex-start;
        }
        .link-block.has-icon {
            justify-content: flex-start;
        }
        .block-thumbnail {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .block-icon {
            width: 24px;
            height: 24px;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .block-title {
            flex: 1;
            text-align: center;
        }
        .has-thumbnail .block-title,
        .has-icon .block-title {
            text-align: left;
        }
        .shape-rounded {
            --button-radius: 12px;
        }
        .shape-pill {
            --button-radius: 9999px;
        }
        .shape-square {
            --button-radius: 0;
        }
        .shape-none {
            --button-radius: 4px;
        }
        .shadow-none .link-block,
        .shadow-none .text-block-styled,
        .shadow-none .image-block,
        .shadow-none .countdown-block {
            box-shadow: none !important;
        }
        .shadow-xs .link-block,
        .shadow-xs .text-block-styled,
        .shadow-xs .image-block,
        .shadow-xs .countdown-block {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08) !important;
        }
        .shadow-md .link-block,
        .shadow-md .text-block-styled,
        .shadow-md .image-block,
        .shadow-md .countdown-block {
            box-shadow: 0 4px 8px -1px rgba(0, 0, 0, 0.12) !important;
        }
        .shadow-lg .link-block,
        .shadow-lg .text-block-styled,
        .shadow-lg .image-block,
        .shadow-lg .countdown-block {
            box-shadow: 0 10px 20px -3px rgba(0, 0, 0, 0.15) !important;
        }
        .shadow-xl .link-block,
        .shadow-xl .text-block-styled,
        .shadow-xl .image-block,
        .shadow-xl .countdown-block {
            box-shadow: 0 25px 35px -5px rgba(0, 0, 0, 0.2) !important;
        }
        .hover-scale .link-block:hover { transform: scale(1.03) !important; }
        .hover-glow .link-block:hover { box-shadow: 0 0 20px 5px var(--hover-glow-color, rgba(99, 102, 241, 0.4)) !important; }
        .hover-lift .link-block:hover { transform: translateY(-4px) !important; box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2) !important; }
        .hover-glossy .link-block { position: relative; overflow: hidden; }
        .hover-glossy .link-block::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent); transition: left 0.5s ease; }
        .hover-glossy .link-block:hover::before { left: 100%; }
        .hover-color-shift .link-block { transition: filter 0.3s ease, transform 0.2s ease !important; }
        .hover-color-shift .link-block:hover { filter: hue-rotate(var(--hover-hue-shift, 30deg)) saturate(1.2) !important; }
        :root {
            --hover-glow-color: {{ $themeGlowColor ?? 'rgba(99, 102, 241, 0.4)' }};
            --hover-hue-shift: {{ $themeHueShift ?? '30deg' }};
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes popIn { 0% { opacity: 0; transform: scale(0.8); } 70% { transform: scale(1.05); } 100% { opacity: 1; transform: scale(1); } }
        @keyframes bounceIn { 0% { opacity: 0; transform: translateY(-30px); } 50% { transform: translateY(10px); } 70% { transform: translateY(-5px); } 100% { opacity: 1; transform: translateY(0); } }
        @keyframes flipIn { from { opacity: 0; transform: perspective(400px) rotateX(-90deg); } to { opacity: 1; transform: perspective(400px) rotateX(0deg); } }
        .entrance-fade { animation: fadeIn 0.5s ease-out forwards; }
        .entrance-slide-up { animation: slideUp 0.5s ease-out forwards; }
        .entrance-slide-down { animation: slideDown 0.5s ease-out forwards; }
        .entrance-pop { animation: popIn 0.4s ease-out forwards; }
        .entrance-bounce { animation: bounceIn 0.6s ease-out forwards; }
        .entrance-flip { animation: flipIn 0.5s ease-out forwards; }
        .entrance-stagger { animation: slideUp 0.5s ease-out forwards; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.02); } }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 20%, 60% { transform: translateX(-3px); } 40%, 80% { transform: translateX(3px); } }
        @keyframes attentionGlow { 0%, 100% { box-shadow: 0 0 5px rgba(99, 102, 241, 0.3); } 50% { box-shadow: 0 0 20px rgba(99, 102, 241, 0.6); } }
        @keyframes wiggle { 0%, 100% { transform: rotate(0deg); } 25% { transform: rotate(-2deg); } 75% { transform: rotate(2deg); } }
        @keyframes heartbeat { 0%, 100% { transform: scale(1); } 14% { transform: scale(1.03); } 28% { transform: scale(1); } 42% { transform: scale(1.03); } 70% { transform: scale(1); } }
        @keyframes rainbow { 0% { filter: hue-rotate(0deg); } 100% { filter: hue-rotate(360deg); } }
        @keyframes attentionBounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-3px); } }
        .attention-pulse { animation: pulse 2s ease-in-out infinite; }
        .attention-shake { animation: shake 0.5s ease-in-out infinite; animation-delay: 2s; }
        .attention-glow { animation: attentionGlow 2s ease-in-out infinite; }
        .attention-wiggle { animation: wiggle 1s ease-in-out infinite; }
        .attention-heartbeat { animation: heartbeat 1.5s ease-in-out infinite; }
        .attention-rainbow { animation: rainbow 3s linear infinite; }
        .attention-bounce { animation: attentionBounce 1s ease-in-out infinite; }
        .bg-animation-container { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0; overflow: hidden; }
        .bg-particle { position: absolute; pointer-events: none; }
        /* Lite mode: nothing that costs a weak phone frames. */
        .lite #bg-animation { display: none !important; }
        .lite *, .lite *::before, .lite *::after { animation: none !important; transition: none !important; backdrop-filter: none !important; -webkit-backdrop-filter: none !important; }
        .lite .bio-facade-play, .lite .bio-product { will-change: auto; }
        /* Visitors who asked for less motion get the final state without animating. */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                animation-delay: 0s !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
            #bg-animation { display: none !important; }
        }

        @keyframes fall { 0% { transform: translateY(-10vh) rotate(0deg); opacity: 1; } 100% { transform: translateY(110vh) rotate(360deg); opacity: 0.3; } }
        @keyframes twinkle { 0%, 100% { opacity: 0.3; transform: scale(1); } 50% { opacity: 1; transform: scale(1.2); } }
        .text-block-expandable .text-block-text {
            max-height: 80px;
            overflow: hidden;
            position: relative;
        }
        .text-block-expandable .text-block-text::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background: linear-gradient(transparent, rgba(255,255,255,0.9));
            pointer-events: none;
        }
        .text-block-expandable.expanded .text-block-text {
            max-height: none;
            overflow: visible;
        }
        .text-block-expandable.expanded .text-block-text::after {
            display: none;
        }
        .text-block-expandable .text-expand-icon::after {
            content: '+';
        }
        .text-block-expandable.expanded .text-expand-icon::after {
            content: '−';
        }
        .text-block-expandable .text-expand-icon {
            font-size: 0;
        }
        .text-block-expandable .text-expand-icon::after {
            font-size: 16px;
        }
        .text-block-styled {
            width: 100%;
            border-radius: 12px;
            border: 1px solid {{ $contentBlockBorder }};
            overflow: hidden;
            background: {{ $contentBlockBg }};
            backdrop-filter: blur(4px);
            cursor: pointer;
            color: {{ $contentBlockText }};
        }
        .text-block-header {
            padding: 8px 12px;
            border-bottom: 1px solid {{ $contentBlockBorder }};
            background: {{ $contentBlockHeaderBg }};
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .text-block-expand {
            font-size: 16px;
            font-weight: bold;
            color: {{ $contentBlockMuted }};
            cursor: pointer;
        }
        .text-block-dots {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .text-block-dots .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        .text-block-dots .dot.red { background: #f87171; }
        .text-block-dots .dot.yellow { background: #fbbf24; }
        .text-block-dots .dot.green { background: #4ade80; }
        .text-block-content {
            padding: 16px 20px;
            font-size: 14px;
            line-height: 1.6;
            color: inherit;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .text-block-content.truncated {
            max-height: 80px;
            overflow: hidden;
            position: relative;
        }
        .text-block-content.truncated::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background: linear-gradient(transparent, {{ $contentBlockBg }});
        }
        .text-block-styled.expanded .text-block-content {
            max-height: none;
        }
        .text-block-styled.expanded .text-block-content::after {
            display: none;
        }
        .text-block-styled.expanded .text-block-expand {
            transform: rotate(45deg);
        }
        .text-block-content.truncated {
            max-height: 80px;
            overflow: hidden;
            position: relative;
        }
        .text-block-styled.expanded .text-block-content.truncated {
            max-height: none;
            overflow: visible;
        }
        .image-block-wrapper {
            width: 100%;
        }
        .image-block {
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
        }
        .image-block-img {
            width: 100%;
            height: auto;
            display: block;
            max-height: 300px;
            object-fit: contain;
        }
        .header-block {
            background: transparent;
            box-shadow: none;
            padding: 12px 0;
            font-size: 18px;
            font-weight: 600;
            text-align: center;
        }
        .divider-block {
            height: 1px;
            background: currentColor;
            opacity: 0.2;
            margin: 8px 0;
        }
        .video-block, .music-block {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            padding: 12px;
        }
        .video-embed {
            aspect-ratio: 16/9;
            border-radius: 8px;
            overflow: hidden;
        }
        .video-embed iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }
        .music-embed iframe {
            width: 100%;
            border-radius: 8px;
            border: 0;
        }
        .block-title-header {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            opacity: 0.9;
        }
    </style>
    @if($bioPage->custom_css)
        <style>{!! \Hop\Safety\SafeContent::css($bioPage->custom_css) !!}</style>
    @endif
</head>
@php
    $hoverEffect = $bioPage->hover_effect ?? 'none';
    $bgAnimation = $bioPage->background_animation ?? 'none';
@endphp
<body class="bio-container {{ $hoverEffect && $hoverEffect !== 'none' ? 'hover-' . $hoverEffect : '' }}" data-theme="{{ $bioPage->theme }}" data-bg-animation="{{ $bgAnimation }}">
    <div class="bg-animation-container" id="bg-animation"></div>
    <button aria-label="Share this page" data-show="#share-modal" 
            class="fixed top-4 right-4 z-40 w-10 h-10 bg-white/90 dark:bg-slate-800/90 backdrop-blur-xs rounded-full shadow-lg flex items-center justify-center hover:bg-white dark:hover:bg-slate-700 transition-all">
        <svg class="w-5 h-5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
        </svg>
    </button>
    <div id="share-modal" class="hidden fixed inset-0 flex items-center justify-center p-4" style="z-index: 999999;" data-hide-self-on-backdrop>
        <div class="absolute inset-0 bg-black/60 backdrop-blur-xs"></div>
        <div class="share-modal-content relative rounded-2xl p-4 max-w-xs w-full shadow-2xl" style="background: {{ $isDarkTheme ? '#1e293b' : '#ffffff' }}; color: {{ $isDarkTheme ? '#f1f5f9' : '#1e293b' }}; max-height: 90vh; overflow-y: auto;">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-semibold" style="color: {{ $isDarkTheme ? '#f1f5f9' : '#1e293b' }};">Share this Page</h3>
                <button aria-label="Close" data-hide="#share-modal" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-black/10 transition-colors" style="color: {{ $isDarkTheme ? '#94a3b8' : '#64748b' }};">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex items-center gap-2 p-2 rounded-lg mb-3" style="background: {{ $isDarkTheme ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)' }};">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: {{ $isDarkTheme ? '#94a3b8' : '#64748b' }};"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                <input type="text" value="{{ url()->current() }}" readonly class="flex-1 bg-transparent border-none text-xs focus:outline-hidden min-w-0" style="color: {{ $isDarkTheme ? '#e2e8f0' : '#334155' }};" id="share-url">
                <button data-copy-from="#share-url" class="px-2 py-1 text-xs font-medium rounded-md transition-colors shrink-0" style="background: #3b82f6; color: #ffffff;">Copy</button>
            </div>
            <div class="grid grid-cols-4 gap-2 mb-3">
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" class="flex flex-col items-center gap-1 p-2 rounded-lg transition-colors hover:bg-black/5" title="Facebook">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background-color: #1877f2;">
                        <svg class="w-5 h-5" style="color: #ffffff;" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </div>
                    <span class="text-[10px]" style="color: {{ $isDarkTheme ? '#94a3b8' : '#64748b' }};">Facebook</span>
                </a>
                <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($bioPage->title) }}" target="_blank" class="flex flex-col items-center gap-1 p-2 rounded-lg transition-colors hover:bg-black/5" title="X">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background-color: #000000;">
                        <svg class="w-5 h-5" style="color: #ffffff;" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </div>
                    <span class="text-[10px]" style="color: {{ $isDarkTheme ? '#94a3b8' : '#64748b' }};">X</span>
                </a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(url()->current()) }}" target="_blank" class="flex flex-col items-center gap-1 p-2 rounded-lg transition-colors hover:bg-black/5" title="LinkedIn">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background-color: #0a66c2;">
                        <svg class="w-5 h-5" style="color: #ffffff;" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </div>
                    <span class="text-[10px]" style="color: {{ $isDarkTheme ? '#94a3b8' : '#64748b' }};">LinkedIn</span>
                </a>
                <a href="mailto:?subject={{ urlencode($bioPage->title) }}&body={{ urlencode(url()->current()) }}" class="flex flex-col items-center gap-1 p-2 rounded-lg transition-colors hover:bg-black/5" title="Email">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background-color: #64748b;">
                        <svg class="w-5 h-5" style="color: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <span class="text-[10px]" style="color: {{ $isDarkTheme ? '#94a3b8' : '#64748b' }};">Email</span>
                </a>
            </div>
            <div class="border-t pt-3" style="border-color: {{ $isDarkTheme ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)' }};">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium" style="color: {{ $isDarkTheme ? '#94a3b8' : '#64748b' }};">QR Code</span>
                    <span class="flex items-center gap-1.5">
                    <button data-call="downloadShareCard" class="px-2 py-1 text-[10px] font-medium rounded-sm transition-colors flex items-center gap-1" style="background: {{ $isDarkTheme ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)' }}; color: {{ $isDarkTheme ? '#e2e8f0' : '#334155' }};" title="{{ __('A picture for Instagram or WhatsApp stories') }}">{{ __('Story image') }}</button>
                    <button data-call="downloadShareQR" class="px-2 py-1 text-[10px] font-medium rounded-sm transition-colors flex items-center gap-1" style="background: {{ $isDarkTheme ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.05)' }}; color: {{ $isDarkTheme ? '#e2e8f0' : '#334155' }};">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Save
                    </button>
                    </span>
                </div>
                <div class="flex justify-center">
                    <div id="share-qr-container" class="p-2 rounded-lg" style="background: #ffffff;"></div>
                </div>
            </div>
            <div class="flex items-center justify-between mt-3 pt-3 border-t" style="border-color: {{ $isDarkTheme ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)' }};">
                <a href="{{ route('report.create', ['slug' => $bioPage->slug]) }}" class="text-xs text-red-500 hover:text-red-600 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Report
                </a>
                <a href="{{ url('/register') }}" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors">
                    Create your own
                </a>
            </div>
        </div>
    </div>
    <div class="min-h-screen flex flex-col {{ $containerAlign }} justify-center p-4" x-data="{ tab: '{{ (request()->attributes->get('openShop') || request()->query('tab') === 'shop') ? 'shop' : 'links' }}' }">
        <div class="w-full {{ $maxWidth }}">
            <div class="rounded-3xl p-8">
                <div class="text-center bio-head bio-head-{{ $headerLayout }} {{ $showHeaderBg ? 'bio-header-bg' : '' }}">
                    @if($headerLayout === 'hero')
                        <img src="{{ $avatarSrc }}" alt="{{ $bioPage->title }}" class="bio-hero-img" fetchpriority="high" {!! \Hop\Bio\Support\ImageMeta::attrs($bioPage->avatar_url) !!}>
                        <div class="bio-hero-text">
                    @elseif($headerLayout === 'banner')
                        <div class="bio-cover" role="presentation"></div>
                    @endif
                    @if($headerLayout === 'cutout')
                        <img src="{{ $avatarSrc }}" alt="{{ $bioPage->title }}" class="bio-avatar mx-auto" fetchpriority="high" {!! \Hop\Bio\Support\ImageMeta::attrs($bioPage->avatar_url) !!}>
                    @elseif($headerLayout !== 'hero')
                        @if($avatarSrc)
                        <img src="{{ $avatarSrc }}" 
                             alt="{{ $bioPage->title }}" 
                             class="bio-avatar mx-auto mb-4 h-24 w-24 object-cover" width="96" height="96" fetchpriority="high"
                             style="border-radius: {{ $avatarRadius }};@if($avatarMeta = \Hop\Bio\Support\ImageMeta::for($bioPage->avatar_url)) background-color: {{ $avatarMeta['color'] }};@endif">
                        @else
                        <div class="bio-avatar mx-auto mb-4 flex h-24 w-24 items-center justify-center bg-linear-to-br from-blue-500 to-indigo-600 text-3xl font-bold text-white"
                             style="border-radius: {{ $avatarRadius }};">
                            {{ mb_strtoupper(mb_substr($bioPage->title, 0, 1)) }}
                        </div>
                        @endif
                    @endif
                    <div class="text-center relative flex justify-center">
                        <h1 class="bio-title text-2xl font-bold relative inline-block">
                            {{ $bioPage->title }}
                            @if($bioPage->badge)
                                <span class="absolute left-full top-1/2 -translate-y-1/2 ml-2 whitespace-nowrap" style="color: {{ $bioPage->badge_color ?? '#3b82f6' }};">
                                    @if($bioPage->badge === 'verified')
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="inline"><path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307zm7.007 6.387a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd"/></svg>
                                    @elseif($bioPage->badge === 'star')
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="inline"><path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd"/></svg>
                                    @elseif($bioPage->badge === 'crown')
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="inline"><path d="M2 17l2-11 4 5 4-7 4 7 4-5 2 11H2z"/><rect x="3" y="18" width="18" height="3" rx="1"/></svg>
                                    @elseif($bioPage->badge === 'fire')
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="inline"><path fill-rule="evenodd" d="M12.963 2.286a.75.75 0 00-1.071-.136 9.742 9.742 0 00-3.539 6.177A7.547 7.547 0 016.648 6.61a.75.75 0 00-1.152.082A9 9 0 1015.68 4.534a7.46 7.46 0 01-2.717-2.248zM15.75 14.25a3.75 3.75 0 11-7.313-1.172c.628.465 1.35.81 2.133 1a5.99 5.99 0 011.925-3.545 3.75 3.75 0 013.255 3.717z" clip-rule="evenodd"/></svg>
                                    @endif
                                </span>
                            @endif
                        </h1>
                    </div>
                    @if($bioPage->bio)
                        <p class="bio-bio mt-2 text-sm">{{ html_entity_decode($bioPage->bio) }}</p>
                    @endif
                    @if($bioPage->statusLabel())<div><span class="bio-status">{{ $bioPage->statusLabel() }}</span></div>@endif
                    @if($headerLayout === 'hero')
                        </div>
                    @endif
                </div>
                @if($bioPage->social_links && count($bioPage->social_links) > 0 && (!$bioPage->social_icons_position || $bioPage->social_icons_position === 'below_bio'))
                    <div class="mt-4 flex justify-center gap-3 flex-wrap">
                        @foreach($bioPage->social_links as $socialLink)
                            @php
                                // Shown unless switched off, as in the editor preview; older rows have no "enabled" key.
                                $isEnabled = is_array($socialLink) && ! in_array($socialLink['enabled'] ?? $socialLink['is_active'] ?? true, [false, 0, '0', 'false'], true);
                                $linkValue = is_array($socialLink) ? trim((string) (($socialLink['value'] ?? '') ?: ($socialLink['url'] ?? ''))) : '';
                            @endphp
                            @if(is_array($socialLink) && $isEnabled && !empty($linkValue))
                                @php
                                    $platform = $socialLink['platform'];
                                    $url = \Hop\Safety\SafeContent::url(SocialUrlHelper::buildUrl($platform, $linkValue));
                                    $platformConfig = config("brands.platforms.{$platform}");
                                    $brandColor = $platformConfig['color'] ?? '#6b7280';
                                @endphp
                                <a href="{{ $url }}" target="_blank" rel="nofollow noreferrer" 
                                   class="social-icon-btn rounded-full p-2.5 transition-all duration-200"
                                   style="background: {{ $brandColor }};">
                                    <img src="/images/brands/{{ $platform }}.svg?v=20251212" 
                                         alt="{{ ucfirst(str_replace('-', ' ', $platform)) }}" 
                                         class="social-icon h-5 w-5" width="20" height="20" decoding="async"
                                         style="filter: brightness(0) invert(1);"
                                         data-fallback="hide">
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
                @php
                    $activeBlocks = $bioPage->links->where('is_active', true)->sortBy('order');
                    $products = $activeBlocks->where('type', 'product');
                    $hasShop = $products->isNotEmpty();
                    $openShop = $hasShop && (request()->attributes->get('openShop') || request()->query('tab') === 'shop');
                    // collections: a marker block that groups what follows it, until the next collection or divider
                    $collectionOf = []; $current = null;
                    foreach ($activeBlocks as $b) {
                        if ($b->type === 'collection') { $current = $b; continue; }
                        if ($b->type === 'divider') { $current = null; }
                        $collectionOf[$b->id] = $current;
                    }
                    $shopGroups = $products->groupBy(fn ($b) => $collectionOf[$b->id]?->id ?? 0);
                    $linkBlocks = $activeBlocks->whereNotIn('type', ['product', 'collection']);
                    $linkCollections = $linkBlocks->map(fn ($b) => $collectionOf[$b->id]?->id)->filter()->unique()->all();
                @endphp
                @if($hasShop)
                    {{-- Links · Shop: a tab bar once the page sells something; without script the address ?tab=shop opens the shop --}}
                    <div class="bio-tabs mt-8" role="tablist" aria-label="Page sections">
                        <a href="{{ route('bio.public.show', $bioPage->slug) }}" role="tab" id="tab-links" aria-controls="panel-links" aria-selected="{{ $openShop ? 'false' : 'true' }}" :aria-selected="String(tab === 'links')" @click.prevent="tab = 'links'" class="bio-tab" style="--tab-bg: {{ $linkBgColor ?? $blockBg ?? 'rgba(255,255,255,.2)' }}; --tab-text: {{ $linkTextColor }}">{{ __('Links') }}</a>
                        <a href="{{ route('bio.public.shop', $bioPage->slug) }}" role="tab" id="tab-shop" aria-controls="panel-shop" aria-selected="{{ $openShop ? 'true' : 'false' }}" :aria-selected="String(tab === 'shop')" @click.prevent="tab = 'shop'" class="bio-tab" style="--tab-bg: {{ $linkBgColor ?? $blockBg ?? 'rgba(255,255,255,.2)' }}; --tab-text: {{ $linkTextColor }}">{{ __('Shop') }} <span class="bio-tab-count">{{ $products->count() }}</span></a>
                    </div>
                    <div id="panel-shop" role="tabpanel" aria-labelledby="tab-shop" class="bio-shop mt-6" x-show="tab === 'shop'" @if(!$openShop) hidden @endif x-bind:hidden="false">
                        @foreach($shopGroups as $groupId => $groupProducts)
                        @if($groupId)<h3 class="bio-collection-title">{{ $activeBlocks->firstWhere('id', $groupId)?->title }}</h3>@endif
                        @foreach($groupProducts as $product)
                            @php
                                $pHref = $product->url ? $clickHref($product->public_id) : null;
                                $pImg = $product->thumbnail_url ?: $product->content;
                                if ($pImg && !str_starts_with($pImg, 'http') && !str_starts_with($pImg, '/storage/')) { $pImg = Storage::disk('public')->url($pImg); }
                                $soldOut = $product->isSoldOut();
                                $variants = $product->variants();
                                $lowStock = $product->lowStock();
                                $wasPrice = $product->comparePriceLabel();
                            @endphp
                            @php $buy = $pHref && !$soldOut; @endphp
                            <article class="bio-product {{ $soldOut ? 'is-sold-out' : '' }} {{ $buy ? 'can-buy' : '' }}" style="--pbg: {{ $linkBgColor ?? $blockBg ?? 'rgba(255,255,255,.2)' }}; --ptext: {{ $linkTextColor }}">
                                @if($buy)<a href="{{ $pHref }}" target="_blank" rel="nofollow noopener" class="bio-product-pic" aria-label="{{ $product->title }}">@else<span class="bio-product-pic">@endif
                                    @if($pImg)<img src="{{ $pImg }}" alt="" loading="lazy" decoding="async" {!! \Hop\Bio\Support\ImageMeta::attrs($pImg) !!}>@else<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>@endif
                                    @if($soldOut)<b class="bio-product-flag">{{ __('Sold out') }}</b>@endif
                                @if($buy)</a>@else</span>@endif
                                <span class="bio-product-body"><b class="bio-product-name">@if($buy)<a href="{{ $pHref }}" target="_blank" rel="nofollow noopener">{{ $product->title }}</a>@else{{ $product->title }}@endif</b>@if($product->description)<small class="bio-product-desc">{{ $product->description }}</small>@endif
                                    @if($variants !== [])
                                    <span class="bio-variants" role="list" aria-label="{{ __('Variants') }}">
                                        @foreach($variants as $v)
                                            @if($v['url'] && !$v['sold_out'])
                                                <a role="listitem" href="{{ $v['url'] }}" target="_blank" rel="nofollow noopener" class="bio-variant" title="{{ $v['price'] !== null ? $product->formatPrice($v['price']) : '' }}">{{ $v['name'] }}</a>
                                            @else
                                                <span role="listitem" class="bio-variant {{ $v['sold_out'] ? 'is-out' : '' }}" title="{{ $v['sold_out'] ? __('Sold out') : ($v['price'] !== null ? $product->formatPrice($v['price']) : '') }}">{{ $v['name'] }}</span>
                                            @endif
                                        @endforeach
                                    </span>
                                    @endif
                                    @if($lowStock && !$soldOut)<small class="bio-product-low">{{ __('Only :count left', ['count' => $lowStock]) }}</small>@endif
                                    <span class="bio-product-row"><em class="bio-product-price">@if($wasPrice)<s class="bio-product-was">{{ $wasPrice }}</s> @endif{{ $product->priceLabel() }}</em>@if($buy)<a href="{{ $pHref }}" target="_blank" rel="nofollow noopener" class="bio-product-cta">{{ __('View') }} ↗</a>@endif</span></span>
                            </article>
                        @endforeach
                        @endforeach
                    </div>
                @endif
                <div id="panel-links" @if($hasShop) role="tabpanel" aria-labelledby="tab-links" x-show="tab === 'links'" @if($openShop) hidden @endif x-bind:hidden="false" @endif class="mt-8 grow blocks-container shape-{{ $bioPage->block_shape ?? 'rounded-sm' }} shadow-{{ $bioPage->block_shadow ?? 'md' }}">
                    @php $openCollection = null; @endphp
                    @forelse($activeBlocks->whereNotIn('type', ['product']) as $link)
                        @php $owner = $link->type === 'collection' ? null : ($collectionOf[$link->id] ?? null); @endphp
                        @if($openCollection && $owner?->id !== $openCollection->id)
                            </div></section>
                            @php $openCollection = null; @endphp
                        @endif
                        @if($link->type === 'collection')
                            @if(in_array($link->id, $linkCollections, true))
                                @php $openCollection = $link; @endphp
                                <section class="bio-collection layout-{{ $link->settings['layout'] ?? 'stack' }}"><h3 class="bio-collection-title">{{ $link->title }}</h3><div class="bio-collection-body">
                            @endif
                            @continue
                        @endif
                        @if(in_array($link->type, ['link', 'whatsapp', 'app'], true))
                            @php
                                $hasThumbnail = $link->thumbnail_url || $link->thumbnail;
                                $hasBrand = $link->brand;
                                $hasIcon = $hasBrand || $link->custom_icon;
                                $brandConfig = $hasBrand ? config("brands.platforms.{$link->brand}") : null;
                                $defaultBrandColor = $brandConfig['color'] ?? $blockBg;
                                $brandColor = $link->btn_bg_color ?? $blockBg;
                                $textColor = $link->btn_text_color ?? $linkTextColor;
                                $forceWhiteIcon = $link->btn_icon_invert ?? false;
                                $forceBlackIcon = false;
                                if (!$link->custom_icon && !$forceWhiteIcon && $hasIcon) {
                                    $forceBlackIcon = $isLightColor($brandColor);
                                }
                                $iconStyle = '';
                                if (!$link->custom_icon) {
                                    if ($forceWhiteIcon) {
                                        $iconStyle = 'filter: brightness(0) invert(1);';
                                    } elseif ($forceBlackIcon) {
                                        $iconStyle = 'filter: brightness(0);';
                                    } elseif ($hasBrand) {
                                        $iconStyle = 'filter: brightness(0) invert(1);';
                                    }
                                }
                                $iconPath = null;
                                if ($hasIcon) {
                                    $iconPath = $link->custom_icon ?: ($hasBrand ? "/images/brands/" . ($brandConfig['icon'] ?? "{$link->brand}.svg") . "?v=20251212" : null);
                                }
                                $borderStyle = $link->btn_border_color ? 'border: 1px solid '.$link->btn_border_color.';' : ($themeLinkBorder ? 'border: 1px solid '.$themeLinkBorder.';' : '');
                                $linkRel = $getLinkRel($link->url);
                                // http(s) links go through the click tracker; mailto/tel/sms stay direct
                                $linkHref = in_array($link->type, ['whatsapp', 'app'], true) || preg_match('#^https?://#i', trim((string) $link->url))
                                    ? $clickHref($link->public_id)
                                    : \Hop\Safety\SafeContent::url($link->url);
                                $entranceAnim = $link->entrance_animation ?? 'none';
                                $attentionAnim = $link->attention_animation ?? 'none';
                                $animClasses = '';
                                if ($entranceAnim && $entranceAnim !== 'none') $animClasses .= ' entrance-' . $entranceAnim;
                                if ($attentionAnim && $attentionAnim !== 'none') $animClasses .= ' attention-' . $attentionAnim;
                            @endphp
                            <a href="{{ $linkHref }}" target="_blank" rel="{{ $linkRel }}" 
                               class="link-block {{ $hasThumbnail ? 'has-thumbnail' : '' }} {{ $hasIcon && !$hasThumbnail ? 'has-icon' : '' }}{{ $animClasses }}"
                               style="background: {{ $brandColor }}; color: {{ $textColor }}; {{ $borderStyle }}{{ $entranceAnim === 'stagger' ? ' animation-delay: ' . ($loop->index * 0.1) . 's;' : '' }}"
                               @if($entranceAnim && $entranceAnim !== 'none')
                               data-entrance="{{ $entranceAnim }}"
                               @endif>
                                @if($hasThumbnail)
                                    @php
                                        $thumbUrl = $link->thumbnail_url ?? $link->thumbnail;
                                        if (!str_starts_with($thumbUrl, 'http') && !str_starts_with($thumbUrl, '/storage/') && !str_starts_with($thumbUrl, '/')) {
                                            $thumbUrl = Storage::disk('public')->url($thumbUrl);
                                        }
                                    @endphp
                                    <img src="{{ $thumbUrl }}" 
                                         alt="" class="block-thumbnail" width="42" height="42" loading="lazy" decoding="async">
                                @elseif($iconPath)
                                    <img src="{{ $iconPath }}" 
                                         alt="{{ $link->brand ?? 'icon' }}" 
                                         class="block-icon" width="24" height="24" loading="lazy" decoding="async"
                                         style="{{ $iconStyle }}">
                                @endif
                                <span class="block-title">{{ $link->title ?? 'Untitled Link' }}</span>
                            </a>
                        @elseif($link->type === 'text')
                            <div class="text-block-styled" data-toggle-class="expanded">
                                <div class="text-block-header">
                                    <div class="text-block-dots">
                                        <span class="dot red"></span>
                                        <span class="dot yellow"></span>
                                        <span class="dot green"></span>
                                    </div>
                                    <span class="text-block-expand">+</span>
                                </div>
                                <div class="text-block-content truncated" style="color: inherit;">
                                    @php
                                        $textContent = html_entity_decode($link->content ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                        $textContent = \Hop\Safety\SafeContent::html(nl2br($textContent));
                                    @endphp
                                    {!! $textContent !!}
                                </div>
                            </div>
                        @elseif($link->type === 'image' && ($link->thumbnail_url || $link->content))
                            @php
                                $imageUrl = $link->content ?: $link->thumbnail_url;
                                if ($imageUrl && !str_starts_with($imageUrl, 'http') && !str_starts_with($imageUrl, '/storage/')) {
                                    $imageUrl = Storage::disk('public')->url($imageUrl);
                                }
                            @endphp
                            <div class="image-block-wrapper">
                                <div class="image-block">
                                    <img src="{{ $imageUrl }}" 
                                         alt="{{ $link->title ?? 'Image' }}" 
                                         class="image-block-img" loading="lazy" decoding="async">
                                </div>
                                @if($link->title)
                                    <p class="mt-2 text-center text-sm" style="color: inherit;">{{ $link->title }}</p>
                                @endif
                            </div>
                        @elseif($link->type === 'header')
                            <div class="header-block" style="color: inherit;">{{ $link->title }}</div>
                        @elseif($link->type === 'divider')
                            <div class="divider-block" style="background: currentColor;"></div>
                        @elseif(in_array($link->type, ['video', 'youtube', 'music', 'spotify', 'soundcloud'], true) && $link->embed_url)
                            @include('bio::partials.embed', ['link' => $link])
                        @elseif($link->type === 'vcard')
                            <div class="w-full rounded-2xl p-5 text-center" style="background: rgba(0,0,0,0.05);">
                                <div class="w-20 h-20 mx-auto mb-3 rounded-full flex items-center justify-center" style="background: var(--button-bg, #6366f1);">
                                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div class="text-lg font-bold mb-1" style="color: inherit;">{{ $link->vcard_name }}</div>
                                @if($link->vcard_company)
                                    <div class="text-sm opacity-70 mb-3" style="color: inherit;">{{ $link->vcard_company }}</div>
                                @endif
                                <div class="space-y-1 mb-4 text-sm" style="color: inherit;">
                                    @if($link->vcard_phone)
                                        <div class="flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            <span>{{ $link->vcard_phone }}</span>
                                        </div>
                                    @endif
                                    @if($link->vcard_email)
                                        <div class="flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            <span>{{ $link->vcard_email }}</span>
                                        </div>
                                    @endif
                                </div>
                                <button type="button" data-call="downloadVCard" data-args="{{ json_encode([$link->vcard_name, $link->vcard_phone, $link->vcard_email, $link->vcard_company]) }}" 
                                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium transition-all"
                                        style="background: var(--button-bg, #6366f1); color: var(--button-text, #fff);">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Save Contact
                                </button>
                            </div>
                        @elseif($link->type === 'html')
                            <div class="w-full rounded-xl p-4" style="background: rgba(0,0,0,0.05);">
                                @if($link->title)
                                    <div class="mb-3 text-sm font-semibold" style="color: inherit;">{{ $link->title }}</div>
                                @endif
                                <div class="text-sm leading-relaxed" style="color: inherit;">
                                    @php
                                        $htmlContent = \Hop\Safety\SafeContent::html($link->html_content);
                                    @endphp
                                    {!! $htmlContent !!}
                                </div>
                            </div>
                        @elseif($link->type === 'countdown' && $link->countdown_date)
                            <div class="countdown-block w-full rounded-2xl p-4 text-center overflow-hidden" style="background: {{ $countdownBg }}; color: {{ $countdownText }};">
                                <div class="text-sm font-medium mb-3 opacity-90">{{ $link->countdown_label ?? 'Countdown' }}</div>
                                <div class="countdown-timer flex justify-center items-center gap-1 sm:gap-2 flex-wrap" data-target="{{ $link->countdown_date->copy()->utc()->toIso8601ZuluString() }}">
                                    <div class="countdown-unit flex flex-col items-center min-w-[40px] sm:min-w-[50px]">
                                        <span class="countdown-days text-xl sm:text-2xl font-bold">00</span>
                                        <span class="text-[10px] sm:text-xs uppercase opacity-80 mt-0.5">Days</span>
                                    </div>
                                    <span class="text-lg sm:text-xl font-bold opacity-60">:</span>
                                    <div class="countdown-unit flex flex-col items-center min-w-[40px] sm:min-w-[50px]">
                                        <span class="countdown-hours text-xl sm:text-2xl font-bold">00</span>
                                        <span class="text-[10px] sm:text-xs uppercase opacity-80 mt-0.5">Hours</span>
                                    </div>
                                    <span class="text-lg sm:text-xl font-bold opacity-60">:</span>
                                    <div class="countdown-unit flex flex-col items-center min-w-[40px] sm:min-w-[50px]">
                                        <span class="countdown-minutes text-xl sm:text-2xl font-bold">00</span>
                                        <span class="text-[10px] sm:text-xs uppercase opacity-80 mt-0.5">Min</span>
                                    </div>
                                    <span class="text-lg sm:text-xl font-bold opacity-60">:</span>
                                    <div class="countdown-unit flex flex-col items-center min-w-[40px] sm:min-w-[50px]">
                                        <span class="countdown-seconds text-xl sm:text-2xl font-bold">00</span>
                                        <span class="text-[10px] sm:text-xs uppercase opacity-80 mt-0.5">Sec</span>
                                    </div>
                                </div>
                            </div>
                        @elseif($link->type === 'code')
                            <div class="w-full rounded-xl overflow-hidden" style="background: #1e293b;">
                                @if($link->title)
                                    <div class="px-4 py-2 border-b border-slate-700 text-xs font-medium text-slate-300 flex items-center justify-between">
                                        <span>{{ $link->title }}</span>
                                        <span class="text-slate-500 uppercase text-[10px]">{{ $link->content ?? 'code' }}</span>
                                    </div>
                                @endif
                                <pre class="p-4 text-sm font-mono text-slate-200 overflow-x-auto" style="margin: 0;"><code>{{ $link->html_content }}</code></pre>
                            </div>
                        @elseif($link->type === 'map' && $link->map_address)
                            <div class="w-full rounded-xl overflow-hidden p-3" style="background: rgba(0,0,0,0.05);">
                                @if($link->title)
                                    <div class="mb-2 text-sm font-semibold" style="color: inherit;">{{ $link->title }}</div>
                                @endif
                                <div class="rounded-lg overflow-hidden mb-2">
                                    @include('bio::partials.facade', ['src' => "https://maps.google.com/maps?q=".urlencode($link->map_address)."&z=".($link->map_zoom ?? 15)."&output=embed", 'href' => 'https://www.google.com/maps/search/?api=1&query='.urlencode($link->map_address), 'title' => $link->title ?: 'Map', 'kind' => 'map', 'height' => 200, 'sandbox' => null])
                                </div>
                                <div class="text-xs text-center opacity-80" style="color: inherit;">{{ $link->map_address }}</div>
                            </div>
                        @elseif($link->type === 'form')
                            @php
                                $ff = $link->formFields(); $fid = $link->public_id; $sent = session('form_sent') === $fid; $errs = $errors->getBag('form_'.$fid);
                                $consentText = trim((string) ($link->settings['consent'] ?? '')) ?: __('I agree that :name keeps these details to get back to me.', ['name' => $bioPage->title]);
                            @endphp
                            <div id="form-{{ $fid }}" class="bio-form w-full rounded-2xl p-4" style="background: {{ $blockBg }}; color: {{ $linkTextColor }};">
                                @if($link->title)<div class="mb-2 text-sm font-semibold">{{ $link->title }}</div>@endif
                                @if($sent)
                                    <p class="text-sm" role="status">✓ {{ trim((string) ($link->settings['thanks'] ?? '')) ?: __('Thank you! Your details were sent.') }}</p>
                                @else
                                    <form method="POST" action="{{ route('bio.public.form', $fid) }}" class="grid gap-2" x-data="turnstileForm(@js(config('services.turnstile.site_key')))" x-on:submit="handleSubmit($event)">
                                        @csrf
                                        <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
                                        @if(isset($ff['name']))<input type="text" name="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name" placeholder="{{ __('Your name') }}" aria-label="{{ __('Your name') }}" class="bio-form-input">@endif
                                        @if(isset($ff['email']))<input type="email" name="email" value="{{ old('email') }}" required maxlength="150" autocomplete="email" placeholder="{{ __('Your e-mail') }}" aria-label="{{ __('Your e-mail') }}" class="bio-form-input">@endif
                                        @if(isset($ff['phone']))<input type="tel" name="phone" value="{{ old('phone') }}" required maxlength="30" autocomplete="tel" placeholder="{{ __('Your WhatsApp number') }}" aria-label="{{ __('Your WhatsApp number') }}" class="bio-form-input">@endif
                                        <label class="flex items-start gap-2 text-xs opacity-90"><input type="checkbox" name="consent" value="1" required class="mt-0.5"><span>{{ $consentText }}</span></label>
                                        <x-turnstile />
                                        @foreach($errs->all() as $e)<p class="text-xs" style="color:#fecaca" role="alert">{{ $e }}</p>@endforeach
                                        <button type="submit" class="bio-form-btn"><span x-show="!waiting">{{ trim((string) ($link->settings['button'] ?? '')) ?: __('Send') }}</span><span x-cloak x-show="waiting">{{ __('Checking…') }}</span></button>
                                    </form>
                                @endif
                            </div>
                        @elseif($link->type === 'poll' && $link->pollOptions())
                            @php
                                $pOpts = $link->pollOptions();
                                $pRes = $link->pollResults(\Hop\Bio\Models\BioPollVote::voterHash(request()->ip(), request()->userAgent()));
                            @endphp
                            <div id="poll-{{ $link->public_id }}" class="bio-poll w-full rounded-2xl p-4" style="background: {{ $blockBg }}; color: {{ $linkTextColor }};">
                                <div class="mb-2 text-sm font-semibold">{{ $link->title }}</div>
                                @if($pRes['voted'] === null)
                                    <form method="POST" action="{{ route('bio.public.poll', $link->public_id) }}" class="grid gap-2">
                                        @csrf
                                        @foreach($pOpts as $i => $opt)
                                            <button type="submit" name="option" value="{{ $i }}" class="bio-poll-opt">{{ $opt }}</button>
                                        @endforeach
                                    </form>
                                @else
                                    <ul class="grid gap-2" aria-label="{{ __('Results') }}">
                                        @foreach($pOpts as $i => $opt)
                                            @php $pc = $pRes['total'] > 0 ? (int) round($pRes['counts'][$i] / $pRes['total'] * 100) : 0; @endphp
                                            <li class="bio-poll-res {{ $pRes['voted'] === $i ? 'is-mine' : '' }}"><span class="bio-poll-bar" style="width: {{ $pc }}%"></span><span class="bio-poll-row"><span>{{ $opt }}@if($pRes['voted'] === $i) ✓@endif</span><span class="tabular-nums">{{ $pc }}%</span></span></li>
                                        @endforeach
                                    </ul>
                                    <p class="mt-2 text-[11px] opacity-70">{{ trans_choice(':count vote|:count votes', $pRes['total'], ['count' => $pRes['total']]) }}</p>
                                @endif
                            </div>
                        @elseif($link->type === 'rss')
                            @php $feed = $link->feedItems(); @endphp
                            @if($feed)
                            <div class="bio-feed w-full rounded-2xl p-3" style="background: {{ $blockBg }}; color: {{ $linkTextColor }};">
                                @if($link->title)<div class="mb-1 px-1 text-sm font-semibold">{{ $link->title }}</div>@endif
                                <ul class="grid gap-0.5">
                                    @foreach($feed as $item)
                                        <li><a href="{{ $item['url'] }}" target="_blank" rel="nofollow noopener" class="flex items-baseline justify-between gap-3 rounded-lg px-1 py-1.5 text-sm hover:bg-black/5"><span>{{ $item['title'] }}</span>@if($item['date'])<time class="shrink-0 text-[11px] opacity-70" datetime="{{ $item['date'] }}">{{ \Illuminate\Support\Carbon::parse($item['date'])->isoFormat('D MMM') }}</time>@endif</a></li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                        @elseif($link->type === 'goal')
                            @php $pct = $link->goalPercent(); $unit = trim((string) ($link->settings['unit'] ?? '')); $fmt = fn ($n) => number_format((float) $n, 0, '.', ',').($unit !== '' ? ' '.$unit : ''); @endphp
                            <div class="bio-goal w-full rounded-2xl p-4" style="background: {{ $blockBg }}; color: {{ $linkTextColor }};">
                                <div class="flex items-baseline justify-between gap-2 text-sm font-semibold"><span>{{ $link->title }}</span><span class="tabular-nums">{{ $pct }}%</span></div>
                                <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full" style="background: rgba(127,127,127,.25)" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}" aria-label="{{ $link->title }}"><div class="h-full rounded-full" style="width: {{ $pct }}%; background: currentColor; opacity: .85"></div></div>
                                <div class="mt-1.5 flex justify-between text-xs opacity-80"><span>{{ $fmt($link->settings['current'] ?? 0) }}</span><span>{{ __('Goal') }}: {{ $fmt($link->settings['target'] ?? 0) }}</span></div>
                                @if($link->url && preg_match('#^https?://#i', $link->url))<a href="{{ $clickHref($link->public_id) }}" target="_blank" rel="nofollow noopener" class="mt-2 inline-block text-xs font-semibold underline">{{ $link->description ?: __('Support') }} ↗</a>@endif
                            </div>
                        @elseif($link->type === 'audio' && $link->content)
                            <div class="bio-audio w-full rounded-2xl p-3" style="background: {{ $blockBg }}; color: {{ $linkTextColor }};">
                                @if($link->title)<div class="mb-2 text-sm font-semibold">{{ $link->title }}</div>@endif
                                <audio controls preload="none" class="w-full" src="{{ $link->content }}">{{ __('Your browser cannot play this clip.') }}</audio>
                            </div>
                        @elseif($link->type === 'pdf' && $link->content)
                            <a href="{{ $link->content }}" target="_blank" rel="noopener" class="bio-pdf link-block" style="background: {{ $blockBg }}; color: {{ $linkTextColor }};">
                                <span class="block-title">📄 {{ $link->title ?: __('Document') }} <small class="opacity-70">PDF</small></span>
                            </a>
                        @elseif($link->type === 'carousel' && $link->carouselImages())
                            <div class="bio-carousel w-full" role="region" aria-roledescription="carousel" aria-label="{{ $link->title ?: __('Pictures') }}" tabindex="0">
                                @foreach($link->carouselImages() as $img)
                                    <figure class="bio-slide"><img src="{{ $img['url'] }}" alt="{{ $img['caption'] }}" loading="lazy" decoding="async" {!! \Hop\Bio\Support\ImageMeta::attrs($img['url']) !!}>@if($img['caption'])<figcaption>{{ $img['caption'] }}</figcaption>@endif</figure>
                                @endforeach
                            </div>
                        @elseif($link->type === 'faq' && $link->faq_items)
                            <div class="w-full rounded-xl p-4" style="background: rgba(0,0,0,0.05);">
                                @if($link->title)
                                    <div class="mb-3 text-sm font-semibold" style="color: inherit;">{{ $link->title }}</div>
                                @endif
                                <div class="space-y-2" x-data="{ openFaq: null }">
                                    @foreach($link->faq_items as $idx => $faqItem)
                                        <div class="rounded-lg overflow-hidden" style="background: rgba(255,255,255,0.5);">
                                            <button @click="openFaq = openFaq === {{ $idx }} ? null : {{ $idx }}" 
                                                    class="w-full flex justify-between items-center px-4 py-3 text-sm font-medium text-left"
                                                    :class="{ 'bg-indigo-50': openFaq === {{ $idx }} }"
                                                    style="color: inherit;">
                                                <span>{{ $faqItem['question'] ?? 'Question' }}</span>
                                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': openFaq === {{ $idx }} }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                            <div x-show="openFaq === {{ $idx }}" x-collapse class="px-4 pb-3 text-sm opacity-80" style="color: inherit;">
                                                {{ $faqItem['answer'] ?? 'Answer' }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @empty
                        <p class="py-8 text-center text-sm opacity-60">No links yet</p>
                    @endforelse
                    @if($openCollection)</div></section>@endif
                </div>
                @if($bioPage->social_links && count($bioPage->social_links) > 0 && $bioPage->social_icons_position === 'bottom_page')
                    <div class="mt-8 flex justify-center gap-3 flex-wrap">
                        @foreach($bioPage->social_links as $socialLink)
                            @php
                                // Shown unless switched off, as in the editor preview; older rows have no "enabled" key.
                                $isEnabled = is_array($socialLink) && ! in_array($socialLink['enabled'] ?? $socialLink['is_active'] ?? true, [false, 0, '0', 'false'], true);
                                $linkValue = is_array($socialLink) ? trim((string) (($socialLink['value'] ?? '') ?: ($socialLink['url'] ?? ''))) : '';
                            @endphp
                            @if(is_array($socialLink) && $isEnabled && !empty($linkValue))
                                @php
                                    $platform = $socialLink['platform'];
                                    $url = \Hop\Safety\SafeContent::url(SocialUrlHelper::buildUrl($platform, $linkValue));
                                    $platformConfig = config("brands.platforms.{$platform}");
                                    $brandColor = $platformConfig['color'] ?? '#6b7280';
                                @endphp
                                <a href="{{ $url }}" target="_blank" rel="nofollow noreferrer" 
                                   class="social-icon-btn rounded-full p-2.5 transition-all duration-200"
                                   style="background: {{ $brandColor }};">
                                    <img src="/images/brands/{{ $platform }}.svg?v=20251212" 
                                         alt="{{ ucfirst(str_replace('-', ' ', $platform)) }}" 
                                         class="social-icon h-5 w-5" width="20" height="20" decoding="async"
                                         style="filter: brightness(0) invert(1);"
                                         data-fallback="hide">
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif
                @if(trim((string) $bioPage->footer_note) !== '')
                <p class="bio-footer-note mt-8 text-center text-xs" style="color: {{ $bioColor }}; opacity: .85">
                    @if($bioPage->footer_url)<a href="{{ \Hop\Safety\SafeContent::url($bioPage->footer_url) }}" target="_blank" rel="nofollow noopener" class="underline">{{ $bioPage->footer_note }}</a>@else{{ $bioPage->footer_note }}@endif
                </p>
                @endif
                @unless($bioPage->hide_branding)
                <div class="{{ trim((string) $bioPage->footer_note) !== '' ? 'mt-3' : 'mt-8' }} text-center">
                    <a href="{{ url('/') }}" class="powered-link inline-flex items-center gap-2 text-sm transition-colors">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        Powered by LinkHop
                    </a>
                </div>
                @endunless
            </div>
        </div>
    </div>
    <script nonce="{{ Vite::cspNonce() }}">
        document.querySelectorAll('.countdown-timer').forEach(timer => {
            const targetDate = new Date(timer.dataset.target);
            function updateCountdown() {
                const now = new Date();
                const diff = targetDate - now;
                if (diff <= 0) {
                    timer.querySelector('.countdown-days').textContent = '00';
                    timer.querySelector('.countdown-hours').textContent = '00';
                    timer.querySelector('.countdown-minutes').textContent = '00';
                    timer.querySelector('.countdown-seconds').textContent = '00';
                    return;
                }
                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                timer.querySelector('.countdown-days').textContent = String(days).padStart(2, '0');
                timer.querySelector('.countdown-hours').textContent = String(hours).padStart(2, '0');
                timer.querySelector('.countdown-minutes').textContent = String(minutes).padStart(2, '0');
                timer.querySelector('.countdown-seconds').textContent = String(seconds).padStart(2, '0');
            }
            updateCountdown();
            setInterval(updateCountdown, 1000);
        });
        function downloadVCard(name, phone, email, company) {
            const vcard = `BEGIN:VCARD
VERSION:3.0
FN:${name || ''}
ORG:${company || ''}
TEL:${phone || ''}
EMAIL:${email || ''}
END:VCARD`;
            const blob = new Blob([vcard], { type: 'text/vcard' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${(name || 'contact').replace(/\s+/g, '_')}.vcf`;
            a.click();
            URL.revokeObjectURL(url);
        }
    </script>
    <script nonce="{{ Vite::cspNonce() }}">
        const qrSettings = @json($bioPage->qr_settings ?? []);
        const qrColor = qrSettings.dotsOptions?.color || qrSettings.color || '#000000';
        const qrBgColor = qrSettings.backgroundOptions?.color || qrSettings.bg_color || '#ffffff';
        const qrDotStyle = qrSettings.dotsOptions?.type || qrSettings.dot_style || 'rounded-sm';
        const qrCornerSquareType = qrSettings.cornersSquareOptions?.type || qrSettings.corner_style || 'extra-rounded';
        const qrCornerSquareColor = qrSettings.cornersSquareOptions?.color || qrColor;
        const qrCornerDotType = qrSettings.cornersDotOptions?.type || (qrCornerSquareType === 'extra-rounded' ? 'dot' : qrCornerSquareType);
        const qrCornerDotColor = qrSettings.cornersDotOptions?.color || qrColor;
        const qrLogoUrl = qrSettings.image || qrSettings.logo_url || null;
        let shareQrInstance = null;
        // Alpine comes from app.js; the QR library loads only when the share dialog opens (P2).
        let qrLibrary = null;
        function loadQrLibrary() {
            if (window.QRCodeStyling) return Promise.resolve();
            qrLibrary ??= new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/qr-code-styling@1.6.0-rc.1/lib/qr-code-styling.js';
                script.nonce = @js(Vite::cspNonce());
                script.addEventListener('load', resolve);
                script.addEventListener('error', () => { qrLibrary = null; reject(); });
                document.head.appendChild(script);
            });
            return qrLibrary;
        }
        // The address without any query, plus ?src= so scans of this code are counted as QR visits.
        function shareUrl(src) {
            const u = new URL(window.location.href);
            u.search = '';
            u.hash = '';
            if (src) u.searchParams.set('src', src);
            return u.toString();
        }
        async function initShareQR() {
            const container = document.getElementById('share-qr-container');
            if (!container || shareQrInstance) return;
            try { await loadQrLibrary(); } catch (e) { return; }
            if (shareQrInstance) return;
            shareQrInstance = new QRCodeStyling({
                width: 120,
                height: 120,
                type: 'svg',
                data: shareUrl('qr'),
                dotsOptions: {
                    color: qrColor,
                    type: qrDotStyle
                },
                backgroundOptions: {
                    color: qrBgColor
                },
                cornersSquareOptions: {
                    color: qrCornerSquareColor,
                    type: qrCornerSquareType
                },
                cornersDotOptions: {
                    color: qrCornerDotColor,
                    type: qrCornerDotType
                },
                imageOptions: {
                    crossOrigin: 'anonymous',
                    margin: 5,
                    imageSize: 0.4
                },
                image: qrLogoUrl || undefined
            });
            container.innerHTML = '';
            shareQrInstance.append(container);
        }
        // A 1080x1920 picture for stories: the page's colours, picture, name, a short line and a QR code that counts as a QR visit.
        async function downloadShareCard() {
            try { await loadQrLibrary(); } catch (e) { return; }
            if (!window.QRCodeStyling) return;
            const W = 1080, H = 1920, colors = @js($cardColors), name = @js($bioPage->title), line = @js(html_entity_decode((string) $bioPage->bio)), avatar = @js($bioPage->avatar_url ? Storage::disk('public')->url($bioPage->avatar_url) : null), shown = @js(preg_replace('#^https?://#', '', rtrim(public_url('b/'.$bioPage->slug), '/')));
            const family = getComputedStyle(document.body).fontFamily;
            try { await Promise.all([document.fonts.load('700 72px ' + family), document.fonts.load('400 38px ' + family)]); } catch (e) {}
            const c = document.createElement('canvas');
            c.width = W; c.height = H;
            const g = c.getContext('2d');
            const grad = g.createLinearGradient(0, 0, W, H);
            colors.forEach((col, i) => grad.addColorStop(colors.length === 1 ? 0 : i / (colors.length - 1), col));
            g.fillStyle = colors.length === 1 ? colors[0] : grad;
            g.fillRect(0, 0, W, H);
            const lum = (hex) => { const n = parseInt(hex.slice(1), 16); return (0.299 * (n >> 16) + 0.587 * ((n >> 8) & 255) + 0.114 * (n & 255)) / 255; };
            const light = lum(colors[0]) > 0.62;
            const ink = light ? '#0f172a' : '#ffffff';
            g.textAlign = 'center';
            // picture
            const cx = W / 2, cy = 390, r = 190;
            g.save();
            g.beginPath(); g.arc(cx, cy, r, 0, Math.PI * 2); g.closePath(); g.clip();
            let drawn = false;
            if (avatar) {
                try {
                    const img = await new Promise((ok, no) => { const i = new Image(); i.crossOrigin = 'anonymous'; i.onload = () => ok(i); i.onerror = no; i.src = avatar; });
                    const s = Math.max(2 * r / img.width, 2 * r / img.height);
                    g.drawImage(img, cx - img.width * s / 2, cy - img.height * s / 2, img.width * s, img.height * s);
                    drawn = true;
                } catch (e) {}
            }
            if (!drawn) { g.fillStyle = 'rgba(255,255,255,0.25)'; g.fillRect(cx - r, cy - r, 2 * r, 2 * r); g.fillStyle = ink; g.font = '700 190px ' + family; g.textBaseline = 'middle'; g.fillText((name || '?').trim().charAt(0).toUpperCase(), cx, cy + 10); }
            g.restore();
            g.lineWidth = 10; g.strokeStyle = 'rgba(255,255,255,0.7)'; g.beginPath(); g.arc(cx, cy, r, 0, Math.PI * 2); g.stroke();
            g.textBaseline = 'alphabetic';
            const wrap = (text, font, max, lines) => { g.font = font; const words = String(text).split(/\s+/); const out = []; let cur = ''; for (const w of words) { const t = cur ? cur + ' ' + w : w; if (g.measureText(t).width > max && cur) { out.push(cur); cur = w; } else cur = t; } if (cur) out.push(cur); if (out.length > lines) { out.length = lines; out[lines - 1] = out[lines - 1].replace(/\s*\S*$/, '') + '…'; } return out; };
            g.fillStyle = ink;
            let y = 680;
            wrap(name, '700 78px ' + family, 900, 2).forEach(l => { g.font = '700 78px ' + family; g.fillText(l, cx, y); y += 92; });
            y += 10;
            g.globalAlpha = 0.85;
            wrap(line, '400 40px ' + family, 860, 3).forEach(l => { g.font = '400 40px ' + family; g.fillText(l, cx, y); y += 54; });
            g.globalAlpha = 1;
            // QR on a white card
            const qr = new QRCodeStyling({ width: 560, height: 560, type: 'canvas', data: shareUrl('qr'), margin: 0, dotsOptions: { color: '#111827', type: qrDotStyle }, backgroundOptions: { color: '#ffffff' }, cornersSquareOptions: { color: '#111827', type: qrCornerSquareType }, cornersDotOptions: { color: '#111827', type: qrCornerDotType }, qrOptions: { errorCorrectionLevel: 'M' } });
            const blob = await qr.getRawData('png');
            const qimg = await createImageBitmap(blob);
            const cardY = 1020, pad = 50, size = 560 + pad * 2;
            g.fillStyle = '#ffffff';
            g.beginPath(); (g.roundRect ? g.roundRect(cx - size / 2, cardY, size, size, 48) : g.rect(cx - size / 2, cardY, size, size)); g.fill();
            g.drawImage(qimg, cx - 280, cardY + pad, 560, 560);
            g.fillStyle = ink;
            g.font = '600 44px ' + family;
            g.fillText(shown, cx, cardY + size + 90);
            g.globalAlpha = 0.7; g.font = '400 34px ' + family;
            g.fillText(@js(__('Scan to open')), cx, cardY + size + 145);
            g.globalAlpha = 1;
            c.toBlob((b) => { const a = document.createElement('a'); a.href = URL.createObjectURL(b); a.download = @js($bioPage->slug).concat('-story.png'); document.body.appendChild(a); a.click(); a.remove(); setTimeout(() => URL.revokeObjectURL(a.href), 4000); }, 'image/png');
        }
        async function downloadShareQR() {
            if (!shareQrInstance) {
                await initShareQR();
            }
            if (!window.QRCodeStyling) return;
            const qrSize = 400;
            const padding = 40;
            const totalSize = qrSize + (padding * 2);
            const downloadQR = new QRCodeStyling({
                width: qrSize,
                height: qrSize,
                type: 'canvas',
                data: shareUrl('qr'),
                dotsOptions: {
                    color: qrColor,
                    type: qrDotStyle
                },
                backgroundOptions: {
                    color: qrBgColor
                },
                cornersSquareOptions: {
                    color: qrCornerSquareColor,
                    type: qrCornerSquareType
                },
                cornersDotOptions: {
                    color: qrCornerDotColor,
                    type: qrCornerDotType
                },
                backgroundOptions: {
                    color: 'transparent'
                },
                cornersSquareOptions: {
                    type: qrCornerStyle
                },
                cornersDotOptions: {
                    type: qrCornerStyle === 'extra-rounded' ? 'dot' : qrCornerStyle
                },
                imageOptions: {
                    crossOrigin: 'anonymous',
                    margin: 10,
                    imageSize: 0.4
                },
                image: qrLogoUrl || undefined
            });
            const tempContainer = document.createElement('div');
            tempContainer.style.position = 'absolute';
            tempContainer.style.left = '-9999px';
            document.body.appendChild(tempContainer);
            downloadQR.append(tempContainer);
            setTimeout(() => {
                const qrCanvas = tempContainer.querySelector('canvas');
                if (qrCanvas) {
                    const paddedCanvas = document.createElement('canvas');
                    paddedCanvas.width = totalSize;
                    paddedCanvas.height = totalSize;
                    const ctx = paddedCanvas.getContext('2d');
                    ctx.fillStyle = qrBgColor;
                    ctx.fillRect(0, 0, totalSize, totalSize);
                    ctx.drawImage(qrCanvas, padding, padding);
                    const link = document.createElement('a');
                    link.download = 'qr-{{ $bioPage->slug }}.png';
                    link.href = paddedCanvas.toDataURL('image/png', 1.0);
                    link.click();
                }
                document.body.removeChild(tempContainer);
            }, 300);
        }
        document.getElementById('share-modal').addEventListener('click', function(e) {
            if (!e.target.closest('.share-modal-content')) return;
            initShareQR();
        });
        document.querySelector('[data-show="#share-modal"]')?.addEventListener('click', () => setTimeout(initShareQR, 100));
        document.addEventListener('alpine:init', () => {
            Alpine.directive('collapse', (el, { expression }, { effect, evaluateLater }) => {
                el.style.overflow = 'hidden';
                if (!el._x_isShown) el._x_isShown = true;
                let show = evaluateLater(expression || 'true');
                effect(() => show(value => {
                    if (value) {
                        el.style.height = 'auto';
                        el.style.overflow = 'visible';
                    } else {
                        el.style.height = '0';
                        el.style.overflow = 'hidden';
                    }
                }));
            });
        });
        (function() {
            const bgAnim = document.body.dataset.bgAnimation;
            const theme = document.body.dataset.theme;
            const container = document.getElementById('bg-animation');
            if (!container) return;
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.classList.contains('lite')) return;
            if ((theme === 'neon' || theme === 'matrix') && bgAnim === 'matrix') {
                initMatrixEffect(container);
                return; // Matrix is the only animation
            }
            if (!bgAnim || bgAnim === 'none') return;
            const lightThemes = ['default', 'light', 'classic', 'minimal-light', 'pastel', 'cherry', 'ice', 'lavender', 'retro'];
            const isLightTheme = lightThemes.includes(theme) || !theme;
            const config = {
                'snow': { 
                    chars: ['❄', '❅', '❆', '✦', '•'],
                    count: 80,
                    speed: { min: 0.8, max: 2.5 },
                    wind: { min: -0.3, max: 0.5 },
                    radius: { min: 10, max: 20 },
                    color: isLightTheme ? '#6b7280' : '#dee4fd', // Gray for light, light blue for dark
                    opacity: { min: 0.4, max: 0.8 }
                },
                'leaves': { 
                    chars: ['🍂', '🍁', '🍃', '🌿'],
                    count: 50,
                    speed: { min: 1, max: 2.5 },
                    wind: { min: -1, max: 1.5 },
                    radius: { min: 18, max: 30 },
                    rotate: true,
                    opacity: { min: 0.7, max: 1 }
                },
                'rain': { 
                    isRain: true,
                    isLightTheme: isLightTheme,
                    count: 180,
                    speed: { min: 12, max: 22 },
                    wind: { min: 0, max: 0.5 },
                    length: { min: 20, max: 50 },
                    width: { min: 1, max: 2 },
                    opacity: { min: isLightTheme ? 0.2 : 0.1, max: isLightTheme ? 0.5 : 0.4 },
                    windowDroplets: true
                },
                'stars': { 
                    chars: ['✦', '✧', '★', '☆', '✨'],
                    count: 60,
                    twinkle: true,
                    radius: { min: 8, max: 16 },
                    color: isLightTheme ? '#f59e0b' : '#ffd700', // Darker gold for light themes
                    opacity: { min: 0.5, max: 1 }
                },
                'hearts': { 
                    chars: ['❤', '💕', '💗', '💖', '♥'],
                    count: 50,
                    speed: { min: 0.6, max: 1.5 },
                    wind: { min: -0.5, max: 0.5 },
                    radius: { min: 16, max: 26 },
                    opacity: { min: 0.6, max: 1 }
                },
                'confetti': { 
                    chars: ['■', '●', '▲', '◆'],
                    count: 70,
                    speed: { min: 1.5, max: 3 },
                    wind: { min: -1, max: 1 },
                    radius: { min: 8, max: 16 },
                    multicolor: true,
                    isLightTheme: isLightTheme,
                    rotate: true,
                    opacity: { min: 0.7, max: 1 }
                },
                'particles': { 
                    chars: ['●', '○', '◦'],
                    count: 60,
                    speed: { min: 0.4, max: 1 },
                    wind: { min: -0.2, max: 0.2 },
                    radius: { min: 4, max: 12 },
                    glow: true,
                    multicolor: true,
                    isLightTheme: isLightTheme,
                    opacity: { min: 0.5, max: 0.9 }
                },
                'matrix': {
                    count: 0
                }
            };
            const c = config[bgAnim];
            if (!c) return;
            const canvas = document.createElement('canvas');
            canvas.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;';
            container.appendChild(canvas);
            const ctx = canvas.getContext('2d');
            function resize() {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
            }
            resize();
            window.addEventListener('resize', resize);
            class Particle {
                constructor() {
                    this.reset(true);
                }
                reset(initial = false) {
                    this.x = Math.random() * canvas.width;
                    this.y = initial ? Math.random() * canvas.height : -50;
                    this.speed = c.speed ? c.speed.min + Math.random() * (c.speed.max - c.speed.min) : 0;
                    this.wind = c.wind ? c.wind.min + Math.random() * (c.wind.max - c.wind.min) : 0;
                    if (c.isRain) {
                        this.length = c.length.min + Math.random() * (c.length.max - c.length.min);
                        this.width = c.width.min + Math.random() * (c.width.max - c.width.min);
                    } else {
                        this.radius = c.radius.min + Math.random() * (c.radius.max - c.radius.min);
                        this.length = c.length ? c.length.min + Math.random() * (c.length.max - c.length.min) : this.radius;
                    }
                    this.opacity = c.opacity.min + Math.random() * (c.opacity.max - c.opacity.min);
                    this.char = c.chars ? c.chars[Math.floor(Math.random() * c.chars.length)] : null;
                    this.rotation = Math.random() * 360;
                    this.rotationSpeed = c.rotate ? (Math.random() - 0.5) * 4 : 0;
                    this.wobble = Math.random() * Math.PI * 2;
                    this.wobbleSpeed = 0.02 + Math.random() * 0.03;
                    if (c.multicolor) {
                        if (c.isLightTheme) {
                            this.color = `hsl(${Math.random() * 360}, 85%, 45%)`;
                        } else {
                            this.color = `hsl(${Math.random() * 360}, 70%, 60%)`;
                        }
                    } else if (c.color) {
                        this.color = c.color;
                    } else {
                        this.color = '#ffffff';
                    }
                    if (c.twinkle) {
                        this.twinklePhase = Math.random() * Math.PI * 2;
                        this.twinkleSpeed = 0.02 + Math.random() * 0.03;
                    }
                }
                update() {
                    if (c.twinkle) {
                        this.twinklePhase += this.twinkleSpeed;
                        this.opacity = c.opacity.min + (Math.sin(this.twinklePhase) + 1) / 2 * (c.opacity.max - c.opacity.min);
                        return;
                    }
                    this.wobble += this.wobbleSpeed;
                    this.y += this.speed;
                    this.x += this.wind + Math.sin(this.wobble) * 0.5;
                    this.rotation += this.rotationSpeed;
                    if (this.y > canvas.height + 20 || this.x < -50 || this.x > canvas.width + 50) {
                        this.reset();
                    }
                }
                draw() {
                    ctx.save();
                    ctx.globalAlpha = this.opacity;
                    if (c.isRain) {
                        const gradient = ctx.createLinearGradient(
                            this.x, this.y,
                            this.x + this.wind * 2, this.y + this.length
                        );
                        if (c.isLightTheme) {
                            gradient.addColorStop(0, 'rgba(100, 130, 180, 0)');
                            gradient.addColorStop(0.1, 'rgba(80, 120, 170, 0.5)');
                            gradient.addColorStop(0.9, 'rgba(60, 100, 150, 0.7)');
                            gradient.addColorStop(1, 'rgba(60, 100, 150, 0)');
                        } else {
                            gradient.addColorStop(0, 'rgba(200, 220, 255, 0)');
                            gradient.addColorStop(0.1, 'rgba(200, 220, 255, 0.6)');
                            gradient.addColorStop(0.9, 'rgba(255, 255, 255, 0.8)');
                            gradient.addColorStop(1, 'rgba(255, 255, 255, 0)');
                        }
                        ctx.strokeStyle = gradient;
                        ctx.lineWidth = this.width;
                        ctx.lineCap = 'round';
                        ctx.beginPath();
                        ctx.moveTo(this.x, this.y);
                        ctx.lineTo(this.x + this.wind * 2, this.y + this.length);
                        ctx.stroke();
                    } else {
                        ctx.translate(this.x, this.y);
                        ctx.rotate(this.rotation * Math.PI / 180);
                        if (c.glow) {
                            ctx.shadowColor = this.color;
                            ctx.shadowBlur = 10;
                        }
                        ctx.fillStyle = this.color;
                        ctx.font = `${this.radius}px sans-serif`;
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText(this.char, 0, 0);
                    }
                    ctx.restore();
                }
            }
            const particles = [];
            // Fewer particles on small screens: a phone gets about a third of the desktop count.
            const density = Math.min(1, Math.max(0.35, (window.innerWidth * window.innerHeight) / (1440 * 900)));
            for (let i = 0; i < Math.round(c.count * density); i++) {
                particles.push(new Particle());
            }
            let windowDroplets = [];
            let dropletInterval = null;
            if (c.windowDroplets) {
                const screenArea = canvas.width * canvas.height;
                const baseDroplets = Math.floor(screenArea / 25000); // ~1 droplet per 25000px²
                const maxDroplets = Math.min(80, Math.max(20, baseDroplets)); // Clamp between 20-80
                const initialDroplets = Math.floor(maxDroplets * 0.6);
                class WindowDroplet {
                    constructor(startFromTop = false) {
                        this.reset(startFromTop);
                    }
                    reset(startFromTop = false) {
                        this.x = Math.random() * canvas.width;
                        this.y = startFromTop ? -10 : Math.random() * canvas.height;
                        this.radius = 2 + Math.random() * 8;
                        this.baseOpacity = 0.12 + Math.random() * 0.2;
                        this.opacity = this.baseOpacity;
                        this.life = 120 + Math.random() * 280; // Shorter life for more dynamism
                        this.age = 0;
                        this.sliding = false;
                        this.slideSpeed = 0;
                        this.wobblePhase = Math.random() * Math.PI * 2;
                        this.wobbleSpeed = 0.02 + Math.random() * 0.03;
                        this.wobbleAmount = 0.1 + Math.random() * 0.2;
                    }
                    update() {
                        this.age++;
                        this.wobblePhase += this.wobbleSpeed;
                        this.opacity = this.baseOpacity + Math.sin(this.wobblePhase) * 0.05;
                        if (this.age > this.life && !this.sliding) {
                            this.sliding = true;
                            this.slideSpeed = 0.15 + Math.random() * 0.4;
                        }
                        if (this.sliding) {
                            this.y += this.slideSpeed;
                            this.x += Math.sin(this.wobblePhase) * this.wobbleAmount; // Wobble while sliding
                            this.slideSpeed += 0.015; // Accelerate
                            this.radius *= 0.997; // Shrink
                            this.opacity *= 0.985;
                        }
                        if (this.y > canvas.height + 20 || this.radius < 1 || this.opacity < 0.02) {
                            this.reset(true);
                        }
                    }
                    draw() {
                        ctx.save();
                        ctx.globalAlpha = this.opacity;
                        const gradient = ctx.createRadialGradient(
                            this.x - this.radius * 0.3, this.y - this.radius * 0.3, 0,
                            this.x, this.y, this.radius
                        );
                        gradient.addColorStop(0, 'rgba(255, 255, 255, 0.85)');
                        gradient.addColorStop(0.25, 'rgba(210, 230, 250, 0.5)');
                        gradient.addColorStop(0.6, 'rgba(160, 190, 220, 0.25)');
                        gradient.addColorStop(1, 'rgba(100, 150, 200, 0.08)');
                        ctx.beginPath();
                        ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                        ctx.fillStyle = gradient;
                        ctx.fill();
                        ctx.beginPath();
                        ctx.arc(this.x - this.radius * 0.25, this.y - this.radius * 0.25, this.radius * 0.3, 0, Math.PI * 2);
                        ctx.fillStyle = 'rgba(255, 255, 255, 0.65)';
                        ctx.fill();
                        ctx.restore();
                    }
                }
                for (let i = 0; i < initialDroplets; i++) {
                    windowDroplets.push(new WindowDroplet(false));
                }
                dropletInterval = setInterval(() => {
                    if (windowDroplets.length < maxDroplets) {
                        const droplet = new WindowDroplet(true);
                        droplet.y = Math.random() * canvas.height * 0.25; // Start near top
                        windowDroplets.push(droplet);
                    }
                    if (windowDroplets.length > initialDroplets && Math.random() < 0.1) {
                        const oldestSliding = windowDroplets.findIndex(d => d.sliding);
                        if (oldestSliding > -1) {
                            windowDroplets.splice(oldestSliding, 1);
                        }
                    }
                }, 300 + Math.random() * 400);
            }
            function animate() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                windowDroplets.forEach(d => {
                    d.update();
                    d.draw();
                });
                particles.forEach(p => {
                    p.update();
                    p.draw();
                });
                requestAnimationFrame(animate);
            }
            animate();
            function initMatrixEffect(container) {
                const matrixCanvas = document.createElement('canvas');
                matrixCanvas.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;opacity:0.35;';
                container.appendChild(matrixCanvas);
                const mCtx = matrixCanvas.getContext('2d');
                function resizeMatrix() {
                    matrixCanvas.width = window.innerWidth;
                    matrixCanvas.height = window.innerHeight;
                }
                resizeMatrix();
                window.addEventListener('resize', resizeMatrix);
                const chars = 'アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲン0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ<>{}[]';
                const fontSize = 16;
                const columns = Math.floor(matrixCanvas.width / fontSize);
                const drops = [];
                for (let i = 0; i < columns; i++) {
                    drops[i] = Math.random() * -100;
                }
                function drawMatrix() {
                    mCtx.fillStyle = 'rgba(0, 0, 0, 0.08)';
                    mCtx.fillRect(0, 0, matrixCanvas.width, matrixCanvas.height);
                    mCtx.font = `bold ${fontSize}px monospace`;
                    for (let i = 0; i < drops.length; i++) {
                        const char = chars[Math.floor(Math.random() * chars.length)];
                        const x = i * fontSize;
                        const y = drops[i] * fontSize;
                        const brightness = Math.random() * 0.4 + 0.6;
                        mCtx.fillStyle = `rgba(0, ${Math.floor(255 * brightness)}, ${Math.floor(50 * brightness)}, ${brightness})`;
                        mCtx.fillText(char, x, y);
                        if (Math.random() > 0.95) {
                            mCtx.fillStyle = '#ffffff';
                            mCtx.fillText(char, x, y);
                        }
                        if (y > matrixCanvas.height && Math.random() > 0.975) {
                            drops[i] = 0;
                        }
                        drops[i] += 0.5 + Math.random() * 0.4;
                    }
                    requestAnimationFrame(drawMatrix);
                }
                drawMatrix();
            }
        })();
    </script>
    <script nonce="{{ Vite::cspNonce() }}">
        // A tap on a player placeholder swaps it for the real iframe; modified clicks (new tab) keep the plain link.
        document.addEventListener('click', function (e) {
            var a = e.target.closest && e.target.closest('.bio-facade');
            if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            e.preventDefault();
            var src = a.dataset.embedSrc, kind = a.dataset.embedKind, f = document.createElement('iframe');
            if (kind === 'video' && src.indexOf('youtube.com') > -1) src += (src.indexOf('?') > -1 ? '&' : '?') + 'autoplay=1';
            f.src = src;
            f.title = a.dataset.embedTitle || '';
            f.setAttribute('allow', kind === 'video' || kind === 'tiktok' ? 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen' : 'autoplay *; clipboard-write; encrypted-media *; fullscreen *; picture-in-picture');
            if (a.dataset.embedSandbox) f.setAttribute('sandbox', a.dataset.embedSandbox);
            f.style.border = '0';
            f.style.width = '100%';
            if (kind === 'video') { f.style.height = '100%'; }
            else { f.style.height = (a.dataset.embedHeight || 200) + 'px'; if (kind === 'tiktok') { f.style.maxWidth = '325px'; f.style.margin = '0 auto'; } else { f.style.borderRadius = '8px'; } }
            a.replaceWith(f);
            f.focus();
        });
    </script>
    @if($fab = $bioPage->fabHref())
        <div aria-hidden="true" style="height: 72px"></div>
        <a href="{{ $fab }}" target="_blank" rel="nofollow noopener" class="bio-fab {{ $bioPage->fab_type }}" aria-label="{{ $bioPage->fab_label ?: ($bioPage->fab_type === 'whatsapp' ? __('Chat on WhatsApp') : __('Open')) }}">
            @if($bioPage->fab_type === 'whatsapp')<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 01-12.4 7.4L3 21l2.1-5.4A8.4 8.4 0 1121 11.5z"/></svg>@endif
            <span>{{ $bioPage->fab_label ?: ($bioPage->fab_type === 'whatsapp' ? __('Chat') : __('Open')) }}</span>
        </a>
    @endif
    @include('bio::partials.trackers')
</body>
</html>
