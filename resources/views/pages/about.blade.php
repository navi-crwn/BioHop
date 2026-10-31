<x-info-page title="About BioHop" lead="BioHop is the free, open-source link-in-bio app of the HOP family.">
    <h2>Why</h2>
    <p>One page for everything you share: your links, profiles, music, videos and contact card, at an address that is easy to remember. No ads and no trackers on your page unless you add your own, and only after your visitors agree.</p>
    <h2>Who runs it</h2>
    <p>BioHop is run by one person on one server. The code is MIT licensed and public on <a href="https://github.com/navi-crwn/BioHop" rel="noopener">GitHub</a>, so you can run your own copy.</p>
    <h2>The HOP family</h2>
    <p>One free <a href="https://hel.ink">HOP ID</a> signs you in to every app:</p>
    <ul class="info-family">
        @foreach (\Hop\Ui\Assets::family('biohop') as $app)
            @continue($app['current'])
            <li><a href="{{ $app['url'] }}"><img src="{{ $app['icons']['mark']['url'] }}" alt="" width="40" height="40" loading="lazy"><span><strong>{{ $app['name'] }}</strong><small>{{ $app['tagline'] }}</small></span></a></li>
        @endforeach
    </ul>
    <p>Questions? See the <a href="{{ route('pages.faq') }}">FAQ</a> or <a href="{{ route('pages.contact') }}">get in touch</a>.</p>
</x-info-page>
