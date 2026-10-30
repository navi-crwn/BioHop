<x-info-page title="Privacy" lead="What BioHop keeps, and what it does not. The privacy policy for every HOP app is at hel.ink/privacy.">
    <h2>When someone visits a bio page</h2>
    <p>We count the view and the clicks on each block, with the country, device and browser type and the referring site. For counting unique visitors we keep a keyed hash of the IP address, which cannot be turned back into the address.</p>
    <h2>Pixels you add</h2>
    <p>If you add Google Analytics, Facebook Pixel or TikTok Pixel to your page, they load only after the visitor agrees. That data goes to those services under their policies.</p>
    <h2>Your page</h2>
    <p>The blocks, images and settings you add, tied to your HOP ID. The account itself (email, password, two-factor) is kept by the hub.</p>
    <h2>What we never do</h2>
    <ul>
        <li>No ads and no trackers of our own.</li>
        <li>No selling or sharing of data.</li>
    </ul>
    <h2>Your data</h2>
    <p>Export your page from BioHop's settings, and download or delete your account at <a href="{{ \App\Models\User::hubUrl('settings') }}">HOP ID</a>. Deleting the account deletes your pages too.</p>
    <p>The full policy: <a href="https://hel.ink/privacy">hel.ink/privacy</a>.</p>
</x-info-page>
