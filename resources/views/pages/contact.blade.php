<x-info-page title="Contact" lead="BioHop is open source, so most conversations happen on GitHub.">
    <ul>
        <li><strong>Found a bug or have an idea?</strong> <a href="https://github.com/navi-crwn/BioHop/issues/new/choose" rel="noopener">Open an issue</a>.</li>
        <li><strong>Want to fix it yourself?</strong> Pull requests are welcome: <a href="https://github.com/navi-crwn/BioHop/pulls" rel="noopener">see open PRs</a>.</li>
        <li><strong>Security problem?</strong> Please report it privately with a <a href="https://github.com/navi-crwn/BioHop/security/advisories/new" rel="noopener">security advisory</a> (see <a href="https://github.com/navi-crwn/BioHop/blob/main/SECURITY.md" rel="noopener">SECURITY.md</a>).</li>
        <li><strong>A page breaks the rules?</strong> Use the <a href="{{ route('report.create') }}">report form</a>.</li>
        <li><strong>Your account</strong> (sign-in, profile, deleting it) is managed at <a href="{{ \App\Models\User::hubUrl('settings') }}">HOP ID</a>.</li>
    </ul>
</x-info-page>
