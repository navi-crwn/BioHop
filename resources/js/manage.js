// The Manage page: one row per product or goal block, saved on its own (PATCH). The server refuses a block that changed
// since the page was opened (409), so a row never overwrites an editor save.
export default function manageRow(cfg) {
    const snapshot = (f) => JSON.stringify(f);
    return {
        f: { ...cfg.fields },
        base: snapshot(cfg.fields),
        version: cfg.version,
        busy: false,
        msg: '',
        state: '',
        get dirty() { return snapshot(this.f) !== this.base; },
        async save() {
            if (this.busy || !this.dirty) return;
            this.busy = true; this.msg = cfg.text.saving; this.state = '';
            const settings = {};
            for (const k of cfg.settingKeys) settings[k] = this.f[k] === '' ? null : this.f[k];
            const body = { base_version: this.version, settings };
            if (cfg.type === 'product') { body.title = this.f.title; body.is_active = !!this.f.is_active; settings.sold_out = !!this.f.sold_out; }
            try {
                const r = await window.axios.patch(cfg.url, body, { headers: { Accept: 'application/json' } });
                this.version = r.data.version; this.base = snapshot(this.f);
                this.msg = cfg.text.saved; this.state = 'ok';
            } catch (e) {
                const d = e.response?.data;
                this.state = e.response?.status === 409 ? 'conflict' : 'error';
                this.msg = d?.message && e.response?.status !== 422 ? d.message : (d?.errors ? Object.values(d.errors)[0][0] : (d?.message || cfg.text.failed));
            } finally { this.busy = false; }
        },
    };
}
