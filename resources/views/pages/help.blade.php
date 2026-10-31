<x-info-page title="Help" lead="How to do the common things. General questions are in the FAQ.">
    <h2 id="start">Make your page</h2>
    <ol>
        <li>Claim your name on the <a href="{{ route('home') }}">home page</a>, or <a href="{{ route('register') }}">create a free HOP ID</a>.</li>
        <li>Open the editor from your HOP ID dashboard.</li>
        <li>Add blocks: links, text, images, videos, music, a contact card.</li>
        <li>Pick a theme and your colors; the preview updates as you go.</li>
        <li>Share <code>hel.ink/b/yourname</code> or its QR code.</li>
    </ol>
    <h2 id="private">Lock a page</h2>
    <p>In the editor's settings, add a password or an 18+ age check. Visitors see a short gate before the page.</p>
    <h2 id="stats">See views and clicks</h2>
    <p>The editor's stats tab shows page views and clicks per block.</p>
    <h2 id="name">Change your address</h2>
    <p>You can rename the page in its settings. Some names are reserved for the HOP family and for safety.</p>
    <h2 id="report">Report a page</h2>
    <p>Use the <a href="{{ route('report.create') }}">report form</a> with the page's address.</p>
</x-info-page>
