{{-- The BioHop logo (the pixel mark, no text) plus the name as real text in the app's own typeface (hop-brand.css). --}}
@once
<link rel="stylesheet" href="{{ asset('hop-brand/hop-brand.css') }}">
@endonce
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 text-ink']) }}>
    <img src="{{ route('brand.favicon') }}" alt="" class="aspect-square h-full max-h-9 w-auto">
    <span class="hop-name hop-name-biohop text-xl leading-none">BioHop</span>
</span>
