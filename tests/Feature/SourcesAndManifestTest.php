<?php

use App\Models\User;
use Hop\Bio\Models\BioClick;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Illuminate\Support\Facades\Storage;

function srcPage(string $slug, array $extra = []): array
{
    $user = User::factory()->create();
    $page = BioPage::create($extra + ['user_id' => $user->id, 'slug' => $slug, 'title' => 'Helena Studio', 'is_published' => true]);
    $link = BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Site', 'url' => 'https://example.com/', 'order' => 0, 'is_active' => true]);

    return [$user, $page, $link];
}

test('a visit from a QR code, an NFC tag or the home-screen app tags its clicks, and a plain visit does not', function () {
    [, $page, $link] = srcPage('srcp');
    $plain = $this->get('/b/srcp')->assertOk()->getContent();
    expect($plain)->toContain('/b/click/'.$link->public_id)->not->toContain('s=qr')->not->toContain('s=nfc');
    foreach (['qr', 'nfc', 'pwa'] as $src) {
        $html = $this->get("/b/srcp?src={$src}")->assertOk()->getContent();
        expect($html)->toContain('/b/click/'.$link->public_id.'?s='.$src);
    }
    expect($this->get('/b/srcp?src=evil')->getContent())->not->toContain('?s=evil');
    // the click records the source (the counting runs after the response in production; here it is immediate)
    $this->get(route('bio.public.click', ['bioLink' => $link->public_id, 's' => 'nfc']))->assertRedirect('https://example.com/');
    $this->get(route('bio.public.click', ['bioLink' => $link->public_id, 's' => 'bogus']));
    expect(BioClick::where('source', 'nfc')->count())->toBe(1)->and(BioClick::whereNull('source')->count())->toBeLessThanOrEqual(1);
});

test('the QR picture downloaded from the page counts as a QR visit', function () {
    srcPage('qrsrc');
    $res = $this->get('/b/qrsrc/qr')->assertOk();
    expect($res->headers->get('Content-Type'))->toBe('image/png');
});

test('each page is an installable web app with its own name, colour, start address and icon', function () {
    Storage::fake('public');
    [, $page] = srcPage('pwapage', ['background_type' => 'solid', 'background_value' => '#112233', 'avatar_url' => 'avatars/pwa.png']);
    Storage::disk('public')->put('avatars/pwa.png', file_get_contents(public_path('images/brand/rabbit-mark.png')));
    $html = $this->get('/b/pwapage')->assertOk()->getContent();
    expect($html)->toContain('rel="manifest" href="'.route('bio.public.manifest', 'pwapage').'"')->toContain('rel="apple-touch-icon"');
    $res = $this->get(route('bio.public.manifest', 'pwapage'))->assertOk();
    $m = $res->json();
    expect($m)->toMatchArray(['name' => 'Helena Studio', 'short_name' => 'Helena Studi', 'start_url' => '/b/pwapage?src=pwa', 'scope' => '/b/pwapage', 'display' => 'standalone', 'theme_color' => '#112233'])
        ->and($m['icons'][0]['src'])->toContain('/storage/avatars/pwa.png')->and($m['icons'][0]['sizes'])->toMatch('/^\d+x\d+$/');
    expect($res->headers->get('Content-Type'))->toContain('manifest+json');
    $this->get('/b/nothere/manifest.webmanifest')->assertNotFound();
});

test('analytics shows how visitors arrived and which sites sent them', function () {
    [$user, $page, $link] = srcPage('anasrc');
    BioClick::create(['bio_link_id' => $link->id, 'clicked_at' => now(), 'source' => 'qr', 'referrer' => 'https://www.instagram.com/stories/x']);
    BioClick::create(['bio_link_id' => $link->id, 'clicked_at' => now(), 'source' => null, 'referrer' => 'https://l.instagram.com/?u=1']);
    BioClick::create(['bio_link_id' => $link->id, 'clicked_at' => now(), 'source' => 'nfc', 'referrer' => null]);
    $html = $this->actingAs($user)->get('/bio/anasrc/analytics')->assertOk()->getContent();
    expect($html)->toContain('How visitors arrived')->toContain('QR code')->toContain('NFC tag')->toContain('Link in bio')->toContain('Where they came from')->toContain('instagram.com')->toContain('Direct or unknown');
});
