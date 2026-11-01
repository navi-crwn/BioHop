{{-- The BioHop wordmark (resources/brand/logo.svg), inlined: "Bio" is currentColor, which an <img> cannot inherit, so it
     stayed black on the dark theme. The colour is the ink of the theme. --}}
@php
    static $svg = null;
    $svg ??= file_get_contents(resource_path('brand/logo.svg'));
    $classes = $attributes->merge(['class' => 'h-9 w-auto text-ink'])->toHtml();
@endphp
{!! preg_replace('/^<svg /', '<svg '.$classes.' focusable="false" ', $svg, 1) !!}
