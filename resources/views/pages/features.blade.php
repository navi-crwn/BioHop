<x-info-page title="Features" lead="Everything is included for every account. No premium tier, no locked features." :wide="true">
    {{-- Bento grid for features --}}
    <div class="not-prose grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 [&>div]:min-w-0 [&>div:first-child]:sm:col-span-2 [&>div:last-child]:lg:col-span-2">
        {{-- Card 1: Your Address --}}
        <div class="rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-white/[0.03]">
            <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-2xl bg-violet-500/10 text-violet-600 dark:text-violet-400">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            </div>
            <h2 id="page" class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Your page') }}</h2>
            <x-feature-demo name="page" label="Your page" />
            <ul class="mt-3 space-y-2 text-sm text-slate-600 dark:text-slate-400">
                <li>{!! __('<strong>Your own address</strong> at <code>hel.ink/b/yourname</code>.') !!}</li>
                <li>{!! __('<strong>Blocks</strong>: links, text, images, YouTube, Spotify, maps, countdowns, FAQ, vCard and code snippets.') !!}</li>
                <li>{!! __('<strong>Brand icons</strong> for your social profiles, placed where you want them.') !!}</li>
                <li>{!! __('<strong>Styled QR codes</strong> that open your page.') !!}</li>
            </ul>
        </div>

        {{-- Card 2: Themes --}}
        <div class="rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-white/[0.03]">
            <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-2xl bg-fuchsia-500/10 text-fuchsia-600 dark:text-fuchsia-400">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22a10 10 0 1 1 10-10c0 2.8-2.2 4-4 4h-1.5a1.5 1.5 0 0 0-1 2.6 1.5 1.5 0 0 1-1 2.6Z"/><circle cx="7.5" cy="11.5" r="1"/><circle cx="10.5" cy="7.5" r="1"/><circle cx="15.5" cy="7.5" r="1"/></svg>
            </div>
            <h2 id="themes" class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Themes') }}</h2>
            <x-feature-demo name="themes" label="Themes" />
            <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ __('20+ themes such as Galaxy, Neon, Aurora and Midnight, or your own colors, fonts and custom CSS, with background animations and button effects. The editor shows a live preview.') }}</p>
        </div>

        {{-- Card 3: Analytics --}}
        <div class="rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-white/[0.03]">
            <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/></svg>
            </div>
            <h2 id="analytics" class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Analytics') }}</h2>
            <x-feature-demo name="analytics" label="Analytics" />
            <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ __('Page views and clicks per block. Visitors are counted with a keyed hash of their IP address, never the address itself.') }}</p>
        </div>

        {{-- Card 4: Private pages --}}
        <div class="rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-white/[0.03]">
            <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
            </div>
            <h2 id="private" class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Private pages') }}</h2>
            <x-feature-demo name="private" label="Private pages" />
            <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ __('Lock a page with a password or an 18+ age check. Google Analytics, Facebook Pixel and TikTok Pixel load only after the visitor agrees.') }}</p>
        </div>

        {{-- Card 5: Short links --}}
        <div class="rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-white/[0.03]">
            <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            </div>
            <h2 id="short" class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Short links') }}</h2>
            <x-feature-demo name="short" label="Short links" />
            <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{!! __('Turn any block link into a <a href=":url">LinkHop</a> short link, with the same HOP ID.', ['url' => 'https://link.hel.ink']) !!}</p>
        </div>

        {{-- Card 6: HOP ID Integration --}}
        <div class="rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm transition hover:shadow-md dark:border-white/10 dark:bg-white/[0.03]">
            <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-500/10 text-sky-600 dark:text-sky-400">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <h2 id="hopid" class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Part of the HOP family') }}</h2>
            <x-feature-demo name="hopid" label="Part of the HOP family" />
            <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ __('One free HOP ID signs you in to every app.') }}</p>
        </div>
    </div>

    <div class="not-prose mt-12 text-center">
        <a class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-violet-600 to-fuchsia-600 px-8 py-4 font-semibold text-white shadow-lg shadow-violet-600/20 transition hover:from-violet-500 hover:to-fuchsia-500" href="{{ route('register') }}">
            {{ __('Create your bio') }} &rarr;
        </a>
    </div>
</x-info-page>
