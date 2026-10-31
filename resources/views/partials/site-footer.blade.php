{{-- BioHop's footer: its own pages, policies, the HOP family from the shared manifest. --}}
@php
    $footerColumns = [
        'BioHop' => [[__('About'), route('pages.about')], [__('Features'), route('pages.features')], [__('Status'), route('pages.status')], ['GitHub', 'https://github.com/navi-crwn/BioHop']],
        __('Support') => [[__('Help'), route('pages.help')], [__('FAQ'), route('pages.faq')], [__('Contact'), route('pages.contact')], [__('Report abuse'), route('report.create')]],
        __('Policies') => [[__('Terms'), route('pages.terms')], [__('Privacy'), route('pages.privacy')]],
        __('HOP family') => collect(\Hop\Ui\Assets::family('biohop'))->reject(fn ($a) => $a['current'])->map(fn ($a) => [$a['name'], $a['url']])->values()->all(),
    ];
@endphp
<footer class="border-t border-slate-200/80 bg-white py-12 dark:border-white/10 dark:bg-[#0e1322]">
    <div class="mx-auto grid max-w-7xl grid-cols-2 gap-8 px-4 text-sm sm:px-6 md:grid-cols-5 lg:px-8">
        <div class="col-span-2 md:col-span-1">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-slate-900 dark:text-white">
                <img src="{{ route('brand.favicon', ['v' => 'b10b1000']) }}" alt="" width="28" height="28" class="h-7 w-7 rounded-lg"> BioHop
            </a>
            <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">{{ __('One link-in-bio page for everything you do.') }} {{ __('Free and open source (MIT), part of the HOP family.') }}</p>
        </div>
        @foreach ($footerColumns as $heading => $links)
            <div>
                <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-900 dark:text-white">{{ $heading }}</h2>
                <ul class="mt-3 space-y-2 text-slate-500 dark:text-slate-400">
                    @foreach ($links as $link)
                        <li><a href="{{ $link[1] }}" class="transition-colors hover:text-violet-600 dark:hover:text-violet-400">{{ $link[0] }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
    <div class="mx-auto mt-10 flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 text-xs text-slate-400 sm:px-6 lg:px-8">
        <p>&copy; {{ date('Y') }} navi-crwn · MIT License</p>
        <nav aria-label="{{ __('Language') }}" class="flex flex-wrap gap-x-3 gap-y-1">
            @foreach (\App\Http\Middleware\SetLocale::LOCALES as $code => $name)
                <a href="?lang={{ $code }}" lang="{{ $code }}" @class(['hover:text-violet-600 dark:hover:text-violet-400', 'font-semibold text-slate-600 dark:text-slate-200' => app()->getLocale() === $code])>{{ $name }}</a>
            @endforeach
        </nav>
    </div>
</footer>
