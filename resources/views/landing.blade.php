<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BioHop · One link-in-bio page for everything you do</title>
    <meta name="description" content="Share all your links, social profiles, embeds, vCard and QR code on one fast, customizable page at hel.ink/b/yourname. Free, open source, and powered by your HOP ID.">
    <link rel="canonical" href="{{ url('/') }}">

    <!-- OpenGraph / Social -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="BioHop · One link-in-bio page for everything you do">
    <meta property="og:description" content="Share all your links, social profiles, embeds, vCard and QR code on one fast, customizable page at hel.ink/b/yourname.">
    <meta property="og:image" content="{{ route('brand.logo', ['v' => 'b10b1000']) }}">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" type="image/svg+xml" href="{{ route('brand.favicon', ['v' => 'b10b1000']) }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|instrument-serif:400i" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-slate-50 text-slate-900 antialiased dark:bg-[#0e1322] dark:text-slate-100 min-h-screen selection:bg-emerald-500/20 selection:text-emerald-500 relative overflow-x-hidden font-sans">

    <!-- Ambient background glows -->
    <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[42rem] h-[30rem] rounded-full bg-gradient-to-tr from-emerald-400/20 via-sky-400/20 to-indigo-400/20 blur-3xl dark:from-emerald-600/10 dark:via-sky-600/10 dark:to-indigo-600/10"></div>
        <div class="absolute top-[28rem] -left-20 w-[28rem] h-[28rem] rounded-full bg-gradient-to-br from-teal-400/15 via-emerald-400/15 to-transparent blur-3xl dark:from-teal-600/10 dark:via-emerald-600/10"></div>
    </div>

    <!-- Header / Nav -->
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/80 backdrop-blur-xl dark:border-white/10 dark:bg-[#0e1322]/80">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <!-- Brand -->
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500 rounded-lg">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-sm shadow-emerald-500/30">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 19c0-8 5-13 14-14-1 9-6 14-14 14Z"/>
                        <path d="M5 19c3-4 6-7 10-9"/>
                    </svg>
                </span>
                <span class="font-bold text-lg tracking-tight">BioHop</span>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium tracking-wide text-slate-600 dark:bg-white/10 dark:text-slate-300">by hel.ink</span>
            </a>

            <!-- Navigation Links -->
            <nav class="hidden md:flex items-center gap-7 text-sm font-medium text-slate-600 dark:text-slate-300" aria-label="Main Navigation">
                <a href="#features" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Features</a>
                <a href="#themes" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Themes</a>
                <a href="#family" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">HOP Family</a>
                <a href="https://hel.ink/status" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Status</a>
            </nav>

            <!-- Actions -->
            <div class="flex items-center gap-3">
                <button type="button"
                    onclick="localStorage.theme = document.documentElement.classList.toggle('dark') ? 'dark' : 'light'"
                    class="rounded-xl border border-slate-200/80 p-2 text-slate-600 hover:bg-slate-100 dark:border-white/10 dark:text-slate-300 dark:hover:bg-white/5 transition-colors"
                    aria-label="Toggle theme">
                    <svg class="h-4 w-4 hidden dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                    <svg class="h-4 w-4 block dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>

                @auth
                    <a href="{{ $hubUrl }}" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 transition-colors">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline-flex rounded-xl px-3.5 py-2 text-sm font-medium text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white transition-colors">
                        Sign in
                    </a>
                    <a href="{{ route('register') }}" class="rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-emerald-500/20 hover:from-emerald-600 hover:to-teal-700 transition-all">
                        Create your Bio
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <main>
        <section class="relative pt-12 pb-20 sm:pt-20 sm:pb-28">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
                    
                    <!-- Left: Copy & Claim Slug Form -->
                    <div class="lg:col-span-7 space-y-8">
                        <div class="space-y-4">
                            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3.5 py-1 text-[11px] font-mono uppercase tracking-[0.25em] text-emerald-700 dark:text-emerald-400 font-semibold">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                BioHop · by hel.ink
                            </span>

                            <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-slate-900 dark:text-white leading-[1.1]">
                                hel.ink/b/<span class="font-serif italic font-normal bg-gradient-to-r from-emerald-500 via-teal-400 to-indigo-500 bg-clip-text text-transparent">you</span>
                            </h1>

                            <p class="text-2xl sm:text-3xl font-bold text-slate-800 dark:text-slate-200">
                                One link-in-bio page for everything you do.
                            </p>

                            <p class="text-base sm:text-lg text-slate-600 dark:text-slate-400 max-w-2xl leading-relaxed">
                                Share all your links, social profiles, embeds, vCard and QR code on one fast, customizable page at <code class="rounded bg-slate-200/60 px-1.5 py-0.5 font-mono text-sm dark:bg-white/10 text-emerald-600 dark:text-emerald-400">hel.ink/b/yourname</code>. Free, open source, and powered by your HOP ID.
                            </p>
                        </div>

                        <!-- Interactive Slug Claim Form -->
                        <div x-data="{
                            slug: '',
                            status: null,
                            message: '',
                            loading: false,
                            async check() {
                                const clean = this.slug.trim().toLowerCase();
                                if (clean.length < 3) {
                                    this.status = null;
                                    this.message = '';
                                    return;
                                }
                                this.loading = true;
                                try {
                                    const res = await fetch('/check-slug-public?slug=' + encodeURIComponent(clean));
                                    const data = await res.json();
                                    this.status = data.available ? 'available' : 'unavailable';
                                    this.message = data.message;
                                } catch (e) {
                                    this.status = null;
                                    this.message = '';
                                } finally {
                                    this.loading = false;
                                }
                            }
                        }" class="space-y-3">
                            <form @submit.prevent="if (slug.trim().length >= 3) window.location.href = '{{ route('register') }}'" class="flex flex-col sm:flex-row items-stretch gap-2.5 max-w-lg">
                                <div class="relative flex-1 flex items-center rounded-2xl border border-slate-300/80 bg-white/90 shadow-sm focus-within:border-emerald-500 focus-within:ring-2 focus-within:ring-emerald-500/20 dark:border-white/15 dark:bg-white/5 dark:focus-within:border-emerald-400">
                                    <span class="pl-4 pr-1 text-sm font-mono text-slate-400 dark:text-slate-500 select-none">hel.ink/b/</span>
                                    <input
                                        type="text"
                                        x-model="slug"
                                        @input.debounce.300ms="check()"
                                        placeholder="yourname"
                                        aria-label="Your bio username"
                                        class="w-full bg-transparent py-3 pr-4 text-sm font-mono text-slate-900 placeholder:text-slate-400 focus:outline-none dark:text-white"
                                        required
                                        minlength="3"
                                        maxlength="30"
                                    />
                                    <span x-show="loading" class="pr-3 text-slate-400 animate-spin" aria-hidden="true">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="4" stroke="currentColor" stroke-opacity="0.2"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    </span>
                                </div>
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-6 py-3.5 text-sm font-semibold text-white shadow-md shadow-emerald-600/20 hover:bg-emerald-500 active:scale-95 transition-all">
                                    <span>Claim my page</span>
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                </button>
                            </form>

                            <!-- Feedback message -->
                            <div x-show="message" class="text-xs font-medium pl-1" :class="status === 'available' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'" x-text="message"></div>

                            <p class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5 pt-1">
                                <svg class="h-4 w-4 text-emerald-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                Free forever with your HOP ID · No ads, no telemetry, zero tracking
                            </p>
                        </div>
                    </div>

                    <!-- Right: Interactive 3D Bio Card Mockup -->
                    <div class="lg:col-span-5 relative" x-data="{ theme: 'midnight' }">
                        
                        <!-- Floating decoration badges -->
                        <div class="absolute -top-6 -left-6 z-20 rounded-2xl border border-slate-200/80 bg-white/90 p-3 shadow-lg dark:border-white/10 dark:bg-slate-900/90 backdrop-blur-md hidden sm:flex items-center gap-2.5 motion-reduce:transform-none transform -rotate-3 hover:rotate-0 transition-transform">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            </span>
                            <div class="text-xs">
                                <div class="font-bold text-slate-800 dark:text-slate-200">100% Free</div>
                                <div class="text-slate-500 dark:text-slate-400">Zero subscription fees</div>
                            </div>
                        </div>

                        <!-- Mobile Mockup Wrapper -->
                        <div class="relative mx-auto max-w-[340px] rounded-[2.5rem] border-4 border-slate-800 bg-slate-950 p-3 shadow-2xl shadow-emerald-500/10">
                            <!-- Speaker / Notch -->
                            <div class="mx-auto mb-3 h-4 w-28 rounded-full bg-slate-800 flex items-center justify-center">
                                <div class="h-1.5 w-1.5 rounded-full bg-slate-700 mr-2"></div>
                                <div class="h-1 w-8 rounded-full bg-slate-700"></div>
                            </div>

                            <!-- Screen Container with dynamic theme -->
                            <div class="overflow-hidden rounded-[2rem] p-5 text-center transition-all duration-300 min-h-[460px] flex flex-col justify-between"
                                :class="{
                                    'bg-gradient-to-b from-slate-900 via-slate-950 to-black text-white': theme === 'midnight',
                                    'bg-gradient-to-b from-teal-900 via-emerald-950 to-slate-950 text-emerald-50': theme === 'aurora',
                                    'bg-gradient-to-b from-slate-100 to-slate-200 text-slate-900 border border-slate-300': theme === 'minimal'
                                }">
                                
                                <div class="space-y-4">
                                    <!-- Avatar & Handle -->
                                    <div class="space-y-2">
                                        <div class="mx-auto h-18 w-18 rounded-full ring-4 ring-emerald-500/40 p-0.5 bg-gradient-to-tr from-emerald-500 to-sky-500 shadow-md">
                                            <div class="h-full w-full rounded-full bg-slate-800 flex items-center justify-center text-xl font-bold text-white">
                                                N
                                            </div>
                                        </div>
                                        <div>
                                            <div class="font-bold text-base tracking-tight">@navi</div>
                                            <div class="text-xs opacity-75">Full Stack Creator & Engineer</div>
                                        </div>
                                    </div>

                                    <!-- Social Icons Row -->
                                    <div class="flex items-center justify-center gap-2.5 pt-1">
                                        <span class="p-1.5 rounded-full bg-white/10 hover:bg-white/20 cursor-pointer transition-colors" title="GitHub">
                                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
                                        </span>
                                        <span class="p-1.5 rounded-full bg-white/10 hover:bg-white/20 cursor-pointer transition-colors" title="Website">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                                        </span>
                                        <span class="p-1.5 rounded-full bg-white/10 hover:bg-white/20 cursor-pointer transition-colors" title="Contact">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="m22 6-10 7L2 6"/></svg>
                                        </span>
                                    </div>

                                    <!-- Links Stack -->
                                    <div class="space-y-2.5 pt-2">
                                        <div class="group rounded-xl border border-white/15 bg-white/10 p-3 text-xs font-semibold backdrop-blur-sm hover:scale-[1.02] transition-all cursor-pointer flex items-center justify-between">
                                            <span class="flex items-center gap-2">
                                                <span>🌐</span>
                                                <span>Website & Portfolio</span>
                                            </span>
                                            <span class="text-xs opacity-60">→</span>
                                        </div>

                                        <div class="group rounded-xl border border-white/15 bg-white/10 p-3 text-xs font-semibold backdrop-blur-sm hover:scale-[1.02] transition-all cursor-pointer flex items-center justify-between">
                                            <span class="flex items-center gap-2">
                                                <span>📸</span>
                                                <span>PixelHop Gallery</span>
                                            </span>
                                            <span class="text-xs opacity-60">→</span>
                                        </div>

                                        <div class="group rounded-xl border border-white/15 bg-white/10 p-3 text-xs font-semibold backdrop-blur-sm hover:scale-[1.02] transition-all cursor-pointer flex items-center justify-between">
                                            <span class="flex items-center gap-2">
                                                <span>📝</span>
                                                <span>Secret Notes</span>
                                            </span>
                                            <span class="text-xs opacity-60">→</span>
                                        </div>

                                        <div class="group rounded-xl border border-white/15 bg-white/10 p-3 text-xs font-semibold backdrop-blur-sm hover:scale-[1.02] transition-all cursor-pointer flex items-center justify-between">
                                            <span class="flex items-center gap-2">
                                                <span>📇</span>
                                                <span>Save Contact (vCard)</span>
                                            </span>
                                            <span class="text-xs opacity-60">→</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- QR badge footer -->
                                <div class="pt-4 flex items-center justify-center gap-1.5 text-[11px] opacity-70">
                                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                    <span>hel.ink/b/navi</span>
                                </div>
                            </div>
                        </div>

                        <!-- Theme Picker Controls -->
                        <div class="mt-4 flex items-center justify-center gap-2">
                            <span class="text-xs text-slate-500 font-medium mr-1">Preview Theme:</span>
                            <button
                                type="button"
                                @click="theme = 'midnight'"
                                :class="theme === 'midnight' ? 'ring-2 ring-emerald-500 font-bold' : 'opacity-70'"
                                class="rounded-lg px-2.5 py-1 text-xs bg-slate-900 text-white transition-all">
                                Midnight
                            </button>
                            <button
                                type="button"
                                @click="theme = 'aurora'"
                                :class="theme === 'aurora' ? 'ring-2 ring-emerald-500 font-bold' : 'opacity-70'"
                                class="rounded-lg px-2.5 py-1 text-xs bg-teal-800 text-teal-100 transition-all">
                                Aurora
                            </button>
                            <button
                                type="button"
                                @click="theme = 'minimal'"
                                :class="theme === 'minimal' ? 'ring-2 ring-emerald-500 font-bold' : 'opacity-70'"
                                class="rounded-lg px-2.5 py-1 text-xs bg-slate-200 text-slate-800 transition-all">
                                Minimal
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- Features Grid -->
        <section id="features" class="border-t border-slate-200/80 bg-slate-100/60 py-20 dark:border-white/10 dark:bg-white/[0.02]">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
                    <span class="text-xs font-mono font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Features</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Everything you need in a modern link-in-bio</h2>
                    <p class="text-slate-600 dark:text-slate-400">Crafted for creators, developers, and businesses who want speed, design control, and genuine data privacy.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Feature 1 -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-7 shadow-sm dark:border-white/10 dark:bg-white/5 space-y-3">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-xl font-bold">
                            🎨
                        </span>
                        <h3 class="text-lg font-bold">20+ Themes</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                            From clean minimalist to neon cyber and glass aurora. Customize fonts, background gradients, button borders, and shadows freely.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-7 shadow-sm dark:border-white/10 dark:bg-white/5 space-y-3">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-500/10 text-sky-600 dark:text-sky-400 text-xl font-bold">
                            📊
                        </span>
                        <h3 class="text-lg font-bold">Click Analytics</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                            Track views and individual block clicks privately. Inspect referrers, device types, and visitor countries without third-party ad pixels.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-7 shadow-sm dark:border-white/10 dark:bg-white/5 space-y-3">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-xl font-bold">
                            🔒
                        </span>
                        <h3 class="text-lg font-bold">Password & 18+</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                            Protect sensitive or private bio pages with an instant PIN gate or age verification dialog before links become accessible.
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-7 shadow-sm dark:border-white/10 dark:bg-white/5 space-y-3">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-500/10 text-teal-600 dark:text-teal-400 text-xl font-bold">
                            📱
                        </span>
                        <h3 class="text-lg font-bold">Styled QR Codes</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                            Generate matching vector QR codes for your bio page. Download ready-to-print SVG and PNG files with your custom logo embedded.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- HOP Family Cross Promotion -->
        <section id="family" class="py-20 border-t border-slate-200/80 dark:border-white/10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto space-y-3 mb-12">
                    <span class="text-xs font-mono font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Ecosystem</span>
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">The HOP Suite</h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400">One account unlocks the entire family of privacy-first creator tools.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- LinkHop -->
                    <a href="https://link.hel.ink" class="group rounded-3xl border border-slate-200/80 bg-white/70 p-6 shadow-sm hover:border-emerald-500/40 hover:shadow-md dark:border-white/10 dark:bg-white/5 transition-all">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-mono font-bold text-sky-600 dark:text-sky-400">hel.ink/l/…</span>
                            <span class="text-sm text-slate-400 group-hover:translate-x-1 transition-transform">→</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">LinkHop</h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400">Smart short links with geo-routing, branded domains, and click analytics.</p>
                    </a>

                    <!-- PixelHop -->
                    <a href="https://p.hel.ink" class="group rounded-3xl border border-slate-200/80 bg-white/70 p-6 shadow-sm hover:border-emerald-500/40 hover:shadow-md dark:border-white/10 dark:bg-white/5 transition-all">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400">p.hel.ink</span>
                            <span class="text-sm text-slate-400 group-hover:translate-x-1 transition-transform">→</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">PixelHop</h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400">Fast, metadata-stripped image & media hosting with instant compression.</p>
                    </a>

                    <!-- NoteHop -->
                    <a href="https://note.hel.ink" class="group rounded-3xl border border-slate-200/80 bg-white/70 p-6 shadow-sm hover:border-emerald-500/40 hover:shadow-md dark:border-white/10 dark:bg-white/5 transition-all">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-xs font-mono font-bold text-teal-600 dark:text-teal-400">note.hel.ink</span>
                            <span class="text-sm text-slate-400 group-hover:translate-x-1 transition-transform">→</span>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">NoteHop</h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400">Zero-knowledge end-to-end encrypted notes with self-destruct timers.</p>
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200/80 bg-white py-12 dark:border-white/10 dark:bg-[#0e1322]">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-6 text-xs text-slate-500 dark:text-slate-400">
            <div class="flex items-center gap-2">
                <span class="font-bold text-slate-700 dark:text-slate-300">BioHop</span>
                <span>·</span>
                <span>Part of the HOP Family</span>
            </div>

            <div class="flex items-center gap-6">
                <a href="{{ route('report.create') }}" class="hover:text-slate-700 dark:hover:text-slate-200 transition-colors">Report Abuse</a>
                <a href="https://hel.ink/status" class="hover:text-slate-700 dark:hover:text-slate-200 transition-colors">System Status</a>
                <a href="https://github.com/navi-crwn/BioHop" target="_blank" rel="noopener noreferrer" class="hover:text-slate-700 dark:hover:text-slate-200 transition-colors">GitHub</a>
            </div>

            <div>
                &copy; {{ date('Y') }} navi-crwn. All rights reserved.
            </div>
        </div>
    </footer>

</body>
</html>
