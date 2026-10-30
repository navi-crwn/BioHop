@php($locale = app()->getLocale())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BioHop · {{ __('One link-in-bio page for everything you do.') }}</title>
    <meta name="description" content="{{ __('Share all your links, social profiles, embeds, vCard and QR code on one fast, customizable page at hel.ink/b/yourname. Free, open source, and powered by your HOP ID.') }}">
    <link rel="canonical" href="{{ url('/') }}">
    @foreach (\App\Http\Middleware\SetLocale::LOCALES as $code => $name)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ url('/') }}?lang={{ $code }}">
    @endforeach

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="BioHop · {{ __('One link-in-bio page for everything you do.') }}">
    <meta property="og:description" content="{{ __('Share all your links, social profiles, embeds, vCard and QR code on one fast, customizable page at hel.ink/b/yourname. Free, open source, and powered by your HOP ID.') }}">
    <meta property="og:image" content="{{ route('brand.logo', ['v' => 'b10b1000']) }}">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" type="image/svg+xml" href="{{ route('brand.favicon', ['v' => 'b10b1000']) }}">
    <x-hop::fonts />
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script nonce="{{ Vite::cspNonce() }}">
        (function () {
            try {
                var t = localStorage.getItem('helink-theme');
                if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
</head>
<body class="bg-slate-50 text-slate-900 antialiased dark:bg-[#0e1322] dark:text-slate-100 min-h-screen selection:bg-violet-500/20 selection:text-violet-500 relative overflow-x-hidden font-sans">

    {{-- Icon sprite: one set of line icons instead of emoji, so every system draws the same thing. --}}
    <svg xmlns="http://www.w3.org/2000/svg" class="hidden" aria-hidden="true">
        <symbol id="i-globe" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></symbol>
        <symbol id="i-image" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/></symbol>
        <symbol id="i-note" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/></symbol>
        <symbol id="i-card" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="8" cy="12" r="2"/><path d="M13 10h5M13 14h3"/></symbol>
        <symbol id="i-palette" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a10 10 0 1 1 10-10c0 2.8-2.2 4-4 4h-1.5a1.5 1.5 0 0 0-1 2.6 1.5 1.5 0 0 1-1 2.6Z"/><circle cx="7.5" cy="11.5" r="1"/><circle cx="10.5" cy="7.5" r="1"/><circle cx="15.5" cy="7.5" r="1"/></symbol>
        <symbol id="i-chart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m7 15 4-4 3 3 5-6"/></symbol>
        <symbol id="i-lock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></symbol>
        <symbol id="i-qr" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3M21 14v.01M14 21h7v-4"/></symbol>
        <symbol id="i-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></symbol>
        <symbol id="i-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></symbol>
    </svg>

    <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[42rem] h-[30rem] rounded-full bg-gradient-to-tr from-violet-400/20 via-fuchsia-400/20 to-indigo-400/20 blur-3xl dark:from-violet-600/10 dark:via-fuchsia-600/10 dark:to-indigo-600/10"></div>
        <div class="absolute top-[28rem] -left-20 w-[28rem] h-[28rem] rounded-full bg-gradient-to-br from-fuchsia-400/15 via-violet-400/15 to-transparent blur-3xl dark:from-fuchsia-600/10 dark:via-violet-600/10"></div>
    </div>

    @include('partials.site-header')

    <main x-data="{ theme: 'galaxy', themes: @js($themes) }">
        {{-- Hero --}}
        <section class="relative pt-12 pb-20 sm:pt-20 sm:pb-28">
            <x-hop::float mode="section" aria-hidden="true" class="max-lg:hidden" />
            <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-12 lg:gap-8">
                    <div class="min-w-0 space-y-8 lg:col-span-7">
                        <div class="space-y-4">
                            <span class="inline-flex items-center gap-2 rounded-full border border-violet-500/30 bg-violet-500/10 px-3.5 py-1 font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-violet-700 dark:text-violet-400">
                                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-violet-500"></span>
                                BioHop · by hel.ink
                            </span>
                            <h1 class="text-[clamp(1.75rem,7.5vw,3.75rem)] font-extrabold leading-[1.1] tracking-tight text-slate-900 dark:text-white" dir="ltr">
                                hel.ink/b/<span class="hop-serif bg-gradient-to-r from-violet-500 via-fuchsia-400 to-indigo-500 bg-clip-text text-transparent">{{ __('you') }}</span>
                            </h1>
                            <p class="text-2xl font-bold text-slate-800 sm:text-3xl dark:text-slate-200">{{ __('One link-in-bio page for everything you do.') }}</p>
                            <p class="max-w-2xl text-base leading-relaxed text-slate-600 sm:text-lg dark:text-slate-400">{{ __('Share all your links, social profiles, embeds, vCard and QR code on one fast, customizable page at hel.ink/b/yourname. Free, open source, and powered by your HOP ID.') }}</p>
                        </div>

                        <div x-data="{
                            slug: '', status: null, message: '', loading: false,
                            async check() {
                                const clean = this.slug.trim().toLowerCase();
                                if (clean.length < 3) { this.status = null; this.message = ''; return; }
                                this.loading = true;
                                try {
                                    const res = await fetch('/check-slug-public?slug=' + encodeURIComponent(clean));
                                    const data = await res.json();
                                    this.status = data.available ? 'available' : 'unavailable';
                                    this.message = data.message;
                                } catch (e) { this.status = null; this.message = ''; } finally { this.loading = false; }
                            }
                        }" class="space-y-3">
                            <form @submit.prevent="if (slug.trim().length >= 3) window.location.href = '{{ route('register') }}'" class="flex max-w-lg flex-col items-stretch gap-2.5 sm:flex-row">
                                <div class="relative flex flex-1 items-center rounded-2xl border border-slate-300/80 bg-white/90 shadow-sm focus-within:border-violet-500 focus-within:ring-2 focus-within:ring-violet-500/20 dark:border-white/15 dark:bg-white/5 dark:focus-within:border-violet-400" dir="ltr">
                                    <span class="select-none pl-4 pr-1 font-mono text-sm text-slate-400 dark:text-slate-500">hel.ink/b/</span>
                                    <input type="text" x-model="slug" @input.debounce.300ms="check()" placeholder="{{ __('yourname') }}" aria-label="{{ __('Your bio name') }}"
                                        class="w-full border-0 bg-transparent py-3 pr-4 font-mono text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 dark:text-white"
                                        required minlength="3" maxlength="30">
                                    <span x-show="loading" class="animate-spin pr-3 text-slate-400" aria-hidden="true">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke-width="4" stroke="currentColor" stroke-opacity="0.2"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    </span>
                                </div>
                                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-violet-600 to-fuchsia-600 px-6 py-3.5 text-sm font-semibold text-white shadow-md shadow-violet-600/20 transition-all hover:from-violet-500 hover:to-fuchsia-500 active:scale-95">
                                    <span>{{ __('Claim my page') }}</span>
                                    <svg class="h-4 w-4 rtl:-scale-x-100"><use href="#i-arrow"/></svg>
                                </button>
                            </form>
                            <div x-show="message" class="pl-1 text-xs font-medium" :class="status === 'available' ? 'text-violet-600 dark:text-violet-400' : 'text-rose-600 dark:text-rose-400'" x-text="message" aria-live="polite"></div>
                            <p class="flex items-center gap-1.5 pt-1 text-xs text-slate-500 dark:text-slate-400">
                                <svg class="h-4 w-4 flex-none text-violet-500"><use href="#i-check"/></svg>
                                {{ __('Free forever with your HOP ID · No ads, no trackers') }}
                            </p>
                        </div>
                    </div>

                    {{-- Live preview: the same themes as the editor --}}
                    <div id="preview" class="relative scroll-mt-24 lg:col-span-5">
                        <div class="relative mx-auto max-w-[340px] rounded-[2.5rem] border-4 border-slate-800 bg-slate-950 p-3 shadow-2xl shadow-violet-500/10">
                            <div class="mx-auto mb-3 flex h-4 w-28 items-center justify-center rounded-full bg-slate-800">
                                <div class="mr-2 h-1.5 w-1.5 rounded-full bg-slate-700"></div>
                                <div class="h-1 w-8 rounded-full bg-slate-700"></div>
                            </div>
                            <div class="flex min-h-[460px] flex-col justify-between overflow-hidden rounded-[2rem] p-5 text-center transition-all duration-300"
                                 :style="`background:${themes[theme].background};color:${themes[theme].text}`">
                                <div class="space-y-4">
                                    <div class="space-y-2">
                                        <div class="mx-auto h-[4.5rem] w-[4.5rem] rounded-full bg-gradient-to-tr from-violet-500 to-fuchsia-500 p-0.5 shadow-md">
                                            <div class="flex h-full w-full items-center justify-center rounded-full bg-slate-900 text-xl font-bold text-white">M</div>
                                        </div>
                                        <div>
                                            <div class="text-base font-bold tracking-tight">@mira</div>
                                            <div class="text-xs" :style="`color:${themes[theme].bio}`">{{ __('Illustrator · Jakarta') }}</div>
                                        </div>
                                    </div>
                                    <div class="space-y-2.5 pt-2">
                                        @foreach ([['i-globe', __('Portfolio')], ['i-image', __('Latest drawings')], ['i-note', __('Commission prices')], ['i-card', __('Save my contact')]] as $demo)
                                            <div class="flex items-center justify-between rounded-xl p-3 text-xs font-semibold transition-all"
                                                 :style="`background:${themes[theme].link_bg};color:${themes[theme].link_text};border:1px solid ${themes[theme].link_border || 'transparent'}`">
                                                <span class="flex items-center gap-2"><svg class="h-4 w-4"><use href="#{{ $demo[0] }}"/></svg><span>{{ $demo[1] }}</span></span>
                                                <svg class="h-3.5 w-3.5 opacity-60 rtl:-scale-x-100"><use href="#i-arrow"/></svg>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="flex items-center justify-center gap-1.5 pt-4 text-[11px] opacity-70" dir="ltr">
                                    <svg class="h-3.5 w-3.5"><use href="#i-qr"/></svg><span>hel.ink/b/mira</span>
                                </div>
                            </div>
                        </div>
                        <p class="mt-4 text-center text-xs text-slate-500 dark:text-slate-400">
                            {{ __('Theme:') }} <strong class="text-slate-700 dark:text-slate-200" x-text="themes[theme].name"></strong> ·
                            <a href="#themes" class="text-violet-600 underline underline-offset-2 dark:text-violet-400">{{ __('Try another') }}</a>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Three steps --}}
        <section class="border-t border-slate-200/80 py-20 dark:border-white/10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="text-center text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('Live in three steps') }}</h2>
                <ol class="mt-12 grid gap-6 md:grid-cols-3">
                    @foreach ([[__('Claim your name'), __('Pick hel.ink/b/yourname and sign in with a free HOP ID.')], [__('Add your blocks'), __('Links, text, images, videos, music, a map or a contact card, in any order.')], [__('Share it everywhere'), __('Put the link in your profiles, or print the QR code.')]] as $i => $step)
                        <li class="rounded-3xl border border-slate-200/80 bg-white/70 p-6 dark:border-white/10 dark:bg-white/[0.03]">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-500 font-bold text-white">{{ $i + 1 }}</span>
                            <h3 class="mt-4 text-lg font-bold">{{ $step[0] }}</h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $step[1] }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- Theme gallery: tap one and the preview above changes --}}
        <section id="themes" class="scroll-mt-20 border-t border-slate-200/80 bg-slate-100/60 py-20 dark:border-white/10 dark:bg-white/[0.02]">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto mb-12 max-w-2xl space-y-3 text-center">
                    <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('Try a theme') }}</h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('Tap a theme to see it on the example page. Every theme can be tuned with your own colors, fonts and CSS.') }}</p>
                </div>
                <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6" role="list">
                    @foreach ($themes as $key => $t)
                        <li>
                            <button type="button" @click="theme = '{{ $key }}'; document.getElementById('preview').scrollIntoView({ behavior: 'smooth', block: 'center' })"
                                    :aria-pressed="theme === '{{ $key }}'"
                                    class="group block w-full overflow-hidden rounded-2xl border-2 text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500"
                                    :class="theme === '{{ $key }}' ? 'border-violet-500' : 'border-transparent'">
                                <span class="flex h-28 flex-col justify-center gap-1.5 px-4" style="background: {{ $t['background'] }}">
                                    <span class="h-2.5 rounded-full" style="background: {{ $t['link_bg'] }}; outline: 1px solid {{ $t['link_border'] ?? 'rgb(0 0 0 / .08)' }}"></span>
                                    <span class="h-2.5 rounded-full" style="background: {{ $t['link_bg'] }}; outline: 1px solid {{ $t['link_border'] ?? 'rgb(0 0 0 / .08)' }}"></span>
                                    <span class="h-2.5 w-2/3 rounded-full" style="background: {{ $t['link_bg'] }}; outline: 1px solid {{ $t['link_border'] ?? 'rgb(0 0 0 / .08)' }}"></span>
                                </span>
                                <span class="block bg-white px-3 py-2 text-xs font-semibold text-slate-700 dark:bg-slate-900 dark:text-slate-200">{{ $t['name'] }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- Features --}}
        <section id="features" class="scroll-mt-20 border-t border-slate-200/80 py-20 dark:border-white/10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto mb-12 max-w-2xl space-y-3 text-center">
                    <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('Everything you need, nothing you pay for') }}</h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('No premium tier and no locked features.') }}</p>
                </div>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                    @foreach ([['i-palette', __('20+ themes'), __('Gradients, animations and button effects, or your own colors, fonts and CSS.')], ['i-chart', __('Click analytics'), __('Views and clicks per block, counted without storing visitor IP addresses.')], ['i-lock', __('Private pages'), __('A password or an 18+ check before the page opens.')], ['i-qr', __('Styled QR codes'), __('A QR code for your page, ready to print.')]] as $f)
                        <div class="rounded-3xl border border-slate-200/80 bg-white/70 p-6 dark:border-white/10 dark:bg-white/[0.03]">
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-500/10 text-violet-600 dark:text-violet-400"><svg class="h-5 w-5"><use href="#{{ $f[0] }}"/></svg></span>
                            <h3 class="mt-4 text-base font-bold">{{ $f[1] }}</h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $f[2] }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="mt-10 text-center text-sm"><a href="{{ route('pages.features') }}" class="font-semibold text-violet-600 underline underline-offset-4 dark:text-violet-400">{{ __('See all features') }}</a> · <a href="https://hel.ink/b/hel" class="font-semibold text-violet-600 underline underline-offset-4 dark:text-violet-400">{{ __('Open a live example') }}</a></p>
            </div>
        </section>

        {{-- HOP family --}}
        <section id="family" class="scroll-mt-20 border-t border-slate-200/80 py-20 dark:border-white/10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mx-auto mb-12 max-w-2xl space-y-3 text-center">
                    <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('Part of the HOP family') }}</h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('One free HOP ID signs you in to every app.') }}</p>
                </div>
                <ul class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4" role="list">
                    @foreach (collect(\Hop\Ui\Assets::family('biohop'))->reject(fn ($app) => $app['current']) as $app)
                        <li>
                            <a href="{{ $app['url'] }}" class="group flex h-full flex-col rounded-3xl border border-slate-200/80 bg-white/70 p-6 shadow-sm transition hover:border-violet-500/40 hover:shadow-md dark:border-white/10 dark:bg-white/[0.03]">
                                <div class="mb-4 flex items-center justify-between">
                                    <img src="{{ $app['icons']['mark']['url'] }}" alt="" width="40" height="40" loading="lazy" class="h-10 w-10 rounded-xl">
                                    <svg class="h-4 w-4 text-slate-400 transition-transform group-hover:translate-x-1 rtl:-scale-x-100" aria-hidden="true"><use href="#i-arrow"/></svg>
                                </div>
                                <h3 class="mb-1 text-base font-bold text-slate-900 dark:text-white">{{ $app['name'] }}</h3>
                                <p class="text-xs text-slate-600 dark:text-slate-400">{{ $app['tagline'] }}</p>
                                <span class="mt-3 font-mono text-xs text-slate-500 dark:text-slate-400" dir="ltr">{{ parse_url($app['url'], PHP_URL_HOST) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- FAQ --}}
        <section id="faq" class="scroll-mt-20 border-t border-slate-200/80 bg-slate-100/60 py-20 dark:border-white/10 dark:bg-white/[0.02]">
            <div class="mx-auto max-w-3xl px-4 sm:px-6">
                <h2 class="text-center text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('Questions') }}</h2>
                <div class="mt-10 space-y-3">
                    @foreach ([[__('Is BioHop really free?'), __('Yes. No paid plan and no locked feature. The code is MIT licensed.')], [__('Do I need an account?'), __('Yes, a free HOP ID. The same account works in LinkHop, PixelHop and NoteHop.')], [__('Do you track my visitors?'), __('Views and clicks are counted with a keyed hash of the IP address, never the address itself. Pixels you add load only after the visitor agrees.')], [__('Can I move my page later?'), __('Yes. You can rename it, and export everything from the settings.')]] as $qa)
                        <details class="group rounded-2xl border border-slate-200/80 bg-white/80 p-5 dark:border-white/10 dark:bg-white/[0.03]">
                            <summary class="cursor-pointer list-none font-semibold">{{ $qa[0] }}</summary>
                            <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">{{ $qa[1] }}</p>
                        </details>
                    @endforeach
                </div>
                <p class="mt-8 text-center text-sm"><a href="{{ route('pages.faq') }}" class="font-semibold text-violet-600 underline underline-offset-4 dark:text-violet-400">{{ __('More answers') }}</a></p>
            </div>
        </section>

        {{-- Call to action --}}
        <section class="border-t border-slate-200/80 py-20 dark:border-white/10">
            <div class="mx-auto max-w-3xl px-4 text-center sm:px-6">
                <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Your page is one minute away.') }}</h2>
                <a href="{{ route('register') }}" class="mt-8 inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-violet-600 to-fuchsia-600 px-7 py-4 font-semibold text-white shadow-lg shadow-violet-600/20 transition hover:from-violet-500 hover:to-fuchsia-500">
                    {{ __('Create your bio') }} <svg class="h-4 w-4 rtl:-scale-x-100"><use href="#i-arrow"/></svg>
                </a>
            </div>
        </section>
    </main>

    @include('partials.site-footer')
</body>
</html>
