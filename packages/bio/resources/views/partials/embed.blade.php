{{-- Video and music blocks on the public page; same URL rules as the editor preview (Hop\Bio\Support\BioEmbed). --}}
@php
    $embed = in_array($link->type, ['music', 'spotify', 'soundcloud'], true)
        ? \Hop\Bio\Support\BioEmbed::music($link->embed_url)
        : \Hop\Bio\Support\BioEmbed::video($link->embed_url);
    $frameTitle = $link->title ?: ($embed && $embed['kind'] === 'music' ? 'Music player' : 'Video player');
@endphp
@if($embed)
    <div class="bio-embed w-full rounded-xl overflow-hidden {{ $embed['kind'] === 'music' ? 'p-3' : '' }}" style="background: rgba(0,0,0,0.05);">
        @if($link->title)
            <div class="{{ $embed['kind'] === 'music' ? 'mb-2' : 'px-4 py-2' }} text-sm font-medium" style="color: inherit;">{{ $link->title }}</div>
        @endif
        @php $apple = str_starts_with($embed['src'], 'https://embed.music.apple.com/') ? 'allow-forms allow-popups allow-same-origin allow-scripts allow-storage-access-by-user-activation allow-top-navigation-by-user-activation' : null; @endphp
        @if($embed['kind'] === 'video')
            <div class="aspect-video">
                @include('bio::partials.facade', ['src' => $embed['src'], 'href' => $link->embed_url, 'title' => $frameTitle, 'kind' => 'video', 'height' => null, 'sandbox' => null])
            </div>
        @else
            @include('bio::partials.facade', ['src' => $embed['src'], 'href' => $link->embed_url, 'title' => $frameTitle, 'kind' => $embed['kind'], 'height' => $embed['height'], 'sandbox' => $apple])
        @endif
    </div>
@endif
