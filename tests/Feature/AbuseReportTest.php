<?php

use App\Models\AbuseReport;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.turnstile.secret' => null, 'services.turnstile.site_key' => null]);
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

test('the report page is BioHop\'s own, with a way back to BioHop', function () {
    $html = $this->get('/report-abuse?slug=some-page')->assertOk()->getContent();

    expect($html)->toContain('Report a bio page')
        ->toContain('Back to BioHop')
        ->toContain('href="'.route('home').'"')
        ->toContain(public_url('b/some-page'))
        ->not->toContain('link.hel.ink/report-abuse');
});

test('an invalid slug is not echoed back into the form', function () {
    $html = $this->get('/report-abuse?slug=%3Cscript%3E')->assertOk()->getContent();

    expect($html)->not->toContain('<script>alert');
    expect($html)->not->toContain('%3Cscript');
});

test('a report is stored in the shared abuse_reports table', function () {
    $this->from('/report-abuse')->post('/report-abuse', [
        'slug' => 'some-page',
        'url' => 'https://hel.ink/b/some-page',
        'email' => 'reporter@example.com',
        'reason' => 'This page pretends to be my bank.',
    ])->assertRedirect('/report-abuse')->assertSessionHas('status');

    $report = AbuseReport::sole();
    expect($report->slug)->toBe('some-page')
        ->and($report->status)->toBe('open')
        ->and($report->email)->toBe('reporter@example.com')
        ->and($report->source)->toBe('biohop');
});

test('a report is still saved before the shared table has the source column', function () {
    Schema::table('abuse_reports', function ($table) {
        $table->dropIndex(['source']);
        $table->dropColumn('source');
    });

    $this->from('/report-abuse')->post('/report-abuse', [
        'slug' => 'some-page',
        'email' => 'reporter@example.com',
        'reason' => 'This page pretends to be my bank.',
    ])->assertRedirect('/report-abuse')->assertSessionHas('status');

    expect(AbuseReport::sole()->slug)->toBe('some-page');
});

test('the thank-you and refusal messages follow the visitor language', function () {
    $payload = ['email' => 'reporter@example.com', 'reason' => 'This page pretends to be my bank.'];

    $this->from('/report-abuse')->post('/report-abuse?lang=de', $payload)
        ->assertSessionHas('status', 'Danke. Deine Meldung wurde an die Moderatoren geschickt.');

    foreach (range(1, 2) as $i) {
        $this->from('/report-abuse')->post('/report-abuse?lang=de', $payload);
    }
    $this->from('/report-abuse')->post('/report-abuse?lang=de', $payload)
        ->assertSessionHasErrors(['reason' => 'Zu viele Meldungen aus deinem Netzwerk, bitte versuche es später noch einmal.']);
});

test('reports are validated', function () {
    $this->from('/report-abuse')->post('/report-abuse', [
        'slug' => '../etc',
        'email' => 'not-an-email',
        'reason' => 'short',
    ])->assertSessionHasErrors(['slug', 'email', 'reason']);

    expect(AbuseReport::count())->toBe(0);
});

test('one network can send at most three reports per half hour', function () {
    foreach (range(1, 4) as $i) {
        $this->from('/report-abuse')->post('/report-abuse', [
            'email' => "r{$i}@example.com",
            'reason' => 'Spam page number '.$i.' please check.',
        ]);
    }

    expect(AbuseReport::count())->toBe(3);
});
