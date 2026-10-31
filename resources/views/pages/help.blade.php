<x-info-page title="Help" lead="How to do the common things. General questions are in the FAQ.">
    <div class="space-y-8">
        <section class="rounded-3xl border border-slate-200/80 bg-white/70 p-6 dark:border-white/10 dark:bg-white/[0.03]">
            <h2 id="start" class="text-xl font-bold">{{ __('Make your page') }}</h2>
            <ol class="mt-4 space-y-2.5">
                <li>{!! __('Claim your name on the <a href=":url">home page</a>, or <a href=":register">create a free HOP ID</a>.', ['url' => route('home'), 'register' => route('register')]) !!}</li>
                <li>{{ __('Open the editor from your HOP ID dashboard.') }}</li>
                <li>{{ __('Add blocks: links, text, images, videos, music, a contact card.') }}</li>
                <li>{{ __('Pick a theme and your colors; the preview updates as you go.') }}</li>
                <li>{!! __('Share <code>hel.ink/b/yourname</code> or its QR code.') !!}</li>
            </ol>
        </section>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <section class="rounded-3xl border border-slate-200/80 bg-white/70 p-6 dark:border-white/10 dark:bg-white/[0.03]">
                <h2 id="private" class="text-lg font-bold">{{ __('Lock a page') }}</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('In the editor\'s settings, add a password or an 18+ age check. Visitors see a short gate before the page.') }}</p>
            </section>

            <section class="rounded-3xl border border-slate-200/80 bg-white/70 p-6 dark:border-white/10 dark:bg-white/[0.03]">
                <h2 id="stats" class="text-lg font-bold">{{ __('See views and clicks') }}</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('The editor\'s stats tab shows page views and clicks per block.') }}</p>
            </section>

            <section class="rounded-3xl border border-slate-200/80 bg-white/70 p-6 dark:border-white/10 dark:bg-white/[0.03]">
                <h2 id="name" class="text-lg font-bold">{{ __('Change your address') }}</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ __('You can rename the page in its settings. Some names are reserved for the HOP family and for safety.') }}</p>
            </section>

            <section class="rounded-3xl border border-slate-200/80 bg-white/70 p-6 dark:border-white/10 dark:bg-white/[0.03]">
                <h2 id="report" class="text-lg font-bold">{{ __('Report a page') }}</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{!! __('Use the <a href=":url">report form</a> with the page\'s address.', ['url' => route('report.create')]) !!}</p>
            </section>
        </div>
    </div>
</x-info-page>
