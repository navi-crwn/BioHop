<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Report a bio page - BioHop</title>
    <link rel="icon" type="image/svg+xml" href="{{ route('brand.favicon', ['v' => 'b10b1000']) }}">
    <x-hop::fonts />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-50 text-slate-900 antialiased dark:bg-[#0e1322] dark:text-slate-100">
    <header class="border-b border-slate-200/80 bg-white/80 backdrop-blur dark:border-white/10 dark:bg-[#0e1322]/80">
        <div class="mx-auto flex h-16 max-w-3xl items-center justify-between px-5">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 font-semibold" aria-label="BioHop home">
                <img src="{{ route('brand.logo', ['v' => 'b10b1000']) }}" alt="" class="h-8 w-8 rounded-lg">
                <span>BioHop</span>
            </a>
            <a href="{{ route('home') }}" class="text-sm text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">&larr; Back to BioHop</a>
        </div>
    </header>

    <main class="mx-auto max-w-2xl px-5 py-12">
        <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm dark:border-white/10 dark:bg-white/5">
            <h1 class="text-2xl font-semibold">Report a bio page</h1>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                Tell us about scams, phishing, impersonation, malware, or anything else that breaks the rules.
                A moderator reviews every report.
            </p>

            @if (session('status'))
                <div class="mt-5 rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-400/40 dark:bg-emerald-500/10 dark:text-emerald-100" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="mt-5 rounded-2xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-400/40 dark:bg-rose-500/10 dark:text-rose-100" role="alert">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('report.store') }}" class="mt-6 space-y-4"
                  x-data="turnstileForm(@js(config('services.turnstile.site_key')))" x-on:submit="handleSubmit($event)">
                @csrf
                <input type="hidden" name="slug" value="{{ old('slug', $slug) }}">
                <div>
                    <label for="report-url" class="text-sm font-medium">Page address</label>
                    <input id="report-url" type="url" name="url" value="{{ old('url', $url) }}" placeholder="https://hel.ink/b/…"
                           class="mt-1 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 focus:border-violet-500 focus:ring-violet-500 dark:border-white/20 dark:bg-white/10">
                </div>
                <div>
                    <label for="report-email" class="text-sm font-medium">Your email <span class="text-slate-500">(so we can follow up)</span></label>
                    <input id="report-email" type="email" name="email" value="{{ old('email') }}" required
                           class="mt-1 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 focus:border-violet-500 focus:ring-violet-500 dark:border-white/20 dark:bg-white/10">
                </div>
                <div>
                    <label for="report-reason" class="text-sm font-medium">What is wrong?</label>
                    <textarea id="report-reason" name="reason" rows="5" required minlength="10" maxlength="1000"
                              class="mt-1 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 focus:border-violet-500 focus:ring-violet-500 dark:border-white/20 dark:bg-white/10">{{ old('reason') }}</textarea>
                </div>
                <x-turnstile />
                <button type="submit" class="w-full rounded-2xl bg-gradient-to-r from-violet-500 to-fuchsia-500 py-3 font-semibold text-white shadow-lg shadow-violet-500/30 hover:opacity-95">
                    <span x-show="!waiting">Send report</span><span x-cloak x-show="waiting">Checking…</span>
                </button>
                <p class="text-xs text-slate-500 dark:text-slate-400">To prevent spam, each network can send a few reports per half hour.</p>
            </form>
        </div>
    </main>
</body>
</html>
