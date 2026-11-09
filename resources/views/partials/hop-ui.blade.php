{{--
    The shared HOP dialogs and reminder cards (hop-assets v5: hop-ui.css, hop-ui.js), straight from the family
    manifest with their SRI hashes. Printed only when the manifest lists both, so nothing changes until the hub
    serves v5; resources/js/dialogs.js uses window.HopUI when it is there and the browser's dialogs when it is not.
--}}
@php
    $hopUiCss = \Hop\Ui\Assets::asset('ui_css');
    $hopUiJs = \Hop\Ui\Assets::asset('ui_js');
    $hopUiReady = is_array($hopUiCss) && is_array($hopUiJs)
        && filled($hopUiCss['url'] ?? null) && filled($hopUiJs['url'] ?? null) && filled($hopUiJs['integrity'] ?? null);
@endphp
@if ($hopUiReady)
    <link rel="stylesheet" href="{{ $hopUiCss['url'] }}"@if (filled($hopUiCss['integrity'] ?? null)) integrity="{{ $hopUiCss['integrity'] }}"@endif crossorigin="anonymous">
    <script src="{{ $hopUiJs['url'] }}" integrity="{{ $hopUiJs['integrity'] }}" crossorigin="anonymous" defer nonce="{{ Vite::cspNonce() }}"></script>
@endif
