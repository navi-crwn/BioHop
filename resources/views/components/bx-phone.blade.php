@props(['id' => null])
{{-- A sample bio page in a phone. The scenes on the home page restyle it as you scroll. Sample content, not a real page. --}}
<div class="bx-phone" @if ($id) id="{{ $id }}" @endif data-lenis-prevent>
    <div class="bx-phone-in">
        <span class="bx-av" aria-hidden="true">M</span>
        <b class="bx-name">Mira Studio</b>
        <p class="bx-bio">{{ __('Illustrator and motion designer in Bandung.') }}</p>
        <a class="bx-link" href="#"><span>{{ __('Portfolio') }}</span><i aria-hidden="true">↗</i></a>
        <a class="bx-link" href="#"><span>{{ __('New short film') }}</span><i aria-hidden="true">▶</i></a>
        <a class="bx-link" href="#"><span>{{ __('Studio playlist') }}</span><i aria-hidden="true">♪</i></a>
        <a class="bx-link" href="#"><span>{{ __('Book a call') }}</span><i aria-hidden="true">↗</i></a>
        {{ $slot }}
    </div>
</div>
