<x-info-page title="Contact" lead="BioHop is open source, so most conversations happen on GitHub.">
    <ul>
        <li>{!! __('<strong>Found a bug or have an idea?</strong> <a href=":url" rel="noopener">Open an issue</a>.', ['url' => 'https://github.com/navi-crwn/BioHop/issues/new/choose']) !!}</li>
        <li>{!! __('<strong>Want to fix it yourself?</strong> Pull requests are welcome: <a href=":url" rel="noopener">see open PRs</a>.', ['url' => 'https://github.com/navi-crwn/BioHop/pulls']) !!}</li>
        <li>{!! __('<strong>Security problem?</strong> Please report it privately with a <a href=":advisory" rel="noopener">security advisory</a> (see <a href=":security" rel="noopener">SECURITY.md</a>).', ['advisory' => 'https://github.com/navi-crwn/BioHop/security/advisories/new', 'security' => 'https://github.com/navi-crwn/BioHop/blob/main/SECURITY.md']) !!}</li>
        <li>{!! __('<strong>A page breaks the rules?</strong> Use the <a href=":url">report form</a>.', ['url' => route('report.create')]) !!}</li>
        <li>{!! __('<strong>Your account</strong> (sign-in, profile, deleting it) is managed at <a href=":url">HOP ID</a>.', ['url' => \App\Models\User::hubUrl('settings')]) !!}</li>
    </ul>
</x-info-page>
