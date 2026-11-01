{{-- A placeholder for a third-party player or map. The real iframe is created only when the visitor taps it, so opening the page
     loads nothing from YouTube, Spotify, TikTok, SoundCloud, Vimeo or Google. Without JavaScript it is a plain link to the original.
     Props: $src (embed address), $href (original address), $title, $kind (video|music|tiktok|map), $height (px, null = 16:9), $sandbox. --}}
@php
    $kindLabel = ['video' => __('Play video'), 'music' => __('Play music'), 'tiktok' => __('Play video'), 'map' => __('Show map')][$kind] ?? __('Open');
    $host = preg_replace('/^www\./', '', (string) parse_url($src, PHP_URL_HOST));
    $hostName = ['youtube.com' => 'YouTube', 'player.vimeo.com' => 'Vimeo', 'tiktok.com' => 'TikTok', 'open.spotify.com' => 'Spotify', 'w.soundcloud.com' => 'SoundCloud', 'embed.music.apple.com' => 'Apple Music', 'maps.google.com' => 'Google Maps'][$host] ?? $host;
@endphp
<a href="{{ $href ?: $src }}" target="_blank" rel="noopener nofollow" class="bio-facade bio-facade-{{ $kind }}" aria-label="{{ $kindLabel }}: {{ $title }}"
   data-embed-src="{{ $src }}" data-embed-title="{{ $title }}" data-embed-kind="{{ $kind }}" @if($height) data-embed-height="{{ $height }}" style="height: {{ $height }}px" @endif @if(!empty($sandbox)) data-embed-sandbox="{{ $sandbox }}" @endif>
    <span class="bio-facade-play" aria-hidden="true"><svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor"><path d="M8 5.5v13a1 1 0 001.5.9l11-6.5a1 1 0 000-1.8l-11-6.5A1 1 0 008 5.5z"/></svg></span>
    <span class="bio-facade-text"><b>{{ $kindLabel }}</b><small>{{ __('Loads from :site when you tap', ['site' => $hostName]) }}</small></span>
</a>
