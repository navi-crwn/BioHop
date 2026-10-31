<x-info-page title="Help" lead="How to do the common things. General questions are in the FAQ.">
    <h2 id="start">{{ __('Make your page') }}</h2>
    <ol>
        <li>{!! __('Claim your name on the <a href=":url">home page</a>, or <a href=":register">create a free HOP ID</a>.', ['url' => route('home'), 'register' => route('register')]) !!}</li>
        <li>{{ __('Open the editor from your HOP ID dashboard.') }}</li>
        <li>{{ __('Add blocks: links, text, images, videos, music, a contact card.') }}</li>
        <li>{{ __('Pick a theme and your colors; the preview updates as you go.') }}</li>
        <li>{!! __('Share <code>hel.ink/b/yourname</code> or its QR code.') !!}</li>
    </ol>
    <h2 id="private">{{ __('Lock a page') }}</h2>
    <p>{{ __('In the editor\'s settings, add a password or an 18+ age check. Visitors see a short gate before the page.') }}</p>
    <h2 id="stats">{{ __('See views and clicks') }}</h2>
    <p>{{ __('The editor\'s stats tab shows page views and clicks per block.') }}</p>
    <h2 id="name">{{ __('Change your address') }}</h2>
    <p>{{ __('You can rename the page in its settings. Some names are reserved for the HOP family and for safety.') }}</p>
    <h2 id="report">{{ __('Report a page') }}</h2>
    <p>{!! __('Use the <a href=":url">report form</a> with the page\'s address.', ['url' => route('report.create')]) !!}</p>
</x-info-page>
