{{-- The BioHop wordmark (resources/brand/logo.svg); currentColor for the "Bio" part, so it reads on light and dark. --}}
<img src="{{ route('brand.logo') }}" alt="BioHop" {{ $attributes->merge(['class' => 'h-9 w-auto']) }}>
