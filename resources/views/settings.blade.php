<x-app-layout>
    <x-slot name="pageTitle">{{ config('app.name') }} - Settings</x-slot>
    <x-page-header title="Settings" subtitle="Your account (profile, password, two-factor, API tokens) lives at HOP ID; here is what belongs to BioHop." />
    <x-flash />
    <div class="grid gap-4 md:grid-cols-2">
        <section class="glass rounded-2xl p-5 space-y-3">
            <h2 class="font-semibold text-ink">HOP ID</h2>
            <p class="text-sm text-muted">One account for every HOP app. Change your name, email, password, avatar and two-factor settings at the hub.</p>
            <a href="{{ \App\Models\User::hubUrl('settings') }}" class="btn-soft btn-sm">Open HOP ID settings</a>
        </section>
        <section id="export" class="glass rounded-2xl p-5 space-y-3">
            <h2 class="font-semibold text-ink">Export my bio pages</h2>
            <p class="text-sm text-muted">A JSON file with every bio page, block and setting of yours. Uploaded images are referenced by URL.</p>
            <a href="{{ route('settings.export') }}" class="btn-primary btn-sm">Download JSON</a>
        </section>
        <section class="glass rounded-2xl p-5 space-y-3 md:col-span-2">
            <h2 class="font-semibold text-ink">Short links in blocks</h2>
            <p class="text-sm text-muted">"Shorten this URL" in the editor asks LinkHop ({{ config('hop.apps.linkhop.url') }}) to make a <code>{{ public_host() }}/l/…</code> link with a service token named "{{ \App\Bio\LinkHopShortlinks::TOKEN_NAME }}" that BioHop creates for you on first use. You can revoke it at HOP ID → API tokens; it is made again the next time you need it. When LinkHop cannot be reached, the block keeps the original URL.</p>
        </section>
    </div>
</x-app-layout>
