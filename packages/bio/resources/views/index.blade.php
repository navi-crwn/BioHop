<x-app-layout>
    <x-slot name="pageTitle">LinkHop - Link in bio</x-slot>
    @php $max = \Hop\Bio\Http\Controllers\BioPageController::MAX_PAGES; @endphp
    @php
        $hour = (int) now(auth()->user()->timezone ?: config('app.timezone'))->format('G');
        $greeting = $hour < 11 ? __('Good morning') : ($hour < 15 ? __('Good afternoon') : ($hour < 19 ? __('Good evening') : __('Good night')));
        $firstName = trim(explode(' ', trim((string) auth()->user()->name))[0] ?? '') ?: auth()->user()->username;
        $weekMax = max(1, max($week));
        $sparkline = function (array $line) {
            $m = max(1, max($line)); $w = 60; $h = 18; $pts = [];
            foreach ($line as $i => $n) { $pts[] = round($i * $w / 6, 1).','.round($h - 2 - $n / $m * ($h - 4), 1); }
            return implode(' ', $pts);
        };
    @endphp
    <x-page-header title="{{ $greeting }}, {{ $firstName }}" subtitle="{{ __(':count of :max pages used', ['count' => $bioPages->count(), 'max' => $max]) }}">
        <x-slot name="actions">
            <a href="{{ route('bio.import') }}" class="btn-soft btn-sm">{{ __('Import from Linktree') }}</a>
            <a href="{{ route('bio.create') }}" class="btn-primary btn-sm">{{ __('New bio page') }}</a>
        </x-slot>
    </x-page-header>

    <x-flash />

    @unless ($bioPages->isEmpty())
        <div class="mt-4 grid gap-3 md:grid-cols-3">
            <section class="glass-card flex flex-col justify-between gap-4 md:col-span-2">
                <div class="flex flex-1 flex-wrap items-stretch justify-between gap-x-6 gap-y-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-muted">{{ __('Clicks this week') }}</p>
                        <p class="mt-1 text-3xl font-semibold tabular-nums text-ink">{{ number_format($weekClicks) }}</p>
                        @if ($weekDelta !== null)
                            <p class="mt-0.5 text-xs {{ $weekDelta >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500' }}">{{ $weekDelta >= 0 ? '+' : '' }}{{ $weekDelta }}% {{ __('against last week') }}</p>
                        @else
                            <p class="mt-0.5 text-xs text-muted">{{ __('No clicks last week to compare with') }}</p>
                        @endif
                    </div>
                    <div class="flex min-h-24 min-w-0 flex-1 items-stretch gap-1.5 sm:max-w-md" role="img" aria-label="{{ __('Clicks per day, last seven days') }}">
                        @foreach ($week as $i => $n)
                            @php $d = now()->subDays(6 - $i); @endphp
                            <div class="flex min-w-0 flex-1 flex-col items-center gap-1">
                                <div class="flex w-full flex-1 items-end">
                                    <div class="w-full rounded-t-md bg-indigo-500/80" style="height: {{ max(4, round($n / $weekMax * 100)) }}%" title="{{ $d->format('D d M') }}: {{ $n }}"></div>
                                </div>
                                <span class="text-[10px] text-muted">{{ $d->isoFormat('dd') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <dl class="grid grid-cols-2 gap-2 text-center sm:grid-cols-3">
                    <div class="glass-inset rounded-xl py-2"><dt class="text-[11px] text-muted">{{ __('Views, all time') }}</dt><dd class="text-sm font-semibold tabular-nums text-ink">{{ number_format($bioPages->sum('view_count')) }}</dd></div>
                    <div class="glass-inset rounded-xl py-2"><dt class="text-[11px] text-muted">{{ __('Clicks, all time') }}</dt><dd class="text-sm font-semibold tabular-nums text-ink">{{ number_format($bioPages->sum(fn ($p) => $p->getTotalClicks())) }}</dd></div>
                    <div class="glass-inset rounded-xl py-2 max-sm:hidden"><dt class="text-[11px] text-muted">{{ __('Published pages') }}</dt><dd class="text-sm font-semibold tabular-nums text-ink">{{ $bioPages->where('is_published', true)->count() }} / {{ $bioPages->count() }}</dd></div>
                </dl>
            </section>
            @if ($checklist)
                <section class="glass-card" data-checklist>
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="text-sm font-semibold text-ink">{{ $checklist['done'] >= count($checklist['items']) ? __('Your page is set up') : __('Set up your page') }}</h2>
                        <span class="text-xs tabular-nums text-muted">{{ __(':done of :total complete', ['done' => $checklist['done'], 'total' => count($checklist['items'])]) }}</span>
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700" aria-hidden="true"><div class="h-full rounded-full bg-indigo-500" style="width: {{ round($checklist['done'] / count($checklist['items']) * 100) }}%"></div></div>
                    <p class="mt-1 truncate text-xs text-muted">{{ $checklist['page']->title }}</p>
                    <ol class="mt-3 space-y-1.5">
                        @foreach ($checklist['items'] as $item)
                            <li>
                                <a href="{{ route('bio.edit', $checklist['page']) }}?tab={{ $item['tab'] }}" class="flex items-center gap-2 rounded-lg px-1 py-0.5 text-sm hover:bg-slate-100 dark:hover:bg-slate-800 {{ $item['done'] ? 'text-muted line-through' : 'text-ink' }}">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border {{ $item['done'] ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-300 dark:border-slate-600' }}" aria-hidden="true">
                                        @if ($item['done'])<svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>@endif
                                    </span>
                                    <span class="truncate">{{ $item['label'] }}</span>
                                    <span class="sr-only">{{ $item['done'] ? __('done') : __('to do') }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif
        </div>
    @endunless

    @if ($bioPages->isEmpty())
        <div class="glass-card py-12 text-center">
            <p class="text-sm font-semibold text-ink">{{ __('No bio pages yet') }}</p>
            <p class="mx-auto mt-1 max-w-sm text-sm text-muted">{{ __('Share all your important links from one page.') }}</p>
            <a href="{{ route('bio.create') }}" class="btn-primary btn-sm mt-4">{{ __('Create your first bio page') }}</a>
            <p class="mt-3 text-xs text-muted"><a href="{{ route('bio.import') }}" class="underline">{{ __('Coming from Linktree? Import your page.') }}</a></p>
        </div>
    @else
        @php
            $gradients = ['from-indigo-500 to-purple-600', 'from-emerald-500 to-cyan-600', 'from-rose-500 to-fuchsia-600', 'from-amber-500 to-red-500', 'from-sky-500 to-indigo-600'];
        @endphp
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($bioPages as $bioPage)
                <section class="glass overflow-hidden rounded-2xl">
                    <div class="h-16 bg-linear-to-br {{ $gradients[$loop->index % count($gradients)] }}"></div>
                    <div class="-mt-8 space-y-3 px-4 pb-4">
                        <div class="flex items-start gap-3">
                            @if ($bioPage->avatar_url)
                                <img src="{{ bio_media_url($bioPage->avatar_url) }}" alt="" class="h-14 w-14 shrink-0 rounded-2xl border-2 border-white object-cover shadow-sm dark:border-slate-900">
                            @else
                                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border-2 border-white bg-indigo-500 text-xl font-semibold text-white shadow-sm dark:border-slate-900">{{ strtoupper(mb_substr($bioPage->title, 0, 1)) }}</span>
                            @endif
                            <div class="min-w-0 flex-1 pt-9">
                                <div class="flex items-center gap-1.5">
                                    <h2 class="truncate text-sm font-semibold text-ink">{{ $bioPage->title }}</h2>
                                    @unless ($bioPage->is_published)
                                        <span class="chip-amber">{{ __('Draft') }}</span>
                                    @endunless
                                </div>
                                <a href="{{ public_url('b/'.$bioPage->slug) }}" target="_blank" rel="noopener" class="block truncate text-xs text-muted hover:underline">{{ public_host() }}/b/{{ $bioPage->slug }}</a>
                            </div>
                        </div>

                        <dl class="grid grid-cols-4 gap-2 text-center">
                            <div class="glass-inset rounded-xl py-1.5"><dt class="text-[11px] text-muted">{{ __('Views') }}</dt><dd class="text-sm font-semibold tabular-nums text-ink">{{ number_format($bioPage->view_count) }}</dd></div>
                            <div class="glass-inset rounded-xl py-1.5"><dt class="text-[11px] text-muted">{{ __('Clicks') }}</dt><dd class="text-sm font-semibold tabular-nums text-ink">{{ number_format($bioPage->getTotalClicks()) }}</dd></div>
                            <div class="glass-inset rounded-xl py-1.5"><dt class="text-[11px] text-muted">{{ __('Links') }}</dt><dd class="text-sm font-semibold tabular-nums text-ink">{{ number_format($bioPage->links_count) }}</dd></div>
                            <div class="glass-inset rounded-xl py-1.5" title="{{ __('Clicks per day, last seven days') }}"><dt class="text-[11px] text-muted">{{ __('7 days') }}</dt><dd class="flex items-center justify-center gap-1 text-sm font-semibold tabular-nums text-ink"><svg class="h-[18px] w-[60px] overflow-visible text-indigo-500" viewBox="0 0 60 18" aria-hidden="true"><polyline fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round" points="{{ $sparkline($perPage[$bioPage->id] ?? array_fill(0, 7, 0)) }}"/></svg><span class="sr-only">{{ array_sum($perPage[$bioPage->id] ?? []) }}</span></dd></div>
                        </dl>

                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('bio.edit', $bioPage) }}" class="btn-primary btn-sm flex-1 justify-center">{{ __('Edit') }}</a>
                            <a href="{{ route('bio.analytics', $bioPage) }}" class="btn-soft btn-sm flex-1 justify-center">{{ __('Analytics') }}</a>
                            <form action="{{ route('bio.duplicate', $bioPage) }}" method="POST">
                                @csrf
                                <button type="submit" class="icon-btn h-8! w-8!" title="Duplicate" aria-label="Duplicate {{ $bioPage->title }}">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </button>
                            </form>
                            <form action="{{ route('bio.destroy', $bioPage) }}" method="POST" data-confirm="Delete {{ $bioPage->title }}? This can't be undone.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="icon-btn h-8! w-8! hover:text-rose-500!" title="Delete" aria-label="Delete {{ $bioPage->title }}">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-app-layout>
