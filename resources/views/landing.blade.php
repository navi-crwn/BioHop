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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script nonce="{{ Vite::cspNonce() }}">
        try { const t = localStorage.getItem('helink-theme'); if (t === 'dark' || (!t && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark'); } catch (e) {}
    </script>
</head>
<body class="bio-home bg-slate-50 text-slate-900 antialiased dark:bg-[#0e1322] dark:text-slate-100 font-sans">
    @include('partials.site-header')
    <main class="product-showcase" x-data="{ theme: 'galaxy', themes: @js($themes), selected: '' }">
        <section class="showcase-wrap bio-hero" aria-labelledby="bio-title">
            <div class="bio-hero-copy">
                <p class="showcase-kicker">BioHop / {{ __('Your own little corner of the internet') }}</p>
                <h1 id="bio-title">{{ __('One page.') }}<br><span class="hop-serif">{{ __('Every part of you.') }}</span></h1>
                <p class="showcase-lead">{{ __('Your work, your links, your next big thing. Bring them together on a page that feels like you.') }}</p>
                @include('partials.home-claim')
                <div class="showcase-actions"><a class="showcase-text-link" href="#tour">{{ __('Explore the editor') }} ↓</a><a class="showcase-text-link" href="https://hel.ink/b/hel" target="_blank" rel="noopener">{{ __('Open the official profile') }} ↗</a></div>
                <p class="showcase-caption">{{ __('Try the HEL.ink profile. Tilt it, tap a link, open the FAQ.') }}</p>
            </div>
            <div class="bio-universe" data-tilt-scene id="preview" aria-label="{{ __('Interactive HEL.ink profile') }}">
                <div class="bio-orbit bio-orbit-one" aria-hidden="true"></div><div class="bio-orbit bio-orbit-two" aria-hidden="true"></div>
                <div class="bio-orbit-label bio-orbit-links" aria-hidden="true"><span>↗</span>{{ __('Your links') }}<small>hel.ink/b/hel</small></div>
                <div class="bio-orbit-label bio-orbit-style" aria-hidden="true"><span>◒</span>{{ __('Your style') }}<small>20+ {{ __('Themes') }}</small></div>
                <div class="bio-profile-float"><div class="bio-profile-tilt" data-tilt-object><x-official-profile /></div></div>
                <div class="bio-scene-controls" role="group" aria-label="{{ __('Rotate profile') }}"><button type="button" data-tilt-left aria-label="{{ __('Rotate left') }}">↶</button><button type="button" data-tilt-reset>{{ __('Reset view') }}</button><button type="button" data-tilt-right aria-label="{{ __('Rotate right') }}">↷</button><button type="button" data-motion-toggle aria-pressed="false">{{ __('Pause motion') }}</button></div>
                <p class="bio-demo-status" aria-live="polite" x-text="selected ? @js(__('Preview selected:')) + ' ' + selected : @js(__('Interactive preview · official HEL.ink content'))"></p>
            </div>
        </section>

        <div class="showcase-wrap showcase-rail"><span>{{ __('Your name. Your space.') }}</span><span>{{ __('20+ themes') }}</span><span>{{ __('No premium tier') }}</span><span>{{ __('Open source') }}</span></div>

        <section id="tour" class="showcase-wrap showcase-section" aria-labelledby="tour-title">
            <p class="showcase-kicker">01 / {{ __('From idea to your bio') }}</p>
            <h2 id="tour-title">{{ __('A little building.') }}<br><span class="hop-serif">{{ __('A lot of you.') }}</span></h2>
            <p class="showcase-lead">{{ __('Scroll through the story, or choose a step. Try the controls inside the preview.') }}</p>
            <div class="showcase-story" data-story>
                <div class="showcase-story-pin">
                    <div class="showcase-story-nav" role="group" aria-label="{{ __('Page creation steps') }}">
                        @foreach (['Add your blocks', 'Make it yours', 'Share it everywhere'] as $step)
                            <button type="button" data-story-step="{{ $loop->index }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"><span>0{{ $loop->iteration }}</span>{{ __($step) }}</button>
                        @endforeach
                    </div>
                    <div class="showcase-story-progress" aria-hidden="true"><span data-story-progress></span></div>
                    <div class="showcase-story-panel" data-story-panel="0" x-data="{ title: 'HEL.ink', extra: false, reversed: false }">
                        <div class="showcase-story-copy"><p class="showcase-kicker">01 / {{ __('Build') }}</p><h3>{{ __('Everything you do, in blocks.') }}</h3><p>{{ __('Links, text, images, videos, music, a map or a contact card, in any order.') }}</p><p class="showcase-caption">{{ __('Demo editor · changes stay in this preview.') }}</p></div>
                        <div class="bio-editor-demo">
                            <div class="showcase-window-bar"><span>● ● ●</span> BioHop / {{ __('Your page') }} <span>{{ __('Preview') }}</span></div>
                            <label>{{ __('Page title') }}<input type="text" maxlength="40" x-model="title" aria-label="{{ __('Demo page title') }}"></label>
                            <div class="bio-editor-columns"><div class="bio-block-list" :class="reversed && 'is-reversed'"><div>⠿ <span>{{ __('Shorten a link') }}</span><span>↗</span></div><div>⠿ <span>{{ __('Source code on GitHub') }}</span><span>↗</span></div><div x-cloak x-show="extra">⠿ <span>{{ __('Get in touch') }}</span><span>↗</span></div></div><div class="bio-mini-result"><img src="{{ asset('images/brand/rabbit-mark.png') }}" alt="" width="44" height="44" loading="lazy"><strong x-text="title || 'HEL.ink'">HEL.ink</strong><span>{{ __('Your links, together.') }}</span><div class="bio-mini-lines" :class="reversed && 'is-reversed'"><i>{{ __('Shorten a link') }}</i><i>GitHub</i><i x-cloak x-show="extra">{{ __('Get in touch') }}</i></div></div></div>
                            <div class="showcase-actions"><button type="button" class="showcase-button" @click="extra = !extra" :aria-pressed="extra">{{ __('Toggle contact block') }}</button><button type="button" class="showcase-button" @click="reversed = !reversed" :aria-pressed="reversed">{{ __('Reverse order') }}</button></div>
                        </div>
                    </div>
                    <div class="showcase-story-panel" data-story-panel="1">
                        <div class="showcase-story-copy"><p class="showcase-kicker">02 / {{ __('Style') }}</p><h3>{{ __('Same links. A whole new feeling.') }}</h3><p>{{ __('Tap a theme to see it on the example page. Every theme can be tuned with your own colors, fonts and CSS.') }}</p><div class="bio-theme-picks" role="group" aria-label="{{ __('Try a theme') }}">@foreach (array_slice($themes, 0, 6, true) as $key => $t)<button type="button" @click="theme = @js($key)" :aria-pressed="theme === @js($key)"><i style="background: {{ $t['background'] }}"></i>{{ $t['name'] }}</button>@endforeach</div></div>
                        <div class="bio-story-profile"><x-official-profile /></div>
                    </div>
                    <div class="showcase-story-panel" data-story-panel="2">
                        <div class="showcase-story-copy"><p class="showcase-kicker">03 / {{ __('Share') }}</p><h3>{{ __('One link. A thousand places.') }}</h3><p>{{ __('Put the link in your profiles, or print the QR code.') }}</p><a class="showcase-button primary" href="https://hel.ink/b/hel" target="_blank" rel="noopener">{{ __('Open the official profile') }} ↗</a></div>
                        <div class="bio-share-scene"><span class="bio-share-orbit" aria-hidden="true"></span><img src="{{ asset('images/brand/rabbit-mark.png') }}" alt="HEL.ink" width="88" height="88" loading="lazy"><strong>hel.ink/b/hel</strong><div><span>{{ __('Social profiles') }}</span><span>{{ __('Business cards') }}</span><span>{{ __('Your next project') }}</span></div><p>{{ __('A single address for everything you want to share.') }}</p></div>
                    </div>
                    <div class="showcase-story-footer"><span>{{ __('Scroll to explore · buttons work too') }}</span><div><button type="button" data-story-prev aria-label="{{ __('Previous step') }}">←</button><button type="button" data-story-next aria-label="{{ __('Next step') }}">→</button></div></div>
                </div>
            </div>
        </section>

        <section id="themes" class="showcase-wrap showcase-section" aria-labelledby="themes-title">
            <p class="showcase-kicker">02 / {{ __('Your signature') }}</p><h2 id="themes-title">{{ __('Pick a mood.') }}<br><span class="hop-serif">{{ __('Make it yours.') }}</span></h2>
            <div class="bio-theme-gallery"><div class="bio-gallery-preview"><x-official-profile /><p class="showcase-caption">{{ __('Theme:') }} <strong x-text="themes[theme].name"></strong></p></div><div><p class="showcase-lead">{{ __('Gradients, animations and button effects, or your own colors, fonts and CSS.') }}</p><div class="bio-theme-grid" role="group" aria-label="{{ __('Theme gallery') }}">@foreach ($themes as $key => $t)<button type="button" @click="theme = @js($key)" :aria-pressed="theme === @js($key)"><span class="bio-theme-swatch" style="background: {{ $t['background'] }}"><i style="background: {{ $t['link_bg'] }}; border: 1px solid {{ $t['link_border'] ?? '#94a3b8' }}"></i><i style="background: {{ $t['link_bg'] }}; border: 1px solid {{ $t['link_border'] ?? '#94a3b8' }}"></i></span><span>{{ $t['name'] }}</span></button>@endforeach</div></div></div>
        </section>

        <section id="features" class="showcase-wrap showcase-section" aria-labelledby="features-title">
            <p class="showcase-kicker">03 / {{ __('Small page. Big possibilities.') }}</p><h2 id="features-title">{{ __('More than a list') }}<br><span class="hop-serif">{{ __('of links.') }}</span></h2>
            <div class="showcase-bento">
                <article class="showcase-card wide"><div class="showcase-card-head"><span class="showcase-kicker">{{ __('Your workspace') }}</span><h3>{{ __('Build it your way.') }}</h3><p>{{ __('Links, text, images, videos, music, a map or a contact card, in any order.') }}</p></div><x-feature-demo name="page" label="Your page" /></article>
                <article class="showcase-card" x-data="{ period: 7 }"><span class="showcase-kicker">{{ __('Sample analytics') }}</span><h3>{{ __('See what connects.') }}</h3><p>{{ __('Views and clicks per block, counted without storing visitor IP addresses.') }}</p><div class="showcase-actions"><button class="showcase-button" type="button" @click="period = 7" :aria-pressed="period === 7">7 {{ __('days') }}</button><button class="showcase-button" type="button" @click="period = 30" :aria-pressed="period === 30">30 {{ __('days') }}</button></div><div class="showcase-chart" aria-hidden="true">@foreach ([42, 68, 35, 80, 55, 92, 72] as $height)<i :style="`height:${period === 7 ? {{ $height }} : {{ 105 - $height / 2 }}}%`"></i>@endforeach</div><p class="showcase-stat" x-text="period === 7 ? '1,284' : '4,820'">1,284</p><span class="showcase-caption">{{ __('Demo views · illustrative data') }}</span></article>
                <article class="showcase-card" x-data="{ protected: false }"><span class="showcase-kicker">{{ __('Your boundaries') }}</span><h3>{{ __('Choose who gets in.') }}</h3><p>{{ __('A password or an 18+ check before the page opens.') }}</p><div class="bio-lock-demo" :class="protected && 'is-locked'"><span aria-hidden="true" x-text="protected ? '▣' : '↗'">↗</span><strong x-text="protected ? @js(__('Password required')) : @js(__('Public page'))"></strong></div><button class="showcase-button" type="button" @click="protected = !protected" :aria-pressed="protected">{{ __('Try password protection') }}</button><p class="showcase-caption">{{ __('Demo only · your page is unchanged.') }}</p></article>
                <article class="showcase-card wide"><div class="showcase-card-head"><span class="showcase-kicker">{{ __('Designed to feel like you') }}</span><h3>{{ __('A fresh look, in a click.') }}</h3><p>{{ __('No premium tier and no locked features.') }}</p></div><x-feature-demo name="themes" label="Themes" /></article>
            </div>
        </section>

        <section id="faq" class="showcase-wrap showcase-section showcase-faq" aria-labelledby="faq-title"><p class="showcase-kicker">04 / {{ __('Good questions') }}</p><h2 id="faq-title">{{ __('A few things') }} <span class="hop-serif">{{ __('to know.') }}</span></h2>@foreach (['Is BioHop really free?' => 'Yes. No paid plan and no locked feature. The code is MIT licensed.', 'Do I need an account?' => 'Yes, a free HOP ID. The same account works in LinkHop, PixelHop and NoteHop.', 'Do you track my visitors?' => 'Views and clicks are counted with a keyed hash of the IP address, never the address itself. Pixels you add load only after the visitor agrees.', 'Can I move my page later?' => 'Yes. You can rename it, and export everything from the settings.'] as $question => $answer)<details><summary>{{ __($question) }}</summary><p>{{ __($answer) }}</p></details>@endforeach</section>

        <section class="showcase-wrap showcase-section bio-final"><p class="showcase-kicker">{{ __('Your next chapter') }}</p><h2>{{ __('The internet is big.') }}<br><span class="hop-serif">{{ __('Make a little space.') }}</span></h2><a class="showcase-button primary" href="{{ route('register') }}">{{ __('Create your bio') }} ↗</a><a class="showcase-text-link" href="https://github.com/navi-crwn/BioHop" target="_blank" rel="noopener">{{ __('Open source') }} ↗</a></section>
        <section id="family" class="showcase-wrap showcase-family" aria-label="{{ __('Part of the HOP family') }}"><p class="showcase-kicker">{{ __('One free HOP ID signs you in to every app.') }}</p><div>@foreach (collect(\Hop\Ui\Assets::family('biohop'))->reject(fn ($app) => $app['current']) as $app)<a href="{{ $app['url'] }}"><img src="{{ $app['icons']['mark']['url'] }}" alt="" width="32" height="32" loading="lazy"><span>{{ $app['name'] }}</span><span aria-hidden="true">↗</span></a>@endforeach</div></section>
    </main>
    @include('partials.site-footer')
</body>
</html>
