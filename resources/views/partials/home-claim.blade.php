<div x-data="{
    slug: '', status: null, message: '', loading: false, request: 0,
    async check() {
        const clean = this.slug.trim().toLowerCase(), request = ++this.request;
        if (clean.length < 3) { this.status = null; this.message = ''; this.loading = false; return; }
        this.loading = true;
        try {
            const res = await fetch('/check-slug-public?slug=' + encodeURIComponent(clean));
            if (!res.ok) throw new Error('Unavailable');
            const data = await res.json();
            if (request !== this.request) return;
            this.status = data.available ? 'available' : 'unavailable'; this.message = data.message;
        } catch (e) { if (request === this.request) { this.status = null; this.message = ''; } }
        finally { if (request === this.request) this.loading = false; }
    }
}" class="bio-claim">
    <form action="{{ route('register') }}" @submit.prevent="if (slug.trim().length >= 3) window.location.href = @js(route('register'))">
        <label class="bio-claim-field" dir="ltr"><span>hel.ink/b/</span><input type="text" x-model="slug" @input.debounce.300ms="check()" placeholder="{{ __('yourname') }}" aria-label="{{ __('Your bio name') }}" required minlength="3" maxlength="30"><span x-cloak x-show="loading" aria-hidden="true">…</span></label>
        <button type="submit" class="showcase-button primary">{{ __('Claim my page') }} <span aria-hidden="true">↗</span></button>
    </form>
    <p x-cloak x-show="message" x-text="message" :class="status === 'available' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'" aria-live="polite"></p>
    <p>{{ __('Free forever with your HOP ID · No ads, no trackers') }}</p>
</div>
