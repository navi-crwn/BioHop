@props(['isAdmin' => false])
@php
    $icons = [
        'bio' => 'M8 3h8a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm1 4h6M9 11h6m-6 4h3',
        'shield' => 'M12 3 4 6v6c0 4.5 3.4 8.3 8 9 4.6-.7 8-4.5 8-9V6l-8-3Z',
        'chart' => 'M4 20V10m6 10V4m6 16v-7m4 7H2',
        'settings' => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm7.4-3a7.4 7.4 0 0 0-.1-1.2l2-1.6-2-3.4-2.4 1a7.6 7.6 0 0 0-2-1.2L14.5 3h-5l-.4 2.6a7.6 7.6 0 0 0-2 1.2l-2.4-1-2 3.4 2 1.6a7.4 7.4 0 0 0 0 2.4l-2 1.6 2 3.4 2.4-1a7.6 7.6 0 0 0 2 1.2l.4 2.6h5l.4-2.6a7.6 7.6 0 0 0 2-1.2l2.4 1 2-3.4-2-1.6c.1-.4.1-.8.1-1.2Z',
        'user' => 'M20 21a8 8 0 1 0-16 0m8-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
    ];
    $items = array_filter([
        ['label' => 'My bio pages', 'icon' => 'bio', 'href' => route('bio.index'), 'active' => 'bio.*'],
        $isAdmin ? ['label' => 'Admin: bio pages', 'icon' => 'shield', 'href' => route('admin.bio.index'), 'active' => 'admin.bio.index'] : null,
        $isAdmin ? ['label' => 'Admin: monitor', 'icon' => 'chart', 'href' => route('admin.bio.monitor'), 'active' => 'admin.bio.monitor'] : null,
        ['label' => 'Settings', 'icon' => 'settings', 'href' => route('settings'), 'active' => 'settings*'],
        ['label' => 'HOP ID (account)', 'icon' => 'user', 'href' => \App\Models\User::hubUrl('settings'), 'active' => '-'],
    ]);
@endphp
<ul class="space-y-1">
    @foreach ($items as $item)
        <li>
            <a href="{{ $item['href'] }}" class="nav-item {{ request()->routeIs($item['active']) ? 'nav-item-active' : '' }}">
                <svg class="h-[18px] w-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icons[$item['icon']] }}"/></svg>
                <span class="flex-1">{{ $item['label'] }}</span>
            </a>
        </li>
    @endforeach
</ul>
<p class="mt-5 px-3 pb-1 text-[11px] font-semibold uppercase tracking-wide text-muted">Other HOP apps</p>
<ul class="space-y-1">
    {{-- hop/ui: the other apps of the family, each opened signed in with this HOP ID --}}
    <x-hop::nav-items />
</ul>
