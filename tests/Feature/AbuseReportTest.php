<?php

use App\Models\AbuseReport;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        ->and($report->email)->toBe('reporter@example.com');
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
