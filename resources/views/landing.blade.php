@php($locale = app()->getLocale())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BioHop · {{ __('One link-in-bio page for everything you do.') }}</title>
    <meta name="description" content="{{ __('Share all your links, social profiles, embeds, vCard and QR code on one fast, customizable page at hel.ink/b/yourname. Free, open source, and powered by your HOP ID.') }}">
    <link rel="canonical" href="{{ url('/') }}">
    @foreach (\App\Http\Middleware\SetLocale::LOCALES as $code => $name)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ url('/') }}?lang={{ $code }}">
    @endforeach
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="BioHop · {{ __('One link-in-bio page for everything you do.') }}">
    <meta property="og:description" content="{{ __('One page. Every part of you.') }}">
    <meta property="og:image" content="{{ route('brand.logo', ['v' => 'b10b1000']) }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" type="image/svg+xml" href="{{ route('brand.favicon', ['v' => 'b10b1000']) }}">
    <x-hop::fonts />
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/home.css', 'resources/js/home.js'])
    <link rel="stylesheet" href="{{ asset('hop-home/hop-home.css') }}?v={{ substr(md5_file(public_path('hop-home/hop-home.css')), 0, 8) }}">
    <script src="{{ asset('hop-home/hop-hero3d.js') }}?v={{ substr(md5_file(public_path('hop-home/hop-hero3d.js')), 0, 8) }}" defer></script>
    <script src="{{ asset('hop-home/hop-motion.js') }}?v={{ substr(md5_file(public_path('hop-home/hop-motion.js')), 0, 8) }}" defer></script>
    <script src="{{ asset('hop-home/hop-home.js') }}?v={{ substr(md5_file(public_path('hop-home/hop-home.js')), 0, 8) }}" defer></script>
    <script src="{{ asset('hop-home/hop-scenes.js') }}?v={{ substr(md5_file(public_path('hop-home/hop-scenes.js')), 0, 8) }}" defer></script>
    <script nonce="{{ Vite::cspNonce() }}">
        try { const t = localStorage.getItem('helink-theme'); if (t === 'dark' || (!t && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark'); } catch (e) {}
    </script>
</head>
<body class="bio-home bg-slate-50 text-slate-900 antialiased dark:bg-[#0e1322] dark:text-slate-100 font-sans">
    @include('partials.site-header')
    <main class="product-showcase hop-home bio-home-main" x-data="{ theme: 'galaxy', themes: @js($themes), selected: '' }">
        {{-- 1. hero: claim a name first; beside it the logo assembles from pixels and becomes the official page --}}
        <section class="h-hero h-tool bio-hero" aria-labelledby="bio-title">
            <div class="bio-hero-copy">
                <div class="h-tool-box">@include('partials.home-claim')</div>
                <p class="h-chip">BioHop · hel.ink/b/{{ __('yourname') }}</p>
                <h1 id="bio-title">{{ __('One page.') }} <em>{{ __('Every part of you.') }}</em></h1>
                <p class="h-lead">{{ __('Your work, your links, your next big thing. Bring them together on a page that feels like you.') }}</p>
                <div class="h-cta"><a class="h-pill" href="#tour">{{ __('Explore the editor') }} ↓</a><a class="h-pill" href="https://hel.ink/b/hel" target="_blank" rel="noopener">{{ __('Open the official profile') }} ↗</a></div>
                <p class="h-small">{{ __('Try the HEL.ink profile. Tilt it, tap a link, open the FAQ.') }}</p>
            </div>
            @php($live = \App\Bio\OfficialPage::embedUrl())
            <div class="bio-stage" id="preview" data-cycle-hold aria-label="{{ __('Interactive HEL.ink profile') }}">
                <figure class="h-art bio-logo-art"><picture><source srcset="{{ asset('hop-home/home/biohop-dark.webp') }}" media="(prefers-color-scheme: dark)"><img src="{{ asset('hop-home/home/biohop-light.webp') }}" alt="" width="460" height="460" fetchpriority="high"></picture>
                    <canvas data-hop-hero="biohop" data-morph="cycle" tabindex="0" aria-label="{{ __('The BioHop logo assembles, then becomes the page') }}"></canvas></figure>
                <div class="bio-phone" data-fit="360">
                    <div class="bio-screen">
                        <div class="bio-static" @if ($live) aria-hidden="true" @endif><x-official-profile :interactive="false" /></div>
                        @if ($live)<iframe class="bio-live" src="{{ $live }}" title="{{ __('Interactive HEL.ink profile') }}" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>@endif
                    </div>
                </div>
                <p class="bio-live-note">@if ($live)<i aria-hidden="true"></i>{{ __('Live: this is the real page at') }} <a href="{{ public_url('b/hel') }}" target="_blank" rel="noopener">hel.ink/b/hel</a>@else{{ __('Interactive preview · official HEL.ink content') }}@endif</p>
            </div>
        </section>

        @include('partials.home-scenes')
    </main>
    @include('partials.site-footer')
</body>
</html>
