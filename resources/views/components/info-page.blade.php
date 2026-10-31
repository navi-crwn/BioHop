{{-- A dedicated page on bio.hel.ink (features, about, help, …): BioHop's header and footer,
     a back link to BioHop's home, a title and the page body. --}}
@props(['title', 'lead' => null, 'description' => null, 'wide' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __($title) }} · BioHop</title>
    <meta name="description" content="{{ $description ?? ($lead ? __($lead) : __('One link-in-bio page for everything you do.')) }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" type="image/svg+xml" href="{{ route('brand.favicon', ['v' => 'b10b1000']) }}">
    <x-hop::fonts />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script nonce="{{ Vite::cspNonce() }}">
        (function () { try { var t = localStorage.getItem('helink-theme'); if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) { document.documentElement.classList.add('dark'); } } catch (e) {} })();
    </script>
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased dark:bg-[#0e1322] dark:text-slate-100">
    @include('partials.site-header')
    <main id="main" class="mx-auto {{ $wide ? 'max-w-6xl' : 'max-w-3xl' }} px-4 pb-16 pt-8 sm:px-6">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 transition hover:text-slate-900 dark:text-slate-400 dark:hover:text-white">
            <span aria-hidden="true">&larr;</span> {{ __('Back to BioHop') }}
        </a>
        <h1 class="mt-6 text-4xl font-extrabold tracking-tight sm:text-5xl">{{ __($title) }}</h1>
        @if ($lead)
            <p class="mt-4 text-lg text-slate-600 dark:text-slate-400">{{ __($lead) }}</p>
        @endif
        <div class="info-prose mt-10">
            {{ $slot }}
        </div>
    </main>
    @include('partials.site-footer')
</body>
</html>
