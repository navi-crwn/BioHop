@props(['id' => null, 'links' => true])
{{-- A sample bio page in a phone. The scenes on the home page restyle it as you scroll. Sample content, not a real page. --}}
<div class="bx-phone" @if ($id) id="{{ $id }}" @endif>
    <div class="bx-phone-in">
        <span class="bx-av" aria-hidden="true">H</span>
        <b class="bx-name">Helena Studio</b>
        <p class="bx-bio">{{ __('Illustrator and motion designer in Bandung.') }}</p>
        @if ($links)
        <a class="bx-link" href="#"><span>{{ __('Portfolio') }}</span><i aria-hidden="true">↗</i></a>
        <a class="bx-link" href="#"><span>{{ __('New short film') }}</span><i aria-hidden="true">▶</i></a>
        <a class="bx-link" href="#"><span>{{ __('Studio playlist') }}</span><i aria-hidden="true">♪</i></a>
        <a class="bx-link" href="#"><span>{{ __('Book a call') }}</span><i aria-hidden="true">↗</i></a>
        <span class="bx-blk bx-blk-img" aria-hidden="true"><i class="sun"></i><i class="hill"></i><small>{{ __('Latest work') }}</small></span>
        <span class="bx-blk bx-blk-vid" aria-hidden="true"><i class="play"></i><small>{{ __('New short film') }}</small></span>
        <span class="bx-blk bx-blk-mus" aria-hidden="true"><b><i></i><i></i><i></i><i></i><i></i></b><small>{{ __('Studio playlist') }}</small></span>
        @endif
        {{ $slot }}
    </div>
</div>
