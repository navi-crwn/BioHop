<x-info-page title="Features" lead="Everything is included for every account. No premium tier, no locked features.">
    <h2 id="page">{{ __('Your page') }}</h2>
    <ul>
        <li>{!! __('<strong>Your own address</strong> at <code>hel.ink/b/yourname</code>.') !!}</li>
        <li>{!! __('<strong>Blocks</strong>: links, text, images, YouTube, Spotify, maps, countdowns, FAQ, vCard and code snippets.') !!}</li>
        <li>{!! __('<strong>Brand icons</strong> for your social profiles, placed where you want them.') !!}</li>
        <li>{!! __('<strong>Styled QR codes</strong> that open your page.') !!}</li>
    </ul>
    <h2 id="themes">{{ __('Themes') }}</h2>
    <p>{{ __('20+ themes such as Galaxy, Neon, Aurora and Midnight, or your own colors, fonts and custom CSS, with background animations and button effects. The editor shows a live preview.') }}</p>
    <h2 id="analytics">{{ __('Analytics') }}</h2>
    <p>{{ __('Page views and clicks per block. Visitors are counted with a keyed hash of their IP address, never the address itself.') }}</p>
    <h2 id="private">{{ __('Private pages') }}</h2>
    <p>{{ __('Lock a page with a password or an 18+ age check. Google Analytics, Facebook Pixel and TikTok Pixel load only after the visitor agrees.') }}</p>
    <h2 id="short">{{ __('Short links') }}</h2>
    <p>{!! __('Turn any block link into a <a href=":url">LinkHop</a> short link, with the same HOP ID.', ['url' => 'https://link.hel.ink']) !!}</p>
    <p class="mt-8"><a class="info-cta" href="{{ route('register') }}">{{ __('Create your bio') }}</a></p>
</x-info-page>
