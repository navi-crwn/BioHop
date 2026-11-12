<x-app-layout>
    <x-slot name="pageTitle">BioHop - {{ __('New bio page') }}</x-slot>
    {{-- Five steps that end in a finished page: name, goal, platforms, links, profile and colours, with the phone on the right. --}}
    <div class="bio-wizard" x-data="bioWizard()" x-init="init()">
        @if($atLimit)
        <div class="bw-limit" role="status">
            <h1 class="bw-title">{{ __('Your pages are full') }}</h1>
            <p class="bw-lead">{{ __('You are using all :max of your bio pages. Delete one you no longer need to make room for a new page.', ['max' => $max]) }}</p>
            <a href="{{ route('bio.index') }}" class="btn-primary btn-sm">{{ __('Go to my pages') }}</a>
        </div>
        @else
        <div class="bw-progress" aria-hidden="true"><i :style="'width:' + Math.round(step / steps * 100) + '%'"></i></div>
        <div class="bw-grid">
            <div class="bw-main">
                {{-- 1 name --}}
                <section x-show="step === 1" class="bw-step">
                    <h1 class="bw-title">{{ __('Pick your address') }}</h1>
                    <p class="bw-lead">{{ __('Letters and numbers only. You can change it later.') }}</p>
                    <label class="bw-field hop-field-box"><span class="bw-prefix">{{ public_host() }}/b/</span><input type="text" x-model="slug" @input.debounce.400ms="checkSlug()" minlength="{{ $slugMin }}" maxlength="20" pattern="[a-zA-Z0-9]+" autocomplete="off" placeholder="{{ __('yourname') }}" aria-label="{{ __('Your address') }}"></label>
                    <p class="bw-hint" x-show="checking">{{ __('Checking…') }}</p>
                    <p class="bw-hint ok" x-show="!checking && available === true" x-cloak>✓ {{ __('Available') }}</p>
                    <p class="bw-hint bad" x-show="!checking && available === false" x-cloak x-text="'✗ ' + (slugMessage || '{{ __('Already taken') }}')"></p>
                    <p class="bw-hint"><a href="{{ route('bio.import') }}">{{ __('Coming from Linktree? Import your page.') }}</a></p>
                </section>
                {{-- 2 goal --}}
                <section x-show="step === 2" class="bw-step" x-cloak>
                    <h1 class="bw-title">{{ __('What is this page for?') }}</h1>
                    <p class="bw-lead">{{ __('This picks a starting look. Everything can be changed.') }}</p>
                    <div class="bw-cards">
                        <template x-for="g in goals" :key="g.id">
                            <button type="button" class="bw-card" :class="{ on: goal === g.id }" @click="goal = g.id" :aria-pressed="String(goal === g.id)">
                                <span class="bw-card-art" :style="'background:' + g.color"></span>
                                <b x-text="g.title"></b><small x-text="g.text"></small>
                            </button>
                        </template>
                    </div>
                </section>
                {{-- 3 platforms --}}
                <section x-show="step === 3" class="bw-step" x-cloak>
                    <h1 class="bw-title">{{ __('Which platforms are you on?') }}</h1>
                    <p class="bw-lead">{{ __('Tap the ones you use, then add your handle. More can be added later.') }}</p>
                    <p class="bw-hint bw-count" aria-live="polite"><span x-text="Object.keys(picked).length + '/' + maxSocials"></span> · {{ __('Up to :max icons fit on a page; more platforms can become link buttons in the editor.', ['max' => \Hop\Bio\Models\BioPage::SOCIAL_MAX]) }}</p>
                    <div class="bw-platforms">
                        <template x-for="p in platforms" :key="p.id">
                            <div class="bw-platform" :class="{ on: picked[p.id] !== undefined }">
                                <button type="button" @click="togglePlatform(p.id)" :aria-pressed="String(picked[p.id] !== undefined)" :disabled="picked[p.id] === undefined && Object.keys(picked).length >= maxSocials"><span class="bw-platform-ic" :style="'background:' + p.color"><img :src="'/images/brands/' + p.id + '.svg?v=20251212'" alt="" width="24" height="24"></span><span x-text="p.name"></span></button>
                                <input class="hop-input" x-show="picked[p.id] !== undefined" x-cloak type="text" :placeholder="p.placeholder" :aria-label="p.name" :value="picked[p.id] || ''" @input="picked[p.id] = $event.target.value">
                            </div>
                        </template>
                    </div>
                </section>
                {{-- 4 links --}}
                <section x-show="step === 4" class="bw-step" x-cloak>
                    <h1 class="bw-title">{{ __('Add your links') }}</h1>
                    <p class="bw-lead">{{ __('Paste an address; the name and icon are filled in for you.') }}</p>
                    <div class="bw-links">
                        <template x-for="(l, i) in links" :key="i">
                            <label class="bw-field hop-field-box"><span class="bw-prefix">🔗</span><input type="url" x-model="links[i]" :placeholder="'https://'" :aria-label="'{{ __('Link') }} ' + (i + 1)"></label>
                        </template>
                        <button type="button" class="bw-ghost" @click="links.push('')" x-show="links.length < 10">+ {{ __('Another link') }}</button>
                    </div>
                </section>
                {{-- 5 profile --}}
                <section x-show="step === 5" class="bw-step" x-cloak>
                    <h1 class="bw-title">{{ __('Add profile details') }}</h1>
                    <p class="bw-lead">{{ __('Your picture, name and a short bio.') }}</p>
                    <label class="bw-avatar">
                        <input type="file" accept="image/png,image/jpeg,image/gif,image/webp,image/bmp" class="sr-only" @change="pickAvatar($event)">
                        <img x-show="avatarUrl" :src="avatarUrl" alt="">
                        <span x-show="!avatarUrl" aria-hidden="true">+</span>
                        <span class="sr-only">{{ __('Upload a profile picture') }}</span>
                    </label>
                    <label class="bw-field hop-field-box"><input type="text" x-model="title" maxlength="{{ \Hop\Bio\Models\BioPage::TITLE_MAX }}" placeholder="{{ __('Display name') }}" aria-label="{{ __('Display name') }}"></label>
                    <label class="bw-field hop-field-box"><span class="bw-prefix">{{ __('Content warning') }}</span><select name="content_warning" x-model="contentWarning" aria-label="{{ __('Content warning') }}">@foreach(['none' => 'None', 'sensitive' => 'Sensitive content', 'adult' => 'Adult 18+'] as $value => $label)<option value="{{ $value }}">{{ __($label) }}</option>@endforeach</select></label>
                    <label class="bw-field bw-area hop-field-box"><textarea x-model="bio" maxlength="{{ \Hop\Bio\Models\BioPage::BIO_MAX }}" rows="3" placeholder="{{ __('Bio') }}" aria-label="{{ __('Bio') }}"></textarea><small x-text="bio.length + '/{{ \Hop\Bio\Models\BioPage::BIO_MAX }}'"></small></label>
                </section>
                {{-- 6 colours --}}
                <section x-show="step === 6" class="bw-step" x-cloak>
                    <h1 class="bw-title">{{ __('Pick your colours') }}</h1>
                    <p class="bw-lead">{{ __('Wallpaper and buttons. Themes, fonts and more wait in the editor.') }}</p>
                    <div class="bw-swatches" role="radiogroup" aria-label="{{ __('Colours') }}">
                        <template x-for="(p, i) in palettes" :key="i">
                            <button type="button" role="radio" :aria-checked="String(palette === i)" class="bw-swatch" :class="{ on: palette === i }" @click="palette = i" :style="'--w:' + p[0] + ';--b:' + p[1]" :aria-label="'{{ __('Colours') }} ' + (i + 1)"></button>
                        </template>
                        <button type="button" role="radio" :aria-checked="String(palette === null)" class="bw-swatch theme" :class="{ on: palette === null }" @click="palette = null" aria-label="{{ __('Theme by goal') }}">✦</button>
                    </div>
                </section>
                <p class="bw-error" x-show="error" x-text="error" x-cloak role="alert"></p>
                <p class="bw-hint bw-blocker" x-show="step < steps && !canContinue()" x-cloak x-text="blocker()"></p>
                <div class="bw-nav">
                    <button type="button" class="bw-ghost" x-show="step > 1" @click="step--">{{ __('Back') }}</button>
                    <button type="button" class="bw-next" x-show="step < steps" :disabled="!canContinue()" @click="step++">{{ __('Continue') }}</button>
                    <button type="button" class="bw-next" x-show="step === steps" :disabled="busy" @click="submit()" x-text="busy ? '{{ __('Creating…') }}' : '{{ __('Create my page') }}'"></button>
                </div>
                <p class="bw-classic"><a href="{{ route('bio.create', ['classic' => 1]) }}">{{ __('Prefer the plain form?') }}</a></p>
            </div>
            {{-- the phone --}}
            <aside class="bw-phone-wrap" aria-hidden="true">
                <div class="bw-phone" :style="'--w:' + wallpaper() + ';--b:' + button() + ';--bt:' + buttonText()">
                    <div class="bw-screen">
                        <span class="bw-av"><img x-show="avatarUrl" :src="avatarUrl" alt=""><i x-show="!avatarUrl"></i></span>
                        <b x-text="title || slug || '{{ __('Your name') }}'"></b>
                        <small x-text="bio"></small>
                        <div class="bw-socials"><template x-for="id in Object.keys(picked)" :key="id"><img :src="'/images/brands/' + id + '.svg?v=20251212'" alt=""></template></div>
                        <template x-for="(l, i) in links.filter(x => x.trim())" :key="'l' + i"><span class="bw-pill" x-text="linkTitle(l)"></span></template>
                        <span class="bw-pill ghost" x-show="!links.some(x => x.trim())"></span>
                        <span class="bw-pill ghost" x-show="!links.some(x => x.trim())"></span>
                    </div>
                </div>
            </aside>
        </div>
        @endif
    </div>
    <script type="application/json" id="bio-wizard-config">{!! json_encode(['platforms' => $platforms, 'palettes' => $palettes, 'checkUrl' => route('bio.check-slug'), 'submitUrl' => route('bio.wizard'), 'maxSocials' => \Hop\Bio\Models\BioPage::SOCIAL_MAX, 'goals' => [
        ['id' => 'creator', 'title' => __('Creator'), 'text' => __('Grow a following and share what you make.'), 'color' => 'linear-gradient(135deg,#d9f99d,#d946ef)'],
        ['id' => 'business', 'title' => __('Business'), 'text' => __('Reach customers and sell.'), 'color' => 'linear-gradient(135deg,#4c1d95,#c4b5fd)'],
        ['id' => 'personal', 'title' => __('Personal'), 'text' => __('Share links with friends.'), 'color' => 'linear-gradient(135deg,#fb7185,#1d4ed8)'],
    ]], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @push('scripts')
    <script nonce="{{ Vite::cspNonce() }}">
    function bioWizard() {
        const C = JSON.parse(document.getElementById('bio-wizard-config').textContent);
        return {
            step: 1, steps: 6, slug: '', checking: false, available: null, slugMessage: '', goal: null, picked: {}, links: ['', '', ''],
            title: '', bio: '', contentWarning: 'none', avatarFile: null, avatarUrl: '', palette: 0, busy: false, error: '',
            platforms: C.platforms, palettes: C.palettes, goals: C.goals, maxSocials: C.maxSocials,
            init() {},
            async checkSlug() {
                const s = this.slug.trim().toLowerCase();
                if (s.length < 3) { this.available = null; return; }
                this.checking = true;
                try { const r = await fetch(C.checkUrl + '?slug=' + encodeURIComponent(s), { headers: { Accept: 'application/json' } }); const d = await r.json(); this.available = !!d.available; this.slugMessage = d.message || ''; }
                catch (e) { this.available = null; }
                finally { this.checking = false; }
            },
            togglePlatform(id) { if (this.picked[id] !== undefined) { delete this.picked[id]; this.picked = { ...this.picked }; } else if (Object.keys(this.picked).length < this.maxSocials) { this.picked = { ...this.picked, [id]: '' }; } },
            pickAvatar(e) { const f = e.target.files[0]; if (!f) return; if (f.type === 'image/svg+xml' || /\.svgz?$/i.test(f.name)) { e.target.value = ''; this.error = @js(__('SVG cannot be used here. Use a PNG, JPG, WebP or GIF picture.')); return; } this.error = ''; this.avatarFile = f; this.avatarUrl = URL.createObjectURL(f); },
            blocker() { return this.step === 1 ? '{{ __('Choose a free address to continue.') }}' : this.step === 5 ? '{{ __('Add a display name to continue.') }}' : ''; },
            canContinue() {
                if (this.step === 1) return this.slug.trim().length >= 3 && this.available !== false;
                if (this.step === 5) return this.title.trim().length > 0;
                return true;
            },
            wallpaper() { return this.palette === null ? '#1e1b4b' : this.palettes[this.palette][0]; },
            button() { return this.palette === null ? '#ffffff' : this.palettes[this.palette][1]; },
            buttonText() { return this.palette === null ? '#1e1b4b' : this.palettes[this.palette][2]; },
            linkTitle(u) {
                try {
                    const h = new URL(u.startsWith('http') ? u : 'https://' + u).hostname.toLowerCase().replace(/^(www|m)\./, '');
                    const p = this.platforms.find(p => p.placeholder && p.placeholder.includes(h.split('.')[0]));
                    if (p) return p.name;
                    const parts = h.split('.').filter(Boolean); parts.pop();
                    if (parts.length > 1 && ['co', 'com', 'net', 'org', 'ac', 'or', 'go', 'gov', 'edu'].includes(parts[parts.length - 1])) parts.pop();
                    let label = parts[0] || 'Link';
                    if (parts.length > 1 && ['shop', 'store', 'blog', 'app', 'link', 'go', 'web', 'my', 'pages', 'site'].includes(label)) label = parts[1];
                    return label.replace(/[-_]+/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                } catch (e) { return u; }
            },
            async submit() {
                this.busy = true; this.error = '';
                const fd = new FormData();
                fd.append('slug', this.slug.trim().toLowerCase()); fd.append('title', this.title.trim()); fd.append('bio', this.bio.trim()); fd.append('content_warning', this.contentWarning);
                if (this.goal) fd.append('goal', this.goal);
                if (this.palette !== null) fd.append('palette', this.palette);
                Object.entries(this.picked).forEach(([platform, value], i) => { fd.append(`platforms[${i}][platform]`, platform); fd.append(`platforms[${i}][value]`, value || ''); });
                this.links.filter(l => l.trim()).forEach((l, i) => fd.append(`links[${i}]`, l.trim()));
                if (this.avatarFile) fd.append('avatar', this.avatarFile);
                try {
                    const r = await fetch(C.submitUrl, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' } });
                    const d = await r.json();
                    if (!r.ok) { this.error = d.message || Object.values(d.errors || {}).flat().join(' '); this.busy = false; return; }
                    window.location.href = d.redirect;
                } catch (e) { this.error = '{{ __('Something went wrong. Try again.') }}'; this.busy = false; }
            }
        };
    }
    </script>
    @endpush
    <style>
        .bio-wizard { max-width: 1040px; margin: 0 auto; padding: 8px 0 40px; }
        .bw-progress { height: 6px; width: 240px; margin: 0 auto 28px; border-radius: 99px; background: rgba(120,120,140,.2); overflow: hidden; }
        .bw-progress i { display: block; height: 100%; background: linear-gradient(90deg, #a855f7, #ec4899); transition: width .4s; }
        .bw-limit { max-width: 520px; margin: 12vh auto 0; text-align: center; display: grid; gap: 14px; justify-items: center; }
        .bw-grid { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(0, .8fr); gap: 40px; align-items: start; }
        .bw-step { max-width: 560px; }
        .bw-title { font-size: clamp(28px, 4vw, 40px); font-weight: 800; letter-spacing: -.03em; line-height: 1.05; margin: 0 0 10px; }
        /* Surfaces follow the dashboard tokens (--glass, --ink, --muted) so the dark theme reads too. The old --home-*
           variables belong to the homepage and are never set in the dashboard layout: the fallback was a white box
           under light text. */
        .bio-wizard { --bw-panel: rgb(var(--glass, 255 255 255) / 0.75); --bw-muted: rgb(var(--muted, 100 116 139)); --bw-line: rgb(var(--ink, 15 23 42) / 0.14); color: rgb(var(--ink, 15 23 42));
            /* the standard's fields and buttons (controls.css) take the wizard's surface and line */
            --hop-surface: var(--bw-panel); --hop-line: var(--bw-line); --hop-text: rgb(var(--ink, 15 23 42)); }
        .dark .bio-wizard { --bw-panel: rgb(var(--glass, 22 27 48) / 0.7); --bw-line: rgb(255 255 255 / 0.14); }
        .bw-lead { color: var(--bw-muted); margin: 0 0 22px; font-size: 16px; }
        .bw-field { margin-bottom: 10px; }
        .bw-field select option { color: #0f172a; }
        .bw-field input::placeholder, .bw-field textarea::placeholder, .bw-platform input::placeholder { color: var(--bw-muted); opacity: .8; }
        .bw-field.bw-area { display: grid; grid-template-columns: 1fr auto; align-items: end; max-width: var(--hop-field-max-wide); }
        .bw-field.bw-area small { font-size: 11px; color: var(--bw-muted); padding: 0 8px 8px 0; font-variant-numeric: tabular-nums; }
        .bw-prefix { color: var(--bw-muted); font: 13px 'JetBrains Mono', ui-monospace, monospace; }
        .bw-hint { font-size: 13px; color: var(--bw-muted); margin: 4px 0 0; } .bw-hint.ok { color: #16a34a; } .bw-hint.bad { color: #e11d48; }
        .dark .bw-hint.ok { color: #4ade80; } .dark .bw-hint.bad { color: #fb7185; }
        .bw-count { margin: -12px 0 16px; font-variant-numeric: tabular-nums; }
        .bw-cards { display: grid; gap: 12px; }
        .bw-card { display: grid; grid-template-columns: 1fr 72px; grid-template-rows: auto auto; gap: 2px 16px; align-items: center; text-align: left; padding: 18px 20px; border: 1px solid var(--bw-line); border-radius: var(--hop-card-radius); background: var(--bw-panel); color: inherit; cursor: pointer; transition: border-color .15s, box-shadow .15s; }
        .bw-card:hover { border-color: var(--bw-muted); } .bw-card:active { transform: scale(.985); }
        .bw-card.on { border-color: currentColor; box-shadow: 0 0 0 1px currentColor inset; }
        .bw-card b { font-size: 18px; } .bw-card small { color: var(--bw-muted); font-size: 14px; }
        .bw-card-art { grid-column: 2; grid-row: 1 / 3; width: 72px; height: 72px; border-radius: 10px; }
        .bw-platforms { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .bw-platform button { width: 100%; display: grid; justify-items: center; gap: 8px; padding: 18px 8px; border: 1px solid var(--bw-line); border-radius: var(--hop-card-radius); background: var(--bw-panel); color: inherit; font-size: 13px; font-weight: 600; cursor: pointer; transition: border-color .15s, box-shadow .15s; }
        .bw-platform button:active { transform: scale(.97); }
        .bw-platform button:disabled { opacity: .45; cursor: not-allowed; }
        .bw-platform.on button { border-color: currentColor; box-shadow: 0 0 0 1px currentColor inset; }
        /* The brand colour sits on a wrapper and the white filter only touches the glyph; on the <img> itself the filter whitened the colour too, which left blank squares. */
        .bw-platform-ic { display: grid; place-items: center; width: 44px; height: 44px; border-radius: 10px; }
        .bw-platform-ic img { width: 24px; height: 24px; filter: brightness(0) invert(1); }
        .bw-platform input { width: 100%; margin-top: 6px; }
        .bw-avatar { position: relative; display: grid; place-items: center; width: 120px; height: 120px; margin: 0 auto 18px; border-radius: 50%; background: rgba(120,120,140,.2); overflow: hidden; cursor: pointer; font-size: 36px; }
        .bw-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .bw-swatches { display: grid; grid-template-columns: repeat(5, 56px); gap: 16px; }
        .bw-swatch { width: 56px; height: 56px; border-radius: 50%; border: 3px solid transparent; background: linear-gradient(135deg, var(--w) 50%, var(--b) 50%); cursor: pointer; box-shadow: inset 0 0 0 1px rgba(0,0,0,.08); }
        .bw-swatch.on { border-color: currentColor; } .bw-swatch.theme { background: linear-gradient(135deg, #22d3ee, #a78bfa, #f472b6); color: #fff; font-size: 20px; }
        .bw-nav { display: flex; align-items: center; gap: 8px; margin-top: 28px; }
        /* Back, Continue and "+ Another link": the standard button, large (the page's main action), as wide as its text. */
        .bw-next, .bw-ghost { box-sizing: border-box; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; height: var(--hop-ctl-h-lg); padding: 0 16px; border-radius: var(--hop-ctl-radius); font-size: var(--hop-ctl-font); font-weight: 600; white-space: nowrap; cursor: pointer; transition: background-color .15s, border-color .15s; }
        .bw-next { border: 1px solid transparent; background: #0b1020; color: #fff; }
        .bw-next:disabled { opacity: .64; cursor: not-allowed; }
        .bw-ghost { border: 1px solid var(--bw-line); background: transparent; color: inherit; }
        .bw-ghost:hover { background: rgb(var(--muted, 100 116 139) / 0.12); }
        .bw-ghost:active, .bw-next:active { transform: scale(.97); }
        .dark .bw-next { background: #f1f5f9; color: #0b1020; }
        .bw-error { color: #e11d48; font-size: 14px; margin-top: 12px; } .dark .bw-error { color: #fb7185; }
        .bw-classic { margin-top: 18px; font-size: 13px; color: var(--bw-muted); } .bw-classic a { text-decoration: underline; }
        .bw-phone-wrap { position: sticky; top: 90px; display: grid; place-items: center; }
        .bw-phone { width: 270px; aspect-ratio: 9 / 18.5; padding: 10px; border-radius: 40px; background: #0b1020; box-shadow: 0 30px 60px -30px rgba(0,0,0,.5); }
        .bw-screen { display: grid; align-content: start; justify-items: center; gap: 8px; height: 100%; padding: 36px 16px; border-radius: 32px; background: var(--w); color: var(--b); text-align: center; transition: background .4s, color .4s; overflow: hidden; }
        .bw-av { width: 64px; height: 64px; border-radius: 50%; overflow: hidden; background: color-mix(in srgb, var(--b) 30%, transparent); }
        .bw-av img { width: 100%; height: 100%; object-fit: cover; } .bw-av i { display: block; width: 100%; height: 100%; }
        .bw-screen b { font-size: 16px; } .bw-screen small { font-size: 11px; opacity: .85; max-width: 200px; }
        .bw-socials { display: flex; gap: 8px; min-height: 18px; } .bw-socials img { width: 16px; height: 16px; filter: brightness(0) invert(1); opacity: .9; }
        .bw-pill { width: 100%; padding: 11px; border-radius: 999px; background: var(--b); color: var(--bt); font-size: 13px; font-weight: 600; }
        .bw-pill.ghost { background: color-mix(in srgb, var(--b) 35%, transparent); height: 40px; }
        @media (pointer: coarse), (max-width: 639px) { .bw-next, .bw-ghost { min-height: var(--hop-hit); } .bw-nav .bw-next { margin-left: auto; } }
        @media (prefers-reduced-motion: reduce) { .bio-wizard * { transition: none !important; } .bw-card:active, .bw-platform button:active, .bw-ghost:active, .bw-next:active { transform: none; } }
        @media (max-width: 860px) { .bw-grid { grid-template-columns: minmax(0, 1fr); } .bw-phone-wrap { position: static; order: -1; } .bw-phone { width: 200px; } .bw-swatches { grid-template-columns: repeat(4, 56px); } .bw-platforms { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    </style>
</x-app-layout>
