{{-- Cloudflare Turnstile for a form whose x-data includes turnstileForm(siteKey). --}}
@props(['size' => 'normal'])
@if (config('services.turnstile.site_key'))
    @once
        <script nonce="{{ Vite::cspNonce() }}" id="turnstile-script" src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>
    @endonce
    <div {{ $attributes }}>
        <div x-ref="captcha" data-size="{{ $size }}"></div>
        <p x-cloak x-show="status === 'error'" class="mt-2 text-sm text-rose-500" role="alert">
            Security check could not load.
            <button type="button" class="font-semibold underline" @click="retry()">Try again</button>
        </p>
        @error('turnstile')<p class="mt-2 text-sm text-rose-500">{{ $message }}</p>@enderror
        <input type="hidden" name="cf-turnstile-response" :value="token">
    </div>
@endif
