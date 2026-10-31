<x-info-page title="Privacy" lead="What BioHop keeps, and what it does not. The privacy policy for every HOP app is at hel.ink/privacy.">
    <div class="mb-6 rounded-2xl border border-violet-500/30 bg-violet-50/50 p-4 text-sm text-violet-900 dark:border-violet-500/20 dark:bg-violet-950/20 dark:text-violet-200">
        <p class="font-semibold">{{ __('Master HOP Privacy Policy') }}</p>
        <p class="mt-1">{!! __('This policy explains BioHop-specific data processing. It operates alongside the Master HOP Privacy Policy at <a href="https://hel.ink/privacy" class="underline font-medium">hel.ink/privacy</a>. Both documents govern your data protection rights across the HOP ecosystem.') !!}</p>
    </div>

    <h2>{{ __('When someone visits a bio page') }}</h2>
    <p>{{ __('We count the view and the clicks on each block, with the country, device and browser type and the referring site. For counting unique visitors we keep a keyed hash of the IP address, which cannot be turned back into the address.') }}</p>
    <h2>{{ __('Pixels you add') }}</h2>
    <p>{{ __('If you add Google Analytics, Facebook Pixel or TikTok Pixel to your page, they load only after the visitor agrees. That data goes to those services under their policies.') }}</p>
    <h2>{{ __('Your page') }}</h2>
    <p>{{ __('The blocks, images and settings you add, tied to your HOP ID. The account itself (email, password, two-factor) is kept by the hub.') }}</p>
    <h2>{{ __('What we never do') }}</h2>
    <ul>
        <li>{{ __('No ads and no trackers of our own.') }}</li>
        <li>{{ __('No selling or sharing of data.') }}</li>
    </ul>
    <h2>{{ __('Your data') }}</h2>
    <p>{!! __('Export your page from BioHop\'s settings, and download or delete your account at <a href=":url">HOP ID</a>. Deleting the account deletes your pages too.', ['url' => \App\Models\User::hubUrl('settings')]) !!}</p>
    <p>{!! __('The full policy: <a href="https://hel.ink/privacy">hel.ink/privacy</a>. Questions or deletion requests: <!--email_off--><a href="mailto:support@hel.ink">support@hel.ink</a><!--/email_off-->.') !!}</p>
</x-info-page>
