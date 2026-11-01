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
    <link rel="stylesheet" href="{{ asset('hop-home/hop-home.css') }}">
    <script src="{{ asset('hop-home/hop-hero3d.js') }}" defer></script>
    <script src="{{ asset('hop-home/hop-motion.js') }}" defer></script>
    <script src="{{ asset('hop-home/hop-home.js') }}" defer></script>
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
            <div class="bio-universe" data-tilt-scene id="preview" aria-label="{{ __('Interactive HEL.ink profile') }}">
                <figure class="h-art bio-logo-art"><picture><source srcset="{{ asset('hop-home/home/biohop-dark.webp') }}" media="(prefers-color-scheme: dark)"><img src="{{ asset('hop-home/home/biohop-light.webp') }}" alt="" width="460" height="460" fetchpriority="high"></picture><canvas data-hop-hero="biohop" data-morph="phone" tabindex="0" aria-label="{{ __('The BioHop logo assembles, then becomes the page') }}"></canvas></figure>
                <div class="bio-orbit bio-orbit-one" aria-hidden="true"></div><div class="bio-orbit bio-orbit-two" aria-hidden="true"></div>
                <div class="bio-orbit-label bio-orbit-links" aria-hidden="true"><span>↗</span>{{ __('Your links') }}<small>hel.ink/b/hel</small></div>
                <div class="bio-orbit-label bio-orbit-style" aria-hidden="true"><span>◒</span>{{ __('Your style') }}<small>20+ {{ __('Themes') }}</small></div>
                <div class="bio-profile-float"><div class="bio-profile-tilt" data-tilt-object><x-official-profile /></div></div>
                <div class="bio-scene-controls" role="group" aria-label="{{ __('Rotate profile') }}"><button type="button" data-tilt-left aria-label="{{ __('Rotate left') }}">↶</button><button type="button" data-tilt-reset>{{ __('Reset view') }}</button><button type="button" data-tilt-right aria-label="{{ __('Rotate right') }}">↷</button><button type="button" data-motion-toggle aria-pressed="false">{{ __('Pause motion') }}</button></div>
                <p class="bio-demo-status" aria-live="polite" x-text="selected ? @js(__('Preview selected:')) + ' ' + selected : @js(__('Interactive preview · official HEL.ink content'))"></p>
            </div>
        </section>

        {{-- 2. the rail --}}
        <div class="h-rail" data-rv><span>{{ __('Your name. Your space.') }}</span><span>{{ __('20+ themes') }}</span><span>{{ __('No premium tier') }}</span><span>{{ __('Open source') }}</span><a href="#tour">{{ __('Explore the editor') }} ↓</a></div>

        {{-- 3. from idea to your bio: pinned, the scroll turns the steps --}}
        <section id="tour" class="h-sec" aria-labelledby="tour-title">
            <p class="h-label"><span class="n">01</span> · <span>{{ __('From idea to your bio') }}</span></p>
            <h2 id="tour-title" data-split>{{ __('A little building.') }} <em>{{ __('A lot of you.') }}</em></h2>
            <p class="h-sub" data-rv>{{ __('Scroll through the story, or choose a step. Try the controls inside the preview.') }}</p>
            <div class="h-story" id="bio-story" data-scroll-story style="--story-n:3">
                <div class="h-story-pin">
                    <div class="h-story-nav">@foreach (['Add your blocks', 'Make it yours', 'Share it everywhere'] as $step)<button type="button" data-story-step="{{ $loop->index }}"><span>0{{ $loop->iteration }}</span>{{ __($step) }}</button>@endforeach</div>
                    <div class="h-story-progress" aria-hidden="true"><span data-story-progress></span></div>
                    <div class="h-story-panel" data-story-panel x-data="{ title: 'HEL.ink', extra: false, reversed: false }">
                        <div class="h-story-copy"><p class="h-label"><span class="n">01</span> · <span>{{ __('Build') }}</span></p><h3>{{ __('Everything you do, in blocks.') }}</h3><p>{{ __('Links, text, images, videos, music, a map or a contact card, in any order.') }}</p><p class="h-small">{{ __('Demo editor · changes stay in this preview.') }}</p></div>
                        <div class="bio-editor-demo">
                            <div class="showcase-window-bar"><span>● ● ●</span> BioHop / {{ __('Your page') }} <span>{{ __('Preview') }}</span></div>
                            <label>{{ __('Page title') }}<input type="text" maxlength="40" x-model="title" aria-label="{{ __('Demo page title') }}"></label>
                            <div class="bio-editor-columns"><div class="bio-block-list" :class="reversed && 'is-reversed'"><div>⠿ <span>{{ __('Shorten a link') }}</span><span>↗</span></div><div>⠿ <span>{{ __('Source code on GitHub') }}</span><span>↗</span></div><div x-cloak x-show="extra">⠿ <span>{{ __('Get in touch') }}</span><span>↗</span></div></div><div class="bio-mini-result"><img src="{{ asset('images/brand/rabbit-mark.png') }}" alt="" width="44" height="44" loading="lazy"><strong x-text="title || 'HEL.ink'">HEL.ink</strong><span>{{ __('Your links, together.') }}</span><div class="bio-mini-lines" :class="reversed && 'is-reversed'"><i>{{ __('Shorten a link') }}</i><i>GitHub</i><i x-cloak x-show="extra">{{ __('Get in touch') }}</i></div></div></div>
                            <div class="showcase-actions"><button type="button" class="showcase-button" @click="extra = !extra" :aria-pressed="extra">{{ __('Toggle contact block') }}</button><button type="button" class="showcase-button" @click="reversed = !reversed" :aria-pressed="reversed">{{ __('Reverse order') }}</button></div>
                        </div>
                    </div>
                    <div class="h-story-panel" data-story-panel hidden>
                        <div class="h-story-copy"><p class="h-label"><span class="n">02</span> · <span>{{ __('Style') }}</span></p><h3>{{ __('Same links. A whole new feeling.') }}</h3><p>{{ __('Tap a theme to see it on the example page. Every theme can be tuned with your own colors, fonts and CSS.') }}</p><div class="bio-theme-picks" role="group" aria-label="{{ __('Try a theme') }}">@foreach (array_slice($themes, 0, 6, true) as $key => $t)<button type="button" @click="theme = @js($key)" :aria-pressed="theme === @js($key)"><i style="background: {{ $t['background'] }}"></i>{{ $t['name'] }}</button>@endforeach</div></div>
                        <div class="bio-story-profile"><x-official-profile /></div>
                    </div>
                    <div class="h-story-panel" data-story-panel hidden>
                        <div class="h-story-copy"><p class="h-label"><span class="n">03</span> · <span>{{ __('Share') }}</span></p><h3>{{ __('One link. A thousand places.') }}</h3><p>{{ __('Put the link in your profiles, or print the QR code.') }}</p><a class="h-btn primary" href="https://hel.ink/b/hel" target="_blank" rel="noopener">{{ __('Open the official profile') }} ↗</a></div>
                        <div class="bio-share-scene"><span class="bio-share-orbit" aria-hidden="true"></span><img src="{{ asset('images/brand/rabbit-mark.png') }}" alt="HEL.ink" width="88" height="88" loading="lazy"><strong>hel.ink/b/hel</strong><div><span>{{ __('Social profiles') }}</span><span>{{ __('Business cards') }}</span><span>{{ __('Your next project') }}</span></div><p>{{ __('A single address for everything you want to share.') }}</p></div>
                    </div>
                    <div class="h-story-foot"><span>{{ __('Scroll to explore · buttons work too') }}</span><div><button type="button" data-story-prev aria-label="{{ __('Previous step') }}">←</button><button type="button" data-story-next aria-label="{{ __('Next step') }}">→</button></div></div>
                </div>
            </div>
        </section>

        {{-- 4. themes --}}
        <section id="themes" class="h-sec" aria-labelledby="themes-title">
            <p class="h-label"><span class="n">02</span> · <span>{{ __('Themes') }}</span></p><h2 id="themes-title" data-split>{{ __('Pick a mood.') }} <em>{{ __('Make it yours.') }}</em></h2><p class="h-sub" data-rv>{{ __('Gradients, animations and button effects, or your own colors, fonts and CSS.') }}</p>
            <div class="bio-theme-gallery" data-rv><div class="bio-gallery-preview"><x-official-profile /><p class="showcase-caption">{{ __('Theme:') }} <strong x-text="themes[theme].name"></strong></p></div><div><div class="bio-theme-grid" role="group" aria-label="{{ __('Theme gallery') }}">@foreach ($themes as $key => $t)<button type="button" @click="theme = @js($key)" :aria-pressed="theme === @js($key)"><span class="bio-theme-swatch" style="background: {{ $t['background'] }}"><i style="background: {{ $t['link_bg'] }}; border: 1px solid {{ $t['link_border'] ?? '#94a3b8' }}"></i><i style="background: {{ $t['link_bg'] }}; border: 1px solid {{ $t['link_border'] ?? '#94a3b8' }}"></i></span><span>{{ $t['name'] }}</span></button>@endforeach</div></div></div>
        </section>

        {{-- 5. blocks --}}
        <section class="h-sec h-stage" id="blocks" aria-labelledby="blocks-title" x-data="{ picked: ['link', 'text'], kinds: { link: @js(__('Link')), text: @js(__('Text')), image: @js(__('Image')), video: @js(__('Video')), music: @js(__('Music')), map: @js(__('Map')), contact: @js(__('Contact card')) } }">
            <div class="h-stage-copy"><p class="h-label"><span class="n">03</span> · <span>{{ __('Blocks') }}</span></p><h2 id="blocks-title" data-split>{{ __('Not just links.') }} <em>{{ __('Everything you make.') }}</em></h2><p class="h-sub" data-rv>{{ __('Links, text, images, videos, music, a map or a contact card, in any order.') }}</p><div class="h-tabs" role="group" aria-label="{{ __('Add a block') }}" data-rv><template x-for="(label, kind) in kinds" :key="kind"><button type="button" @click="picked.length < 8 && picked.push(kind)" x-text="'+ ' + label"></button></template></div></div>
            <div class="h-stage-visual h-window" data-rv><div class="showcase-window-bar"><span>● ● ●</span> hel.ink/b/{{ __('yourname') }} <span>{{ __('Demo') }}</span></div><div class="h-window-body bio-blocks-demo"><template x-for="(kind, i) in picked" :key="i"><div class="bio-block" :data-kind="kind"><span x-text="kinds[kind]"></span><button type="button" @click="picked.splice(i, 1)" aria-label="{{ __('Remove') }}">✕</button></div></template><p class="h-small" x-show="!picked.length">{{ __('Add a block to begin.') }}</p><p class="h-small">{{ __('Demo only · your page is unchanged.') }}</p></div></div>
        </section>

        {{-- 6. analytics --}}
        <section class="h-sec h-stage" id="analytics" aria-labelledby="analytics-title">
            <div class="h-stage-copy"><p class="h-label"><span class="n">04</span> · <span>{{ __('Analytics') }}</span></p><h2 id="analytics-title" data-split>{{ __('See what connects.') }}</h2><p class="h-sub" data-rv>{{ __('Views and clicks per block, counted without storing visitor IP addresses.') }}</p></div>
            <article class="h-stage-visual h-window" data-rv x-data="{ period: 7 }"><div class="showcase-window-bar"><span>● ● ●</span> BioHop / {{ __('Analytics') }} <span>{{ __('Sample analytics') }}</span></div><div class="h-window-body"><div class="h-tabs"><button type="button" @click="period = 7" :aria-pressed="period === 7">7 {{ __('days') }}</button><button type="button" @click="period = 30" :aria-pressed="period === 30">30 {{ __('days') }}</button></div><div class="h-chart" aria-hidden="true">@foreach ([42, 68, 35, 80, 55, 92, 72] as $height)<i :style="`height:${period === 7 ? {{ $height }} : {{ 105 - $height / 2 }}}%`"></i>@endforeach</div><p class="h-stat" x-text="period === 7 ? '1,284' : '4,820'">1,284</p><div class="h-ranks"><div><span>{{ __('Views') }}</span><strong x-text="period === 7 ? '1,284' : '4,820'">1,284</strong></div><div><span>{{ __('Clicks') }}</span><strong x-text="period === 7 ? '412' : '1,590'">412</strong></div><div><span>{{ __('Top block') }}</span><strong>{{ __('Shorten a link') }}</strong></div></div><p class="h-small">{{ __('Demo views · illustrative data') }}</p></div></article>
        </section>

        {{-- 7. boundaries --}}
        <section class="h-sec h-stage" id="boundaries" aria-labelledby="boundaries-title">
            <div class="h-stage-copy"><p class="h-label"><span class="n">05</span> · <span>{{ __('Your boundaries') }}</span></p><h2 id="boundaries-title" data-split>{{ __('Choose who gets in.') }}</h2><p class="h-sub" data-rv>{{ __('A password or an 18+ check before the page opens.') }}</p></div>
            <article class="h-stage-visual h-window" data-rv x-data="{ protected: false }"><div class="showcase-window-bar"><span>● ● ●</span> hel.ink/b/{{ __('yourname') }} <span>{{ __('Demo') }}</span></div><div class="h-window-body"><div class="bio-lock-demo" :class="protected && 'is-locked'"><span aria-hidden="true" x-text="protected ? '▣' : '↗'">↗</span><strong x-text="protected ? @js(__('Password required')) : @js(__('Public page'))">{{ __('Public page') }}</strong></div><button class="primary" type="button" @click="protected = !protected" :aria-pressed="protected">{{ __('Try password protection') }}</button><p class="h-small">{{ __('Demo only · your page is unchanged.') }}</p></div></article>
        </section>

        {{-- 8. your own style --}}
        <section class="h-sec h-stage" id="style" aria-labelledby="style-title" x-data="{ font: 'sans', color: '#7c3aed' }">
            <div class="h-stage-copy"><p class="h-label"><span class="n">06</span> · <span>{{ __('Your style') }}</span></p><h2 id="style-title" data-split>{{ __('Your fonts.') }} <em>{{ __('Your colours. Your CSS.') }}</em></h2><p class="h-sub" data-rv>{{ __('Start from a theme, then change the font, the colours or the whole stylesheet. Nothing is locked.') }}</p><div class="h-tabs" role="group" aria-label="{{ __('Font') }}" data-rv><button type="button" @click="font = 'sans'" :aria-pressed="font === 'sans'">Inter</button><button type="button" @click="font = 'serif'" :aria-pressed="font === 'serif'">Instrument Serif</button><button type="button" @click="font = 'mono'" :aria-pressed="font === 'mono'">JetBrains Mono</button><label class="bio-color"><input type="color" x-model="color" aria-label="{{ __('Button colour') }}"><span x-text="color"></span></label></div></div>
            <div class="h-stage-visual h-window" data-rv><div class="showcase-window-bar"><span>● ● ●</span> {{ __('Custom CSS') }} <span>{{ __('Demo') }}</span></div><div class="h-window-body bio-style-demo" :style="`--demo-color:${color}`" :data-font="font"><strong>HEL.ink</strong><p>{{ __('Short links and link in bio pages. Free, open source, and yours to host.') }}</p><i>{{ __('Shorten a link') }} ↗</i><i>{{ __('Source code on GitHub') }} ↗</i><pre dir="ltr">.link { background: <span x-text="color"></span>; font-family: <span x-text="{sans:'Inter',serif:'Instrument Serif',mono:'JetBrains Mono'}[font]"></span>; }</pre></div></div>
        </section>

        {{-- 9. tutorial --}}
        <section class="h-sec" id="tutorial" aria-labelledby="tutorial-title">
            <p class="h-label"><span class="n">07</span> · <span>{{ __('Tutorial') }}</span></p><h2 id="tutorial-title" data-split>{{ __('Three steps.') }} <em>{{ __('One page.') }}</em></h2><p class="h-sub" data-rv>{{ __('Free forever with your HOP ID · No ads, no trackers') }}</p>
            <div class="h-tut" data-rv-group><article><h3>{{ __('Claim your name') }}</h3><p>{{ __('Type it in the box at the top. If it is free, it is yours with a HOP ID.') }}</p><div class="h-tut-art">hel.ink/b/{{ __('yourname') }} ✓</div></article><article><h3>{{ __('Add your blocks') }}</h3><p>{{ __('Links, text, images, videos, music, a map or a contact card, in any order.') }}</p><div class="h-tut-art">⠿ {{ __('Link') }} · ⠿ {{ __('Text') }} · ⠿ {{ __('Map') }}</div></article><article><h3>{{ __('Share it everywhere') }}</h3><p>{{ __('Put the link in your profiles, or print the QR code.') }}</p><div class="h-tut-art">▣ hel.ink/b/{{ __('yourname') }}</div></article></div>
        </section>

        {{-- 10. self-host --}}
        <section class="h-sec h-stage" id="open" aria-labelledby="open-title">
            <div class="h-stage-copy"><p class="h-label"><span class="n">08</span> · <span>MIT</span></p><h2 id="open-title" data-split>{{ __('Open source') }} <em>{{ __('and yours to host.') }}</em></h2><p class="h-sub" data-rv>{{ __('Yes. No paid plan and no locked feature. The code is MIT licensed.') }}</p><div class="h-badges" data-rv><a class="h-badge" href="https://github.com/navi-crwn/BioHop/blob/main/LICENSE"><span>{{ __('License') }}</span><b>MIT</b></a><span class="h-badge"><span>PHP</span><b>8.3+</b></span><span class="h-badge"><span>Laravel</span><b>12</b></span></div><div class="h-cta"><a class="h-pill dark" href="https://github.com/navi-crwn/BioHop">GitHub ↗</a></div></div>
            <div class="h-stage-visual h-shell" data-rv><div class="h-shell-bar"><i></i><i></i><i></i><span>bash</span></div><div class="h-term"><code id="install-sh">git clone https://github.com/navi-crwn/BioHop.git && cd BioHop && composer install && npm install && npm run build</code><button type="button" data-copy-text data-copy-from="#install-sh">{{ __('Copy') }}</button></div><pre>cp .env.example .env && php artisan key:generate
php artisan migrate
<span class="ok">✓ php artisan serve</span></pre></div>
        </section>

        {{-- 11. FAQ --}}
        <section id="faq" class="h-sec" aria-labelledby="faq-title"><p class="h-label"><span class="n">09</span> · <span>{{ __('Good questions') }}</span></p><h2 id="faq-title" data-split>{{ __('A few things') }} <em>{{ __('to know.') }}</em></h2><p class="h-sub" data-rv>{{ __('No premium tier and no locked features.') }}</p><div class="h-faq-grid">@foreach (['Is BioHop really free?' => 'Yes. No paid plan and no locked feature. The code is MIT licensed.', 'Do I need an account?' => 'Yes, a free HOP ID. The same account works in LinkHop, PixelHop and NoteHop.', 'Do you track my visitors?' => 'Views and clicks are counted with a keyed hash of the IP address, never the address itself. Pixels you add load only after the visitor agrees.', 'Can I move my page later?' => 'Yes. You can rename it, and export everything from the settings.', 'Can I have more than one page?' => 'Yes. One HOP ID can hold several pages, each with its own name, theme and blocks.', 'Can I put a password on my page?' => 'Yes. Add a password or an 18+ check, and visitors see a gate before the page opens.'] as $question => $answer)<details><summary>{{ __($question) }}</summary><p>{{ __($answer) }}</p></details>@endforeach</div></section>

        {{-- 12. family --}}
        <section id="family" class="h-sec" aria-labelledby="family-title"><p class="h-label"><span class="n">10</span> · <span>HOP</span></p><h2 id="family-title" data-split><em>{{ __('Part of the HOP family') }}</em></h2><p class="h-sub" data-rv>{{ __('One free HOP ID signs you in to every app.') }}</p><div class="h-family" data-rv-group>@foreach (collect(\Hop\Ui\Assets::family('biohop'))->reject(fn ($app) => $app['current']) as $app)<a href="{{ $app['url'] }}"><img src="{{ $app['icons']['mark']['url'] }}" alt="" width="32" height="32" loading="lazy">{{ $app['name'] }}</a>@endforeach</div></section>

        {{-- 13. the last word --}}
        <section class="h-sec h-final bio-final" aria-labelledby="final-title"><p class="h-label"><span class="n">11</span> · <span>{{ __('Your next chapter') }}</span></p><h2 id="final-title" data-split>{{ __('The internet is big.') }} <em>{{ __('Make a little space.') }}</em></h2><p class="h-sub">{{ __('Your work, your links, your next big thing. Bring them together on a page that feels like you.') }}</p><div class="h-cta"><a class="h-pill dark" href="{{ route('register') }}">{{ __('Create your bio') }} ↗</a><a class="h-pill" href="#preview">{{ __('Claim my page') }} ↑</a></div></section>
    </main>
    @include('partials.site-footer')
</body>
</html>
