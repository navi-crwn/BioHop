<x-info-page title="FAQ" lead="Common questions. How-to steps are in Help.">
    <h2>Is it really free?</h2>
    <p>Yes. There is no paid plan and no locked feature, and the code is MIT licensed.</p>
    <h2>Do I need an account?</h2>
    <p>Yes, a free HOP ID. It also signs you in to LinkHop, PixelHop and NoteHop.</p>
    <h2>Where does my page live?</h2>
    <p>At <code>hel.ink/b/</code> followed by the name you pick, for example <a href="https://hel.ink/b/hel">hel.ink/b/hel</a>.</p>
    <h2>Do you track my visitors?</h2>
    <p>BioHop counts views and clicks with a keyed hash of the IP address, never the address itself. Analytics pixels you add yourself load only after the visitor agrees.</p>
    <h2>Can I use my own domain?</h2>
    <p>By running your own copy. See the <a href="https://github.com/navi-crwn/BioHop" rel="noopener">README</a>.</p>
    <h2>How do I report a page?</h2>
    <p>Use the <a href="{{ route('report.create') }}">report form</a>.</p>
    <h2>Is BioHop working right now?</h2>
    <p>See the <a href="{{ route('pages.status') }}">status page</a>.</p>
</x-info-page>
