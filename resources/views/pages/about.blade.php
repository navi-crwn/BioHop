<x-info-page title="About BioHop" lead="BioHop is the free, open-source link-in-bio app of the HOP family.">
    <h2>{{ __('Why') }}</h2>
    <p>{{ __('One page for everything you share: your links, profiles, music, videos and contact card, at an address that is easy to remember. No ads and no trackers on your page unless you add your own, and only after your visitors agree.') }}</p>
    <h2>{{ __('Who runs it') }}</h2>
    <p>{!! __('BioHop is run by one person on one server. The code is MIT licensed and public on <a href=":url" rel="noopener">GitHub</a>, so you can run your own copy.', ['url' => 'https://github.com/navi-crwn/BioHop']) !!}</p>
    <h2>{{ __('The HOP family') }}</h2>
    <p>{!! __('One free <a href=":url">HOP ID</a> signs you in to every app:', ['url' => 'https://hel.ink']) !!}</p>
    <ul class="info-family">
        @foreach (\Hop\Ui\Assets::family('biohop') as $app)
            @continue($app['current'])
            <li><a href="{{ $app['url'] }}"><img src="{{ $app['icons']['mark']['url'] }}" alt="" width="40" height="40" loading="lazy"><span><strong>{{ $app['name'] }}</strong><small>{{ $app['tagline'] }}</small></span></a></li>
        @endforeach
    </ul>
    <p>{!! __('Questions? See the <a href=":faq">FAQ</a> or <a href=":contact">get in touch</a>.', ['faq' => route('pages.faq'), 'contact' => route('pages.contact')]) !!}</p>
</x-info-page>
