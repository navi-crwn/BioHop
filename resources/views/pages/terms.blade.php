<x-info-page title="Terms" lead="The rules for bio pages on BioHop. They add to the terms that cover every HOP app.">
    <h2>{{ __('Not allowed') }}</h2>
    <ul>
        <li>{{ __('Phishing, malware, scams, or anything illegal where you or your visitors are.') }}</li>
        <li>{{ __('Pretending to be another person, brand or official body.') }}</li>
        <li>{{ __('Adult content without the 18+ gate turned on.') }}</li>
        <li>{{ __('Spam, or using a page to hide where links really go.') }}</li>
    </ul>
    <h2>{{ __('What we do') }}</h2>
    <p>{!! __('Links on bio pages are checked for known harmful destinations. A page that breaks these rules is hidden, and an account that keeps breaking them is suspended. Report a page with the <a href=":url">report form</a>.', ['url' => route('report.create')]) !!}</p>
    <h2>{{ __('Names') }}</h2>
    <p>{{ __('Some page names are reserved for the HOP family, for safety and for well-known brands. We may take back a name used to mislead.') }}</p>
    <h2>{{ __('No warranty') }}</h2>
    <p>{{ __('BioHop is free and provided as is.') }}</p>
    <p>{!! __('The full terms for every HOP app are at <a href="https://hel.ink/terms">hel.ink/terms</a>.') !!}</p>
</x-info-page>
