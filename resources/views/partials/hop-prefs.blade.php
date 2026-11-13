{{-- The family theme before first paint (cookie hop_theme, then the old localStorage key, then the system). One file for
     every head that has a theme toggle: resources/js/hop-prefs.js, printed inline so nothing is fetched before paint. --}}
<script nonce="{{ Vite::cspNonce() }}">{!! file_get_contents(resource_path('js/hop-prefs.js')) !!}</script>
