<?php

use App\Models\User;
use Hop\Bio\Models\BioLead;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;

function formPage(string $slug, array $settings = []): array
{
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => $slug, 'title' => 'Forms', 'is_published' => true]);
    $link = BioLink::create(['bio_page_id' => $page->id, 'type' => 'form', 'title' => 'Stay in touch', 'order' => 0, 'is_active' => true, 'settings' => $settings + ['ask_name' => true, 'ask_email' => true, 'ask_phone' => false]]);

    return [$user, $page, $link];
}

test('the form shows only the fields it asks for and a consent line', function () {
    [, , $link] = formPage('fshow', ['ask_phone' => true, 'ask_name' => false, 'button' => 'Join', 'consent' => 'I agree to be written to.']);
    $html = $this->get('/b/fshow')->assertOk()->getContent();
    expect($html)->toContain('name="email"')->toContain('name="phone"')->not->toContain('name="name"')->toContain('Join')->toContain('I agree to be written to.')->toContain('name="website"')->toContain(route('bio.public.form', $link->public_id));
});

test('an entry is stored once the visitor consents, and bad entries are refused', function () {
    [, $page, $link] = formPage('fsend');
    $url = route('bio.public.form', $link->public_id);
    $this->post($url, ['name' => 'Rina', 'email' => 'rina@example.com'])->assertSessionHasErrorsIn('form_'.$link->public_id, 'consent');
    $this->post($url, ['name' => 'Rina', 'email' => 'not-an-email', 'consent' => '1'])->assertSessionHasErrorsIn('form_'.$link->public_id, 'email');
    expect(BioLead::count())->toBe(0);
    $this->post($url, ['name' => 'Rina', 'email' => 'rina@example.com', 'consent' => '1'])->assertRedirect(route('bio.public.show', $page->slug).'#form-'.$link->public_id)->assertSessionHas('form_sent', $link->public_id);
    $lead = BioLead::firstOrFail();
    expect($lead->name)->toBe('Rina')->and($lead->email)->toBe('rina@example.com')->and($lead->phone)->toBeNull()->and($lead->getAttributes()['ip_hash'])->not->toContain('127.0.0.1');
    $this->get('/b/fsend')->assertSee('Thank you! Your details were sent.');
    // the honeypot looks like success but stores nothing
    $this->post($url, ['name' => 'Bot', 'email' => 'b@example.com', 'consent' => '1', 'website' => 'spam.example'])->assertSessionHas('form_sent');
    expect(BioLead::count())->toBe(1);
});

test('the owner downloads a safe CSV and can delete all entries', function () {
    [$user, $page, $link] = formPage('fcsv');
    BioLead::create(['bio_link_id' => $link->id, 'name' => '=HYPERLINK("http://x")', 'email' => 'a@example.com']);
    BioLead::create(['bio_link_id' => $link->id, 'name' => 'Budi', 'email' => 'b@example.com', 'phone' => '+62 812 000']);
    $this->get("/bio/fcsv/links/{$link->id}/leads.csv")->assertRedirect();
    $this->actingAs($user);
    $csv = $this->get("/bio/fcsv/links/{$link->id}/leads.csv")->assertOk()->streamedContent();
    expect($csv)->toContain('Name,Email,Phone')->toContain("'=HYPERLINK")->toContain('Budi,b@example.com')->not->toContain(',=HYPERLINK');
    $other = User::factory()->create();
    $this->actingAs($other)->get("/bio/fcsv/links/{$link->id}/leads.csv")->assertForbidden();
    $this->actingAs($user)->deleteJson("/bio/fcsv/links/{$link->id}/leads")->assertOk()->assertJson(['deleted' => 2]);
    expect(BioLead::count())->toBe(0);
});

test('old entries are pruned by the days the owner chose', function () {
    [, , $link] = formPage('fprune', ['retention_days' => 10]);
    $old = BioLead::create(['bio_link_id' => $link->id, 'email' => 'old@example.com']);
    $old->forceFill(['created_at' => now()->subDays(11)])->save();
    BioLead::create(['bio_link_id' => $link->id, 'email' => 'new@example.com']);
    $this->artisan('privacy:prune')->expectsOutputToContain('form entries: 1')->assertSuccessful();
    expect(BioLead::pluck('email')->all())->toBe(['new@example.com']);
});
