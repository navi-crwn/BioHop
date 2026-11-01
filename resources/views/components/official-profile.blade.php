@props(['interactive' => true])
<div class="official-profile" :style="`--profile-bg:${themes[theme].background};--profile-text:${themes[theme].text};--profile-muted:${themes[theme].bio};--profile-link:${themes[theme].link_bg};--profile-link-text:${themes[theme].link_text};--profile-border:${themes[theme].link_border || 'transparent'}`">
    <div class="official-profile-address"><span aria-hidden="true">◉</span> hel.ink/b/hel <span aria-hidden="true">↗</span></div>
    <div class="official-profile-scroll">
        <img class="official-profile-avatar" src="{{ asset('images/brand/rabbit-mark.png') }}" width="72" height="72" alt="HEL.ink jumping rabbit">
        <h2>HEL.ink</h2>
        <p class="official-profile-bio">{{ __('Short links, bio pages, images and private notes. One free HOP ID, open source, yours to host.') }}</p>
        <div class="official-profile-social" aria-label="{{ __('Social links') }}">
            <a href="https://github.com/navi-crwn/HEL.ink" target="_blank" rel="noopener">GitHub ↗</a>
            <a href="https://hel.ink" target="_blank" rel="noopener">{{ __('Website') }} ↗</a>
        </div>
        <p class="official-profile-label">{{ __('Hop in') }}</p>
        @foreach (\Hop\Ui\Assets::family() as $app)
            @if ($interactive)
                <button type="button" class="official-profile-link" @click="selected = @js($app['name'])" :class="selected === @js($app['name']) && 'selected'"><img src="{{ $app['icons']['mark']['url'] }}" alt="" width="20" height="20" loading="lazy">{{ $app['name'] }} <span aria-hidden="true">↗</span></button>
            @else
                <div class="official-profile-link"><img src="{{ $app['icons']['mark']['url'] }}" alt="" width="20" height="20" loading="lazy">{{ $app['name'] }} <span aria-hidden="true">↗</span></div>
            @endif
        @endforeach
        <p class="official-profile-label">{{ __('Start here') }}</p>
        @foreach (['Create your free HOP ID', 'Source code on GitHub', 'Status and uptime'] as $label)
            @if ($interactive)
                <button type="button" class="official-profile-link" @click="selected = @js(__($label))" :class="selected === @js(__($label)) && 'selected'">{{ __($label) }} <span aria-hidden="true">↗</span></button>
            @else
                <div class="official-profile-link">{{ __($label) }} <span aria-hidden="true">↗</span></div>
            @endif
        @endforeach
        <details class="official-profile-faq"><summary>{{ __('Is it free?') }}</summary><p>{{ __('Yes. No paid plan, no locked feature, no ads. The code is MIT licensed.') }}</p></details>
        <details class="official-profile-faq"><summary>{{ __('Do I need an account?') }}</summary><p>{{ __('Not to shorten a link. With an account you keep your links and get analytics, folders, the API and a bio page.') }}</p></details>
        <details class="official-profile-faq"><summary>{{ __('Can I host it myself?') }}</summary><p>{{ __('Yes. HEL.ink is open source under the MIT license; the self-host guide is on the docs site.') }}</p></details>
        <p class="official-profile-footer">{{ __('Made with BioHop') }} · HEL.ink</p>
    </div>
</div>
