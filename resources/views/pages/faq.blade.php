<x-info-page title="FAQ" lead="Common questions. How-to steps are in Help.">
    <h2>{{ __('Is it really free?') }}</h2>
    <p>{{ __('Yes. There is no paid plan and no locked feature, and the code is MIT licensed.') }}</p>
    <h2>{{ __('Do I need an account?') }}</h2>
    <p>{{ __('Yes, a free HOP ID. It also signs you in to LinkHop, PixelHop and NoteHop.') }}</p>
    <h2>{{ __('Where does my page live?') }}</h2>
    <p>{!! __('At <code>hel.ink/b/</code> followed by the name you pick, for example <a href=":url">hel.ink/b/hel</a>.', ['url' => 'https://hel.ink/b/hel']) !!}</p>
    <h2>{{ __('Do you track my visitors?') }}</h2>
    <p>{{ __('BioHop counts views and clicks with a keyed hash of the IP address, never the address itself. Analytics pixels you add yourself load only after the visitor agrees.') }}</p>
    <h2>{{ __('Can I use my own domain?') }}</h2>
    <p>{!! __('By running your own copy. See the <a href=":url" rel="noopener">README</a>.', ['url' => 'https://github.com/navi-crwn/BioHop']) !!}</p>
    <h2>{{ __('How do I report a page?') }}</h2>
    <p>{!! __('Use the <a href=":url">report form</a>.', ['url' => route('report.create')]) !!}</p>
    <h2>{{ __('Is BioHop working right now?') }}</h2>
    <p>{!! __('See the <a href=":url">status page</a>.', ['url' => route('pages.status')]) !!}</p>
</x-info-page>
