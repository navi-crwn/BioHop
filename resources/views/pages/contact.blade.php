<x-info-page title="Contact" lead="BioHop is open source, so most conversations happen on GitHub.">
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div class="rounded-3xl border border-slate-200/80 bg-white/70 p-6 dark:border-white/10 dark:bg-white/[0.03]">
            <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Issues & Ideas') }}</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{!! __('<strong>Found a bug or have an idea?</strong> <a href=":url" rel="noopener">Open an issue</a>.', ['url' => 'https://github.com/navi-crwn/BioHop/issues/new/choose']) !!}</p>
        </div>

        <div class="rounded-3xl border border-slate-200/80 bg-white/70 p-6 dark:border-white/10 dark:bg-white/[0.03]">
            <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Contributions') }}</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{!! __('<strong>Want to fix it yourself?</strong> Pull requests are welcome: <a href=":url" rel="noopener">see open PRs</a>.', ['url' => 'https://github.com/navi-crwn/BioHop/pulls']) !!}</p>
        </div>

        <div class="rounded-3xl border border-slate-200/80 bg-white/70 p-6 dark:border-white/10 dark:bg-white/[0.03]">
            <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Security') }}</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{!! __('<strong>Security problem?</strong> Please report it privately with a <a href=":advisory" rel="noopener">security advisory</a> (see <a href=":security" rel="noopener">SECURITY.md</a>).', ['advisory' => 'https://github.com/navi-crwn/BioHop/security/advisories/new', 'security' => 'https://github.com/navi-crwn/BioHop/blob/main/SECURITY.md']) !!}</p>
        </div>

        <div class="rounded-3xl border border-slate-200/80 bg-white/70 p-6 dark:border-white/10 dark:bg-white/[0.03]">
            <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Abuse & Moderation') }}</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{!! __('<strong>A page breaks the rules?</strong> Use the <a href=":url">report form</a>.', ['url' => route('report.create')]) !!}</p>
        </div>

        <div class="rounded-3xl border border-slate-200/80 bg-white/70 p-6 dark:border-white/10 dark:bg-white/[0.03]">
            <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ __('HOP ID Account') }}</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{!! __('<strong>Your account</strong> (sign-in, profile, deleting it) is managed at <a href=":url">HOP ID</a>.', ['url' => \App\Models\User::hubUrl('settings')]) !!}</p>
        </div>

        <div class="rounded-3xl border border-slate-200/80 bg-white/70 p-6 dark:border-white/10 dark:bg-white/[0.03]">
            <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Email Inquiries') }}</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('For inquiries where GitHub is unsuitable, email us at:') }} <!--email_off--><a href="mailto:support@hel.ink" class="font-mono text-violet-600 underline dark:text-violet-400">support@hel.ink</a><!--/email_off-->.</p>
        </div>
    </div>
</x-info-page>
