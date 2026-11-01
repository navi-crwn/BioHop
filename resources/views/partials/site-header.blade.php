{{-- BioHop's header: brand to BioHop's home, BioHop's own pages; the hub only for the account. --}}
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/80 backdrop-blur-xl dark:border-white/10 dark:bg-[#0e1322]/80">
        <div class="mx-auto flex min-h-16 max-w-7xl flex-nowrap items-center justify-between gap-2 px-3 py-2 sm:px-6 lg:px-8">
            <!-- Brand -->
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5 focus:outline-none focus:ring-2 focus:ring-violet-500 rounded-lg">
                <img src="{{ route('brand.favicon', ['v' => 'b10b1000']) }}" alt="" width="36" height="36" class="h-9 w-9 rounded-xl">
                <span class="font-bold text-lg tracking-tight max-[440px]:hidden">BioHop</span>
                <span class="hidden sm:inline rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium tracking-wide text-slate-600 dark:bg-white/10 dark:text-slate-300">by hel.ink</span>
            </a>

            <!-- Navigation Links -->
            <nav class="hidden xl:flex items-center gap-5 text-sm font-medium text-slate-600 dark:text-slate-300" aria-label="{{ __('Menu') }}">
                @php
                    $mainNav = [__('Features') => route('pages.features'), __('Themes') => route('home').'#themes', __('Help') => route('pages.help'), __('FAQ') => route('pages.faq'), __('HOP family') => route('home').'#family'];
                @endphp
                @foreach ($mainNav as $label => $href)
                    <a href="{{ $href }}" class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">{{ $label }}</a>
                @endforeach
            </nav>

            <!-- Actions -->
            <div class="flex min-w-0 flex-nowrap items-center gap-1.5 sm:gap-2">
                <x-hop::app-switcher app="biohop" />
                <details class="hop-switcher xl:hidden" data-hop-switcher>
                    <summary aria-label="{{ __('Menu') }}"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg></summary>
                    <nav class="hop-switcher-panel" aria-label="{{ __('Menu') }}">
                        @foreach ($mainNav as $label => $href)<a href="{{ $href }}">{{ $label }}</a>@endforeach
                    </nav>
                </details>
                {{-- Language: plain links (?lang=xx), no script needed. --}}
                <details class="relative" data-hop-switcher>
                    <summary class="flex cursor-pointer list-none items-center gap-1 rounded-xl border border-slate-200/80 px-2 py-2 text-xs font-semibold uppercase text-slate-600 hover:bg-slate-100 dark:border-white/10 dark:text-slate-300 dark:hover:bg-white/5" aria-label="{{ __('Language') }}: {{ strtoupper(app()->getLocale()) }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                        <span class="max-[440px]:hidden">{{ app()->getLocale() }}</span>
                    </summary>
                    <ul class="absolute start-0 z-50 mt-2 w-44 rounded-2xl border border-slate-200/80 bg-white p-1.5 text-sm shadow-lg dark:border-white/10 dark:bg-slate-900">
                        @foreach (\App\Http\Middleware\SetLocale::LOCALES as $code => $name)
                            <li><a href="?lang={{ $code }}" lang="{{ $code }}" @class(['block rounded-xl px-3 py-2 hover:bg-slate-100 dark:hover:bg-white/5', 'font-bold text-violet-600 dark:text-violet-400' => app()->getLocale() === $code])>{{ $name }}</a></li>
                        @endforeach
                    </ul>
                </details>
                <button type="button"
                    onclick="try { var d = document.documentElement.classList.toggle('dark'); localStorage.setItem('helink-theme', d ? 'dark' : 'light'); } catch (e) {}"
                    class="rounded-xl border border-slate-200/80 p-2 text-slate-600 hover:bg-slate-100 max-[360px]:hidden dark:border-white/10 dark:text-slate-300 dark:hover:bg-white/5 transition-colors"
                    aria-label="{{ __('Toggle theme') }}">
                    <svg class="h-4 w-4 hidden dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                    <svg class="h-4 w-4 block dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>

                @auth
                    <a href="{{ \App\Models\User::hubUrl('dashboard') }}#biohop" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 transition-colors">
                        {{ __('Dashboard') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="hidden sm:inline-flex rounded-xl px-3.5 py-2 text-sm font-medium text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white transition-colors">
                        {{ __('Sign in') }}
                    </a>
                    <a href="{{ route('register') }}" class="whitespace-nowrap rounded-xl bg-gradient-to-r from-violet-500 to-fuchsia-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-violet-500/20 hover:from-violet-600 hover:to-fuchsia-700 transition-all">
                        <span class="max-sm:hidden">{{ __('Create your bio') }}</span><span class="sm:hidden">{{ __('Create') }}</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>
