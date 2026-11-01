<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('PIN required') }} · {{ $bioLink->title }}</title>
    <meta name="robots" content="noindex, nofollow">
    @if(! empty($unlocked))
        <meta http-equiv="refresh" content="0;url={{ route('bio.public.click', $bioLink->public_id) }}">
    @endif
    <link rel="icon" href="{{ route('brand.favicon') }}">
    <x-hop::fonts />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%); color: #fff; }
        .card { width: 100%; max-width: 380px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); border-radius: 24px; padding: 2rem; text-align: center; }
        .lock { width: 64px; height: 64px; margin: 0 auto 1rem; border-radius: 50%; display: grid; place-items: center; background: rgba(251,191,36,.15); border: 1px solid rgba(251,191,36,.3); color: #fbbf24; }
        h1 { font-size: 1.25rem; margin-bottom: .35rem; }
        p { color: #94a3b8; font-size: .9rem; }
        .title { display: inline-block; margin-top: .75rem; padding: .4rem .9rem; border-radius: 8px; background: rgba(255,255,255,.06); color: #e2e8f0; font-size: .9rem; }
        form { margin-top: 1.25rem; display: grid; gap: .75rem; }
        input { width: 100%; padding: .85rem 1rem; border-radius: 12px; border: 1px solid rgba(100,116,139,.4); background: rgba(30,41,59,.8); color: #fff; font-size: 1.25rem; letter-spacing: .3em; text-align: center; outline: none; }
        input:focus { border-color: #818cf8; }
        button { padding: .85rem; border: 0; border-radius: 12px; background: #6366f1; color: #fff; font-weight: 600; font-size: 1rem; cursor: pointer; }
        .err { color: #fca5a5; font-size: .85rem; }
        a.back { display: inline-block; margin-top: 1rem; color: #94a3b8; font-size: .85rem; }
    </style>
</head>
<body>
    <main class="card">
        <div class="lock" aria-hidden="true"><svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg></div>
        <h1>{{ __('PIN required') }}</h1>
        <p>{{ __('The owner protected this link with a PIN.') }}</p>
        <span class="title">{{ $bioLink->title }}</span>
        @if(! empty($unlocked))
            <form method="GET" action="{{ route('bio.public.click', $bioLink->public_id) }}">
                <p>{{ __('PIN accepted.') }}</p>
                <button type="submit" autofocus>{{ __('Continue') }}</button>
            </form>
        @else
        <form method="POST" action="{{ route('bio.public.click.pin', $bioLink->public_id) }}">
            @csrf
            <input type="text" name="pin" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{4,8}" maxlength="8" required autofocus aria-label="{{ __('PIN') }}" placeholder="••••">
            @error('pin')<span class="err">{{ $message }}</span>@enderror
            <button type="submit">{{ __('Open link') }}</button>
        </form>
        @endif
        <a class="back" href="{{ route('bio.public.show', $bioLink->bioPage->slug) }}">← {{ __('Back to the page') }}</a>
    </main>
</body>
</html>
