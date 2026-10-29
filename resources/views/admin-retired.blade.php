<x-app-layout>
    <x-slot name="pageTitle">{{ config('app.name') }} - {{ config('hop.control_name') }}</x-slot>

    <x-page-header title="The admin panel moved" subtitle="Moderation and settings now live in the HOP control center." />

    <section class="glass rounded-2xl p-5 space-y-3 text-sm text-muted">
        <p>
            Users, bio pages, abuse reports, and platform settings are managed in
            <strong class="text-ink">{{ config('hop.control_name') }}</strong>, which talks to this server through the signed HOP admin API.
        </p>
        <p>
            This installation has no control center address yet. Set <code>HOP_CONTROL_URL</code> in <code>.env</code> to send
            <code>/admin</code> there, or use the <code>/api/hop/*</code> API and the artisan commands directly.
        </p>
    </section>
</x-app-layout>
