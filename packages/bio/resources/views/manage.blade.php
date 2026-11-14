<x-app-layout>
    <x-slot name="pageTitle">BioHop - {{ __('Manage') }}</x-slot>
    <x-page-header :title="__('Manage')" :subtitle="__('Prices, stock and goals of all your pages in one place. Everything else stays in the editor.')" />
    <x-flash />

    @php
        $text = ['saving' => __('Saving…'), 'saved' => __('Saved'), 'failed' => __('Something went wrong. Nothing was saved.')];
        $str = fn ($v) => ($v === null || $v === '') ? '' : (string) $v;
    @endphp

    @if ($pages->isEmpty())
        <section class="glass mt-4 rounded-2xl p-5 space-y-3">
            <p class="text-sm text-muted">{{ __('No products, goals, polls or forms yet. Add them in the editor and they show up here.') }}</p>
            <a href="{{ route('bio.index') }}" class="btn-soft btn-sm">{{ __('My bio pages') }}</a>
        </section>
    @endif

    <div class="mt-4 space-y-4">
        @foreach ($pages as $page)
            @php
                $edit = route('bio.edit', $page).'?tab=links';
                $products = $page->links->where('type', 'product');
                $goals = $page->links->where('type', 'goal');
                $polls = $page->links->where('type', 'poll');
                $forms = $page->links->where('type', 'form');
            @endphp
            <section class="glass rounded-2xl p-4 sm:p-5 space-y-5" aria-labelledby="manage-page-{{ $page->id }}">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 id="manage-page-{{ $page->id }}" class="min-w-0 font-semibold text-ink">{{ $page->title ?: $page->slug }} <span class="text-sm font-normal text-muted">/{{ $page->slug }}</span></h2>
                    <a href="{{ $edit }}" class="btn-soft btn-sm">{{ __('Edit in editor') }}</a>
                </div>

                @if ($products->isNotEmpty())
                    <div>
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Products') }}</h3>
                        <div class="manage-row manage-head hidden px-0 pb-1 text-xs font-medium text-muted md:grid" aria-hidden="true">
                            <span>{{ __('Name') }}</span><span>{{ __('Price') }}</span><span>{{ __('Was price') }}</span><span>{{ __('Stock') }}</span><span class="text-center">{{ __('Sold out') }}</span><span class="text-center">{{ __('Shown') }}</span><span></span>
                        </div>
                        <div class="divide-y divide-slate-200/70 dark:divide-slate-700/60">
                            @foreach ($products as $p)
                                @php
                                    $cfg = ['type' => 'product', 'url' => route('bio.manage.update', $p), 'version' => $p->contentVersion(), 'settingKeys' => ['price', 'compare_price', 'stock'], 'text' => $text,
                                        'fields' => ['title' => (string) $p->title, 'price' => $str($p->settings['price'] ?? null), 'compare_price' => $str($p->settings['compare_price'] ?? null), 'stock' => $str($p->settings['stock'] ?? null), 'sold_out' => (bool) ($p->settings['sold_out'] ?? false), 'is_active' => (bool) $p->is_active]];
                                    $cur = strtoupper((string) ($p->settings['currency'] ?? ''));
                                @endphp
                                <form x-data="manageRow(@js($cfg))" @submit.prevent="save()" class="manage-row py-3 first:pt-1" data-manage-row="product" novalidate>
                                    <div class="manage-name">
                                        <label class="label md:sr-only" for="mp-name-{{ $p->id }}">{{ __('Name') }}</label>
                                        <input id="mp-name-{{ $p->id }}" class="input" type="text" maxlength="100" required x-model="f.title">
                                        @if (count($p->variants()) > 0)<p class="mt-1 text-xs text-muted">{{ __('Has variants: edit them in the editor.') }}</p>@endif
                                    </div>
                                    <div>
                                        <label class="label md:sr-only" for="mp-price-{{ $p->id }}">{{ __('Price') }}{{ $cur ? ' ('.$cur.')' : '' }}</label>
                                        <input id="mp-price-{{ $p->id }}" class="input" type="text" inputmode="decimal" maxlength="12" x-model="f.price">
                                    </div>
                                    <div>
                                        <label class="label md:sr-only" for="mp-was-{{ $p->id }}">{{ __('Was price') }}</label>
                                        <input id="mp-was-{{ $p->id }}" class="input" type="text" inputmode="decimal" maxlength="12" x-model="f.compare_price">
                                    </div>
                                    <div class="manage-stock">
                                        <label class="label md:sr-only" for="mp-stock-{{ $p->id }}">{{ __('Stock') }}</label>
                                        <input id="mp-stock-{{ $p->id }}" class="input" type="text" inputmode="numeric" maxlength="6" placeholder="{{ __('Unlimited') }}" x-model="f.stock">
                                    </div>
                                    <label class="manage-check"><input type="checkbox" x-model="f.sold_out"><span class="md:sr-only">{{ __('Sold out') }}</span></label>
                                    <label class="manage-check"><input type="checkbox" x-model="f.is_active"><span class="md:sr-only">{{ __('Shown') }}</span></label>
                                    <div class="manage-act">
                                        <button type="submit" class="btn-primary btn-sm" :disabled="!dirty || busy">{{ __('Save') }}</button>
                                    </div>
                                    <p class="manage-msg text-xs" role="status" aria-live="polite" :class="state === 'ok' ? 'text-emerald-600 dark:text-emerald-400' : (state ? 'text-rose-600 dark:text-rose-400' : 'text-muted')" x-show="msg" x-text="msg" x-cloak></p>
                                    <p class="manage-msg text-xs" x-show="state === 'conflict'" x-cloak><button type="button" class="underline" data-reload>{{ __('Reload the page') }}</button></p>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($goals->isNotEmpty())
                    <div>
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Goals') }}</h3>
                        <div class="divide-y divide-slate-200/70 dark:divide-slate-700/60">
                            @foreach ($goals as $g)
                                @php
                                    $cfg = ['type' => 'goal', 'url' => route('bio.manage.update', $g), 'version' => $g->contentVersion(), 'settingKeys' => ['current', 'target', 'unit'], 'text' => $text,
                                        'fields' => ['current' => $str($g->settings['current'] ?? null), 'target' => $str($g->settings['target'] ?? null), 'unit' => $str($g->settings['unit'] ?? null)]];
                                @endphp
                                <form x-data="manageRow(@js($cfg))" @submit.prevent="save()" class="manage-row manage-goal py-3 first:pt-1" data-manage-row="goal" novalidate>
                                    <p class="manage-name truncate text-sm font-medium text-ink">{{ $g->title ?: __('Goal') }} <span class="chip-gray ml-1">{{ $g->goalPercent() }}%</span></p>
                                    <div>
                                        <label class="label" for="mg-cur-{{ $g->id }}">{{ __('So far') }}</label>
                                        <input id="mg-cur-{{ $g->id }}" class="input" type="text" inputmode="decimal" maxlength="15" required x-model="f.current">
                                    </div>
                                    <div>
                                        <label class="label" for="mg-tgt-{{ $g->id }}">{{ __('Goal') }}</label>
                                        <input id="mg-tgt-{{ $g->id }}" class="input" type="text" inputmode="decimal" maxlength="15" required x-model="f.target">
                                    </div>
                                    <div>
                                        <label class="label" for="mg-unit-{{ $g->id }}">{{ __('Unit') }}</label>
                                        <input id="mg-unit-{{ $g->id }}" class="input" type="text" maxlength="20" x-model="f.unit">
                                    </div>
                                    <div class="manage-act">
                                        <button type="submit" class="btn-primary btn-sm" :disabled="!dirty || busy">{{ __('Save') }}</button>
                                    </div>
                                    <p class="manage-msg text-xs" role="status" aria-live="polite" :class="state === 'ok' ? 'text-emerald-600 dark:text-emerald-400' : (state ? 'text-rose-600 dark:text-rose-400' : 'text-muted')" x-show="msg" x-text="msg" x-cloak></p>
                                    <p class="manage-msg text-xs" x-show="state === 'conflict'" x-cloak><button type="button" class="underline" data-reload>{{ __('Reload the page') }}</button></p>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($polls->isNotEmpty() || $forms->isNotEmpty())
                    <div>
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted">{{ __('Polls and forms') }} <span class="font-normal normal-case">· {{ __('to read here, edited in the editor') }}</span></h3>
                        <div class="divide-y divide-slate-200/70 dark:divide-slate-700/60">
                            @foreach ($polls as $poll)
                                @php $res = $poll->pollResults(); $opts = $poll->pollOptions(); @endphp
                                <div class="py-3 first:pt-1" data-manage-poll>
                                    <p class="text-sm font-medium text-ink">{{ $poll->title ?: __('Poll') }} <span class="chip-gray ml-1">{{ trans_choice(':count vote|:count votes', $res['total'], ['count' => $res['total']]) }}</span></p>
                                    <ul class="mt-1 space-y-0.5 text-sm text-muted">
                                        @foreach ($opts as $i => $label)
                                            <li class="flex justify-between gap-3"><span class="min-w-0 truncate">{{ $label }}</span><span class="shrink-0 tabular-nums">{{ $res['counts'][$i] ?? 0 }}@if ($res['total'] > 0) · {{ (int) round(($res['counts'][$i] ?? 0) / $res['total'] * 100) }}%@endif</span></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                            @foreach ($forms as $form)
                                <div class="flex flex-wrap items-center justify-between gap-2 py-3 first:pt-1" data-manage-form>
                                    <p class="text-sm font-medium text-ink">{{ $form->title ?: __('Form') }} <span class="chip-gray ml-1">{{ trans_choice(':count entry|:count entries', $form->leads_count, ['count' => $form->leads_count]) }}</span></p>
                                    @if ($form->leads_count > 0)
                                        <a href="{{ route('bio.links.leads.csv', [$page, $form]) }}" class="btn-soft btn-sm">{{ __('Download CSV') }}</a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
        @endforeach
    </div>
</x-app-layout>
