{{-- The BioHop home below the hero: one scroll scene per feature (resources/js/home.js). Every scene is complete as plain HTML; the script only adds motion. --}}
<div class="bx-marq" data-bx-marq aria-label="{{ __('What you get') }}"><div>@foreach (['Your name', '20+ themes', 'Blocks for everything', 'QR codes', 'Analytics without tracking', 'Password and 18+ gate', 'Your own CSS', 'Open source', '8 languages'] as $t)<span>{{ __($t) }}</span>@endforeach</div></div>

<div class="bx-nums" data-bx-nums>
    <div><b data-count="20" data-suffix="+">20+</b><span>{{ __('themes') }}</span></div>
    <div><b data-count="{{ count(\App\Http\Middleware\SetLocale::LOCALES) }}">{{ count(\App\Http\Middleware\SetLocale::LOCALES) }}</b><span>{{ __('languages') }}</span></div>
    <div><b data-count="0">0</b><span>{{ __('ads and trackers') }}</span></div>
    <div><b data-count="100" data-suffix="%">100%</b><span>{{ __('free and open source') }}</span></div>
</div>

{{-- 01 blocks --}}
<section id="blocks" class="bx" aria-labelledby="blocks-title">
    <div class="bx-wrap">
        <div class="bx-head"><p class="h-label"><span class="n">01</span> · <span>{{ __('Blocks') }}</span></p><h2 id="blocks-title"><span>{{ __('Not just links.') }} <em>{{ __('Everything you make.') }}</em></span></h2><p class="h-sub"><span>{{ __('Add a block, drag it where you want it. Each kind knows how to look good on a phone.') }}</span></p></div>
        <div class="bx-blocks" data-bx-blocks>
        <div class="bx-bento" data-bx-bento>
            <article class="bx-cell"><div class="bx-vis v-link" aria-hidden="true"><span class="bx-pill">{{ __('Portfolio') }} <i>↗</i></span><span class="bx-pill">{{ __('Book a call') }} <i>↗</i></span><span class="bx-cursor"></span></div><h3>{{ __('Link') }}</h3><p>{{ __('Any address, with an icon, a thumbnail and a style of its own.') }}</p></article>
            <article class="bx-cell"><div class="bx-vis v-text" aria-hidden="true"><span></span><span></span><span></span><span class="short"></span></div><h3>{{ __('Text') }}</h3><p>{{ __('A heading, a paragraph, a quote. Say a little more than a link can.') }}</p></article>
            <article class="bx-cell"><div class="bx-vis v-image" aria-hidden="true"><span class="sun"></span><span class="hill"></span></div><h3>{{ __('Image') }}</h3><p>{{ __('Photos and artwork, cropped to fit and loaded fast.') }}</p></article>
            <article class="bx-cell"><div class="bx-vis v-video" aria-hidden="true"><span class="play"></span><span class="bar"><i></i></span></div><h3>{{ __('Video') }}</h3><p>{{ __('YouTube and Vimeo play right on your page.') }}</p></article>
            <article class="bx-cell"><div class="bx-vis v-music" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div><h3>{{ __('Music') }}</h3><p>{{ __('Spotify, SoundCloud and Apple Music players.') }}</p></article>
            <article class="bx-cell"><div class="bx-vis v-map" aria-hidden="true"><span class="pin"></span><span class="ring"></span></div><h3>{{ __('Map') }}</h3><p>{{ __('Show where you are, with a pin people can tap.') }}</p></article>
            <article class="bx-cell"><div class="bx-vis v-card" aria-hidden="true"><span class="av"></span><span class="row"></span><span class="row"></span><span class="btn">{{ __('Save contact') }}</span></div><h3>{{ __('Contact card') }}</h3><p>{{ __('A vCard visitors save to their phone in one tap.') }}</p></article>
            <article class="bx-cell"><div class="bx-vis v-qr" aria-hidden="true">@for ($q = 0; $q < 49; $q++)<i></i>@endfor</div><h3>{{ __('QR code') }}</h3><p>{{ __('Print your page, with your colours and your logo.') }}</p></article>
        </div>
            <div class="bx-blocks-stage" aria-hidden="true">
                <x-bx-phone id="blocks-phone" :links="false">
                    <span class="bx-fly f-link"><span class="bx-pill">{{ __('Portfolio') }} <i>↗</i></span></span>
                    <span class="bx-fly f-text"><i></i><i></i><i class="short"></i></span>
                    <span class="bx-fly f-image"><i class="sun"></i><i class="hill"></i></span>
                    <span class="bx-fly f-video"><i class="play"></i></span>
                    <span class="bx-fly f-music"><b><i></i><i></i><i></i><i></i><i></i></b></span>
                    <span class="bx-fly f-map"><i class="pin"></i></span>
                    <span class="bx-fly f-card"><i class="av"></i><b>{{ __('Save contact') }}</b></span>
                    <span class="bx-fly f-qr">@for ($q = 0; $q < 25; $q++)<i></i>@endfor</span>
                </x-bx-phone>
            </div>
        </div>
    </div>
</section>

{{-- 02 from idea to live: pinned, the scroll plays the story --}}
<section id="tour" class="bx bx-story" aria-labelledby="tour-title" data-bx-story>
    <div class="bx-wrap bx-story-grid">
        <div class="bx-story-copy">
            <div class="bx-head"><p class="h-label"><span class="n">02</span> · <span>{{ __('From idea to live') }}</span></p><h2 id="tour-title"><span>{{ __('From a name') }} <em>{{ __('to your page.') }}</em></span></h2></div>
            <ol class="bx-steps" data-bx-steps>
                <li><b>{{ __('Claim your name') }}</b><span>{{ __('Type it. If it is free, it is yours with a HOP ID.') }}</span></li>
                <li><b>{{ __('Add your blocks') }}</b><span>{{ __('Links, videos, music, a map. Drag to reorder.') }}</span></li>
                <li><b>{{ __('Make it yours') }}</b><span>{{ __('Pick a theme, then change colours and fonts.') }}</span></li>
                <li><b>{{ __('Share it') }}</b><span>{{ __('One link, one QR code, every profile.') }}</span></li>
                <li><b>{{ __('See what works') }}</b><span>{{ __('Views and clicks per block, counted without tracking people.') }}</span></li>
            </ol>
        </div>
        <div class="bx-story-vis" aria-hidden="true">
            <div class="bx-addr"><span class="bx-dot"></span><span class="bx-addr-text">hel.ink/b/<b data-story-type="helena">helena</b></span><em class="bx-ok" data-story-ok>{{ __('Available') }} ✓</em></div>
            <div class="bx-story-body">
                <x-bx-phone id="story-phone"></x-bx-phone>
                <div class="bx-qr" data-story-qr>@for ($q = 0; $q < 49; $q++)<i></i>@endfor<small>hel.ink/b/helena</small></div>
                <span class="bx-chip c1">Instagram</span><span class="bx-chip c2">TikTok</span><span class="bx-chip c3">{{ __('Email signature') }}</span><span class="bx-chip c4">YouTube</span>
                <div class="bx-stats" data-story-stats><small>{{ __('Views') }}</small><b data-story-views>1,284</b><div class="bx-bars"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div></div>
            </div>
        </div>
    </div>
</section>

{{-- 03 themes --}}
<section id="themes" class="bx" aria-labelledby="themes-title" data-bx-themes>
    <script type="application/json" id="bx-themes-data">@json($themes)</script>
    <div class="bx-wrap bx-two">
        <div class="bx-head"><p class="h-label"><span class="n">03</span> · <span>{{ __('Themes') }}</span></p><h2 id="themes-title"><span>{{ __('Pick a mood.') }} <em>{{ __('Make it yours.') }}</em></span></h2><p class="h-sub"><span>{{ __('Gradients, animations and button effects. Scroll to see them change, or tap one.') }}</span></p>
        </div>
        <div class="bx-themes-vis">
            <div class="bx-ring-box"><x-bx-phone id="theme-phone"></x-bx-phone>
                <div class="bx-swatches bx-ring" role="group" aria-label="{{ __('Themes') }}" style="--n:{{ count($themes) }}">@foreach ($themes as $key => $theme)<button type="button" data-theme-key="{{ $key }}" style="--i:{{ $loop->index }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"><i style="background:{{ $theme['background'] }}"></i>{{ $theme['name'] ?? $key }}</button>@endforeach</div>
            </div>
            <p class="bx-note" data-theme-name>{{ collect($themes)->first()['name'] ?? '' }}</p>
        </div>
    </div>
</section>

{{-- 04 analytics --}}
<section id="analytics" class="bx" aria-labelledby="analytics-title" data-bx-analytics>
    <div class="bx-wrap bx-two rev">
        <div class="bx-head"><p class="h-label"><span class="n">04</span> · <span>{{ __('Analytics') }}</span></p><h2 id="analytics-title"><span>{{ __('See what') }} <em>{{ __('connects.') }}</em></span></h2><p class="h-sub"><span>{{ __('Views and clicks per block, countries, devices and where people came from. Counted with a keyed hash, never the visitor itself.') }}</span></p>
            <ul class="bx-facts"><li><b>{{ __('Per block.') }}</b> <span>{{ __('Find the link people actually tap.') }}</span></li><li><b>{{ __('No cookies.') }}</b> <span>{{ __('Nothing to accept, nothing to follow.') }}</span></li><li><b>{{ __('Yours to export.') }}</b> <span>{{ __('Download everything from the settings.') }}</span></li></ul></div>
        <div class="bx-analytics-vis">
        <div class="bx-iso" data-bx-iso aria-label="{{ __('Clicks per day, last four weeks') }}"><div class="bx-iso-grid">@foreach ([3,5,4,7,6,9,8, 4,6,5,8,7,10,9, 5,4,7,9,8,12,10, 6,8,7,11,9,14,12] as $n)<i style="--h:{{ round($n / 14, 2) }}" data-n="{{ $n }}"><b class="top"></b><b class="front"></b><b class="side"></b></i>@endforeach</div><p class="bx-iso-label">{{ __('Clicks per day, last four weeks') }}</p></div>
        <article class="bx-card" aria-label="{{ __('Sample analytics') }}">
            <div class="bx-card-bar"><span>● ● ●</span> BioHop / {{ __('Analytics') }} <span>{{ __('Sample data') }}</span></div>
            <div class="bx-metrics"><div><small>{{ __('Views') }}</small><b data-metric="12840">12,840</b></div><div><small>{{ __('Clicks') }}</small><b data-metric="3912">3,912</b></div><div><small>{{ __('Click rate') }}</small><b data-metric="30" data-suffix="%">30%</b></div></div>
            <svg class="bx-line" viewBox="0 0 400 120" preserveAspectRatio="none" aria-hidden="true"><defs><linearGradient id="bxg" x1="0" x2="1"><stop offset="0" stop-color="#22d3ee"/><stop offset=".5" stop-color="#a78bfa"/><stop offset="1" stop-color="#f472b6"/></linearGradient></defs><path class="area" d="M0 96 C40 90 60 70 100 74 S160 40 200 52 S270 20 310 30 S370 10 400 8 L400 120 L0 120Z"/><path class="draw" d="M0 96 C40 90 60 70 100 74 S160 40 200 52 S270 20 310 30 S370 10 400 8" stroke="url(#bxg)"/></svg>
            <ul class="bx-top">@foreach ([['Portfolio', 82], ['New short film', 61], ['Book a call', 44], ['Studio playlist', 27]] as [$label, $pct])<li><span>{{ __($label) }}</span><i style="--w:{{ $pct }}%"></i><em>{{ $pct }}%</em></li>@endforeach</ul>
        </article>
        </div>
    </div>
</section>

{{-- 05 share everywhere --}}
<section id="share" class="bx" aria-labelledby="share-title" data-bx-share>
    <div class="bx-wrap bx-two">
        <div class="bx-head"><p class="h-label"><span class="n">05</span> · <span>{{ __('Share') }}</span></p><h2 id="share-title"><span>{{ __('One link.') }} <em>{{ __('A thousand places.') }}</em></span></h2><p class="h-sub"><span>{{ __('Put it in your profiles, your email signature or on a poster. The QR code carries your colours.') }}</span></p></div>
        <div class="bx-net" aria-hidden="true">
            <svg viewBox="0 0 400 320" preserveAspectRatio="xMidYMid meet"><g class="links"><path d="M200 160 L60 50"/><path d="M200 160 L340 50"/><path d="M200 160 L40 160"/><path d="M200 160 L360 160"/><path d="M200 160 L60 270"/><path d="M200 160 L340 270"/></g><circle class="pkt" r="4"/><circle class="pkt" r="4"/><circle class="pkt" r="4"/></svg>
            <div class="bx-core"><img src="{{ asset('images/brand/rabbit-mark.png') }}" alt="" width="44" height="44" loading="lazy"><b>hel.ink/b/helena</b></div>
            <span class="bx-dest d1" style="left:15%;top:15%">Instagram</span><span class="bx-dest d2" style="left:85%;top:15%">TikTok</span><span class="bx-dest d3" style="left:10%;top:50%">YouTube</span><span class="bx-dest d4" style="left:90%;top:50%">X</span><span class="bx-dest d5" style="left:15%;top:84%">{{ __('Email signature') }}</span><span class="bx-dest d6" style="left:85%;top:84%">{{ __('Printed QR') }}</span>
        </div>
    </div>
</section>

{{-- 06 boundaries --}}
<section id="boundaries" class="bx" aria-labelledby="boundaries-title" data-bx-gate>
    <div class="bx-wrap">
        <div class="bx-head"><p class="h-label"><span class="n">06</span> · <span>{{ __('Your boundaries') }}</span></p><h2 id="boundaries-title"><span>{{ __('Choose who') }} <em>{{ __('gets in.') }}</em></span></h2><p class="h-sub"><span>{{ __('A password or an 18+ check. Visitors see a gate before the page opens, and nothing behind it is sent until they pass.') }}</span></p></div>
        <div class="bx-gates">
            <article class="bx-gate" data-gate="pw"><div class="bx-gate-page" aria-hidden="true"><span></span><span></span><span class="s"></span></div><div class="bx-gate-box" aria-hidden="true"><svg class="lock" viewBox="0 0 48 56"><path class="shackle" d="M14 24V16a10 10 0 0 1 20 0v8" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"/><rect x="6" y="24" width="36" height="28" rx="8" fill="currentColor"/></svg><div class="bx-pw"><small>{{ __('Password') }}</small><span class="dots" data-gate-dots>••••••••</span></div><span class="bx-go">{{ __('Enter') }}</span></div><h3>{{ __('Password gate') }}</h3><p>{{ __('Share the password only with the people you choose.') }}</p></article>
            <article class="bx-gate" data-gate="age"><div class="bx-gate-page" aria-hidden="true"><span></span><span></span><span class="s"></span></div><div class="bx-gate-box" aria-hidden="true"><b class="age">18+</b><div class="bx-pw"><small>{{ __('Content warning') }}</small><span>{{ __('Are you 18 or older?') }}</span></div><span class="bx-go">{{ __('Yes, continue') }}</span></div><h3>{{ __('18+ check') }}</h3><p>{{ __('For pages with adult or sensitive content.') }}</p></article>
        </div>
    </div>
</section>

{{-- 07 your own style --}}
<section id="style" class="bx" aria-labelledby="style-title" data-bx-style>
    <div class="bx-wrap bx-two rev">
        <div class="bx-head"><p class="h-label"><span class="n">07</span> · <span>{{ __('Your style') }}</span></p><h2 id="style-title"><span>{{ __('Your fonts.') }} <em>{{ __('Your colours. Your CSS.') }}</em></span></h2><p class="h-sub"><span>{{ __('Start from a theme, then change the font, the colours or the whole stylesheet. Nothing is locked.') }}</span></p></div>
        <div class="bx-style-vis">
            <article class="bx-card bx-code" aria-hidden="true"><div class="bx-card-bar"><span>● ● ●</span> {{ __('Custom CSS') }} <span>{{ __('Demo') }}</span></div><pre><code data-style-code>.page { font-family: "Instrument Serif"; }
.page { background: #0f172a; }
.link { border-radius: 999px; }
.link { background: #a78bfa; color: #0f172a; }</code></pre></article>
            <x-bx-phone id="style-phone"></x-bx-phone>
        </div>
    </div>
</section>

{{-- 08 self-host --}}
<section id="open" class="bx" aria-labelledby="open-title" data-bx-install>
    <div class="bx-wrap bx-two">
        <div class="bx-head"><p class="h-label"><span class="n">08</span> · <span>MIT</span></p><h2 id="open-title"><span>{{ __('Open source') }} <em>{{ __('and yours to host.') }}</em></span></h2><p class="h-sub"><span>{{ __('Yes. No paid plan and no locked feature. The code is MIT licensed.') }}</span></p><div class="h-badges"><a class="h-badge" href="https://github.com/navi-crwn/BioHop/blob/main/LICENSE"><span>{{ __('License') }}</span><b>MIT</b></a><span class="h-badge"><span>PHP</span><b>8.3+</b></span><span class="h-badge"><span>Laravel</span><b>12</b></span></div><div class="h-cta"><a class="h-pill dark" href="https://github.com/navi-crwn/BioHop">GitHub ↗</a></div></div>
        <div class="h-shell bx-term"><div class="h-shell-bar"><i></i><i></i><i></i><span>bash</span></div><div class="h-term"><code id="install-sh" data-type>git clone https://github.com/navi-crwn/BioHop.git && cd BioHop && composer install && npm install && npm run build</code><button type="button" data-copy-text data-copy-from="#install-sh">{{ __('Copy') }}</button></div><pre data-out><span>cp .env.example .env && php artisan key:generate</span>
<span>php artisan migrate</span>
<span class="ok">✓ php artisan serve</span></pre></div>
    </div>
</section>

{{-- 09 questions --}}
<section id="faq" class="bx" aria-labelledby="faq-title">
    <div class="bx-wrap">
        <div class="bx-head"><p class="h-label"><span class="n">09</span> · <span>{{ __('Good questions') }}</span></p><h2 id="faq-title"><span>{{ __('A few things') }} <em>{{ __('to know.') }}</em></span></h2><p class="h-sub"><span>{{ __('No premium tier and no locked features.') }}</span></p></div>
        <div class="bx-faq" data-bx-faq>@foreach (['Is BioHop really free?' => 'Yes. No paid plan and no locked feature. The code is MIT licensed.', 'Do I need an account?' => 'Yes, a free HOP ID. The same account works in LinkHop, PixelHop and NoteHop.', 'Do you track my visitors?' => 'Views and clicks are counted with a keyed hash of the IP address, never the address itself. Pixels you add load only after the visitor agrees.', 'Can I move my page later?' => 'Yes. You can rename it, and export everything from the settings.', 'Can I have more than one page?' => 'Yes. One HOP ID can hold several pages, each with its own name, theme and blocks.', 'Can I put a password on my page?' => 'Yes. Add a password or an 18+ check, and visitors see a gate before the page opens.'] as $question => $answer)<article class="bx-cell bx-q"><h3>{{ __($question) }}</h3><p>{{ __($answer) }}</p></article>@endforeach</div>
    </div>
</section>

{{-- 10 family --}}
<section id="family" class="bx" aria-labelledby="family-title">
    <div class="bx-wrap">
        <div class="bx-head"><p class="h-label"><span class="n">10</span> · <span>HOP</span></p><h2 id="family-title"><span><em>{{ __('Part of the HOP family') }}</em></span></h2><p class="h-sub"><span>{{ __('One free HOP ID signs you in to every app.') }}</span></p></div>
        {{-- every app as its own 3D mark: drag to turn it; the flat mark stays without WebGL --}}
        <div class="bx-family" data-bx-family>@foreach (collect(\Hop\Ui\Assets::family('biohop'))->reject(fn ($app) => $app['current']) as $app)<article class="bx-fam"><figure class="h-art"><img src="{{ $app['icons']['mark']['url'] }}" alt="" width="120" height="120" loading="lazy"><canvas data-hop-hero="{{ $app['slug'] }}" tabindex="0" aria-label="{{ __('Drag or use arrow keys to rotate') }}"></canvas></figure><b>{{ $app['name'] }}</b><a href="{{ $app['url'] }}">{{ parse_url($app['url'], PHP_URL_HOST) }} ↗</a></article>@endforeach</div>
    </div>
</section>

{{-- 11 the last word --}}
<section class="bx bx-final" aria-labelledby="final-title">
    <div class="bx-wrap"><p class="h-label"><span class="n">11</span> · <span>{{ __('Your next chapter') }}</span></p><h2 id="final-title"><span>{{ __('The internet is big.') }} <em>{{ __('Make a little space.') }}</em></span></h2><p class="h-sub"><span>{{ __('Your work, your links, your next big thing. Bring them together on a page that feels like you.') }}</span></p><div class="h-cta"><a class="h-pill dark" href="{{ route('register') }}">{{ __('Create your bio') }} ↗</a><a class="h-pill" href="#preview">{{ __('Claim my page') }} ↑</a></div></div>
</section>
