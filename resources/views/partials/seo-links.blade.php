{{-- Canonical, og:url and the language alternates of one of BioHop's own public pages (App\Support\Seo). --}}
@php($seoCanonical = \App\Support\Seo::canonical(request()))
    <link rel="canonical" href="{{ $seoCanonical }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
@foreach (\App\Support\Seo::alternates(request()->path()) as $seoLang => $seoHref)
    <link rel="alternate" hreflang="{{ $seoLang }}" href="{{ $seoHref }}">
@endforeach
