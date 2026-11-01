<x-app-layout>
    <x-slot name="pageTitle">BioHop - {{ __('New bio page') }}</x-slot>
    {{-- Five steps that end in a finished page: name, goal, platforms, links, profile and colours, with the phone on the right. --}}
    <div class="bio-wizard" x-data="bioWizard()" x-init="init()">
        <div class="bw-progress" aria-hidden="true"><i :style="'width:' + Math.round(step / steps * 100) + '%'"></i></div>
        <div class="bw-grid">
            <div class="bw-main">
                {{-- 1 name --}}
                <section x-show="step === 1" class="bw-step">
                    <h1 class="bw-title">{{ __('Pick your address') }}</h1>
                    <p class="bw-lead">{{ __('Letters and numbers only. You can change it later.') }}</p>
                    <label class="bw-field"><span class="bw-prefix">{{ public_host() }}/b/</span><input type="text" x-model="slug" @input.debounce.400ms="checkSlug()" minlength="{{ $slugMin }}" maxlength="20" pattern="[a-zA-Z0-9]+" autocomplete="off" placeholder="{{ __('yourname') }}" aria-label="{{ __('Your address') }}"></label>
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
                    <div class="bw-platforms">
                        <template x-for="p in platforms" :key="p.id">
                            <div class="bw-platform" :class="{ on: picked[p.id] !== undefined }">
                                <button type="button" @click="togglePlatform(p.id)" :aria-pressed="String(picked[p.id] !== undefined)"><img :src="'/images/brands/' + p.id + '.svg?v=20251212'" alt="" width="28" height="28" :style="'background:' + p.color"><span x-text="p.name"></span></button>
                                <input x-show="picked[p.id] !== undefined" x-cloak type="text" :placeholder="p.placeholder" :aria-label="p.name" :value="picked[p.id] || ''" @input="picked[p.id] = $event.target.value">
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
                            <label class="bw-field"><span class="bw-prefix">🔗</span><input type="url" x-model="links[i]" :placeholder="'https://'" :aria-label="'{{ __('Link') }} ' + (i + 1)"></label>
                        </template>
                        <button type="button" class="bw-ghost" @click="links.push('')" x-show="links.length < 10">+ {{ __('Another link') }}</button>
                    </div>
                </section>
                {{-- 5 profile --}}
                <section x-show="step === 5" class="bw-step" x-cloak>
                    <h1 class="bw-title">{{ __('Add profile details') }}</h1>
                    <p class="bw-lead">{{ __('Your picture, name and a short bio.') }}</p>
                    <label class="bw-avatar">
                        <input type="file" accept="image/*" class="sr-only" @change="pickAvatar($event)">
                        <img x-show="avatarUrl" :src="avatarUrl" alt="">
                        <span x-show="!avatarUrl" aria-hidden="true">+</span>
                        <span class="sr-only">{{ __('Upload a profile picture') }}</span>
                    </label>
                    <label class="bw-field"><input type="text" x-model="title" maxlength="{{ \Hop\Bio\Models\BioPage::TITLE_MAX }}" placeholder="{{ __('Display name') }}" aria-label="{{ __('Display name') }}"></label>
                    <label class="bw-field"><span class="bw-prefix">{{ __('Content warning') }}</span><select name="content_warning" x-model="contentWarning" aria-label="{{ __('Content warning') }}">@foreach(['none' => 'None', 'sensitive' => 'Sensitive content', 'adult' => 'Adult 18+'] as $value => $label)<option value="{{ $value }}">{{ __($label) }}</option>@endforeach</select></label>
                    <label class="bw-field bw-area"><textarea x-model="bio" maxlength="{{ \Hop\Bio\Models\BioPage::BIO_MAX }}" rows="3" placeholder="{{ __('Bio') }}" aria-label="{{ __('Bio') }}"></textarea><small x-text="bio.length + '/{{ \Hop\Bio\Models\BioPage::BIO_MAX }}'"></small></label>
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
    </div>
    <script type="application/json" id="bio-wizard-config">{!! json_encode(['platforms' => $platforms, 'palettes' => $palettes, 'checkUrl' => route('bio.check-slug'), 'submitUrl' => route('bio.wizard'), 'goals' => [
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
            platforms: C.platforms, palettes: C.palettes, goals: C.goals,
            init() {},
            async checkSlug() {
                const s = this.slug.trim().toLowerCase();
                if (s.length < 3) { this.available = null; return; }
                this.checking = true;
                try { const r = await fetch(C.checkUrl + '?slug=' + encodeURIComponent(s), { headers: { Accept: 'application/json' } }); const d = await r.json(); this.available = !!d.available; this.slugMessage = d.message || ''; }
                catch (e) { this.available = null; }
                finally { this.checking = false; }
            },
            togglePlatform(id) { if (this.picked[id] !== undefined) { delete this.picked[id]; this.picked = { ...this.picked }; } else { this.picked = { ...this.picked, [id]: '' }; } },
            pickAvatar(e) { const f = e.target.files[0]; if (!f) return; this.avatarFile = f; this.avatarUrl = URL.createObjectURL(f); },
            canContinue() {
                if (this.step === 1) return this.slug.trim().length >= 3 && this.available !== false;
                if (this.step === 5) return this.title.trim().length > 0;
                return true;
            },
            wallpaper() { return this.palette === null ? '#1e1b4b' : this.palettes[this.palette][0]; },
            button() { return this.palette === null ? '#ffffff' : this.palettes[this.palette][1]; },
            buttonText() { return this.palette === null ? '#1e1b4b' : this.palettes[this.palette][2]; },
            linkTitle(u) { try { const h = new URL(u.startsWith('http') ? u : 'https://' + u).hostname.replace(/^(www|m)\./, ''); const p = this.platforms.find(p => p.placeholder && p.placeholder.includes(h.split('.')[0])); return p ? p.name : h.split('.')[0].charAt(0).toUpperCase() + h.split('.')[0].slice(1); } catch (e) { return u; } },
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
        .bw-grid { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(0, .8fr); gap: 40px; align-items: start; }
        .bw-step { max-width: 560px; }
        .bw-title { font-size: clamp(28px, 4vw, 40px); font-weight: 800; letter-spacing: -.03em; line-height: 1.05; margin: 0 0 10px; }
        .bw-lead { color: var(--home-muted, #64748b); margin: 0 0 22px; font-size: 16px; }
        .bw-field { display: flex; align-items: center; gap: 8px; padding: 6px 6px 6px 16px; border: 1.5px solid rgba(120,120,140,.3); border-radius: 16px; background: var(--home-panel, #fff); margin-bottom: 10px; }
        .bw-field select { flex: 1; border: 0; background: transparent; padding: 10px 6px; font: inherit; color: inherit; }
        .bw-field input, .bw-field textarea { flex: 1; min-width: 0; border: 0; background: transparent; padding: 10px 6px; font: inherit; color: inherit; outline: none; }
        .bw-field.bw-area { display: grid; grid-template-columns: 1fr auto; align-items: end; }
        .bw-field.bw-area small { font-size: 11px; color: var(--home-muted, #64748b); padding: 0 8px 8px 0; }
        .bw-prefix { color: var(--home-muted, #64748b); font: 13px ui-monospace, monospace; }
        .bw-hint { font-size: 13px; color: var(--home-muted, #64748b); margin: 4px 0 0; } .bw-hint.ok { color: #16a34a; } .bw-hint.bad { color: #e11d48; }
        .bw-cards { display: grid; gap: 12px; }
        .bw-card { display: grid; grid-template-columns: 1fr 72px; grid-template-rows: auto auto; gap: 2px 16px; align-items: center; text-align: left; padding: 18px 20px; border: 1.5px solid rgba(120,120,140,.3); border-radius: 20px; background: var(--home-panel, #fff); color: inherit; cursor: pointer; transition: border-color .2s, transform .2s; }
        .bw-card:hover { transform: translateY(-1px); } .bw-card.on { border-color: currentColor; }
        .bw-card b { font-size: 18px; } .bw-card small { color: var(--home-muted, #64748b); font-size: 14px; }
        .bw-card-art { grid-column: 2; grid-row: 1 / 3; width: 72px; height: 72px; border-radius: 16px; }
        .bw-platforms { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .bw-platform button { width: 100%; display: grid; justify-items: center; gap: 8px; padding: 18px 8px; border: 1.5px solid rgba(120,120,140,.3); border-radius: 18px; background: var(--home-panel, #fff); color: inherit; font-size: 13px; font-weight: 600; cursor: pointer; }
        .bw-platform.on button { border-color: currentColor; }
        .bw-platform button img { width: 44px; height: 44px; padding: 10px; border-radius: 12px; filter: brightness(0) invert(1); }
        .bw-platform input { width: 100%; margin-top: 6px; padding: 8px 10px; border: 1.5px solid rgba(120,120,140,.3); border-radius: 10px; background: var(--home-panel, #fff); color: inherit; font-size: 13px; }
        .bw-avatar { position: relative; display: grid; place-items: center; width: 120px; height: 120px; margin: 0 auto 18px; border-radius: 50%; background: rgba(120,120,140,.2); overflow: hidden; cursor: pointer; font-size: 36px; }
        .bw-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .bw-swatches { display: grid; grid-template-columns: repeat(5, 56px); gap: 16px; }
        .bw-swatch { width: 56px; height: 56px; border-radius: 50%; border: 3px solid transparent; background: linear-gradient(135deg, var(--w) 50%, var(--b) 50%); cursor: pointer; box-shadow: inset 0 0 0 1px rgba(0,0,0,.08); }
        .bw-swatch.on { border-color: currentColor; } .bw-swatch.theme { background: linear-gradient(135deg, #22d3ee, #a78bfa, #f472b6); color: #fff; font-size: 20px; }
        .bw-nav { display: flex; gap: 10px; margin-top: 28px; }
        .bw-next { flex: 1; padding: 14px 20px; border: 0; border-radius: 999px; background: #0b1020; color: #fff; font-weight: 700; font-size: 16px; cursor: pointer; }
        .bw-next:disabled { opacity: .4; cursor: not-allowed; }
        .bw-ghost { padding: 12px 18px; border: 1.5px solid rgba(120,120,140,.3); border-radius: 999px; background: transparent; color: inherit; font-weight: 600; cursor: pointer; }
        .bw-error { color: #e11d48; font-size: 14px; margin-top: 12px; }
        .bw-classic { margin-top: 18px; font-size: 13px; color: var(--home-muted, #64748b); } .bw-classic a { text-decoration: underline; }
        .bw-phone-wrap { position: sticky; top: 90px; display: grid; place-items: center; }
        .bw-phone { width: 270px; aspect-ratio: 9 / 18.5; padding: 10px; border-radius: 40px; background: #0b1020; box-shadow: 0 30px 60px -30px rgba(0,0,0,.5); }
        .bw-screen { display: grid; align-content: start; justify-items: center; gap: 8px; height: 100%; padding: 36px 16px; border-radius: 32px; background: var(--w); color: var(--b); text-align: center; transition: background .4s, color .4s; overflow: hidden; }
        .bw-av { width: 64px; height: 64px; border-radius: 50%; overflow: hidden; background: color-mix(in srgb, var(--b) 30%, transparent); }
        .bw-av img { width: 100%; height: 100%; object-fit: cover; } .bw-av i { display: block; width: 100%; height: 100%; }
        .bw-screen b { font-size: 16px; } .bw-screen small { font-size: 11px; opacity: .85; max-width: 200px; }
        .bw-socials { display: flex; gap: 8px; min-height: 18px; } .bw-socials img { width: 16px; height: 16px; filter: brightness(0) invert(1); opacity: .9; }
        .bw-pill { width: 100%; padding: 11px; border-radius: 999px; background: var(--b); color: var(--bt); font-size: 13px; font-weight: 600; }
        .bw-pill.ghost { background: color-mix(in srgb, var(--b) 35%, transparent); height: 40px; }
        @media (max-width: 860px) { .bw-grid { grid-template-columns: minmax(0, 1fr); } .bw-phone-wrap { position: static; order: -1; } .bw-phone { width: 200px; } .bw-swatches { grid-template-columns: repeat(4, 56px); } .bw-platforms { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    </style>
</x-app-layout>
