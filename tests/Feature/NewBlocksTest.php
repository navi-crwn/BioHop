<?php

use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function blockPage(string $slug, array $extra = []): array
{
    $user = User::factory()->create();
    $page = BioPage::create($extra + ['user_id' => $user->id, 'slug' => $slug, 'title' => 'Blocks', 'is_published' => true]);

    return [$user, $page];
}

test('the status chip shows until its end time and the floating button follows its type', function () {
    [$user, $page] = blockPage('chip');
    $this->actingAs($user)->putJson('/bio/chip', ['status_text' => 'Open for commissions', 'status_until' => now()->addDay()->toIso8601String(), 'fab_type' => 'whatsapp', 'fab_url' => '+62 812-3456-7890', 'fab_label' => 'Chat us'])->assertOk();
    $html = $this->get('/b/chip')->assertOk()->getContent();
    expect($html)->toContain('class="bio-status"')->toContain('Open for commissions')->toContain('class="bio-fab whatsapp"')->toContain('https://wa.me/6281234567890')->toContain('Chat us');
    $this->travel(2)->days();
    $this->get('/b/chip')->assertDontSee('class="bio-status"', false);
    $this->travelBack();
    $this->putJson('/bio/chip', ['fab_type' => 'custom', 'fab_url' => 'javascript:alert(1)'])->assertStatus(422);
    $this->putJson('/bio/chip', ['fab_type' => 'none'])->assertOk();
    $this->get('/b/chip')->assertDontSee('class="bio-fab', false);
});

test('an app block sends iPhone, Android and other visitors to their own address', function () {
    [$user, $page] = blockPage('appb');
    $this->actingAs($user)->postJson('/bio/appb/links', ['type' => 'app', 'title' => 'Get the app', 'settings' => ['ios' => 'https://apps.apple.com/app/id1', 'android' => 'https://play.google.com/store/apps/details?id=x', 'web' => 'https://app.example']])->assertOk();
    $this->postJson('/bio/appb/links', ['type' => 'app', 'title' => 'Bad', 'settings' => ['ios' => 'http://insecure.example']])->assertStatus(422);
    $link = BioLink::where('title', 'Get the app')->firstOrFail();
    auth()->logout();
    $this->get('/b/appb')->assertOk()->assertSee(route('bio.public.click', $link->public_id), false);
    $iphone = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
    $android = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Mobile Safari/537.36';
    $mac = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15';
    $this->withHeader('User-Agent', $iphone)->get(route('bio.public.click', $link->public_id))->assertRedirect('https://apps.apple.com/app/id1');
    $this->withHeader('User-Agent', $android)->get(route('bio.public.click', $link->public_id))->assertRedirect('https://play.google.com/store/apps/details?id=x');
    $this->withHeader('User-Agent', $mac)->get(route('bio.public.click', $link->public_id))->assertRedirect('https://app.example');
});

test('a goal block shows its percentage and an optional link', function () {
    [$user, $page] = blockPage('goalb');
    $this->actingAs($user)->postJson('/bio/goalb/links', ['type' => 'goal', 'title' => 'Short film', 'url' => 'https://example.com/help', 'description' => 'Support the film', 'settings' => ['current' => '7500000', 'target' => '10000000', 'unit' => 'IDR']])->assertOk();
    $link = BioLink::firstOrFail();
    expect($link->goalPercent())->toBe(75);
    $html = $this->get('/b/goalb')->assertOk()->getContent();
    expect($html)->toContain('role="progressbar"')->toContain('aria-valuenow="75"')->toContain('7,500,000 IDR')->toContain('Support the film');
});

test('audio and PDF files are uploaded here and only those are played or linked', function () {
    Storage::fake('public');
    [$user, $page] = blockPage('media');
    $this->actingAs($user);
    $audio = $this->post('/bio/media/upload-media', ['file' => UploadedFile::fake()->create('clip.mp3', 200, 'audio/mpeg')], ['Accept' => 'application/json'])->assertOk()->json();
    $pdf = $this->post('/bio/media/upload-media', ['file' => UploadedFile::fake()->create('menu.pdf', 300, 'application/pdf')], ['Accept' => 'application/json'])->assertOk()->json();
    expect($audio['url'])->toStartWith('/storage/bio-audio/')->and($pdf['url'])->toStartWith('/storage/bio-files/');
    Storage::disk('public')->assertExists($audio['path']);
    $this->post('/bio/media/upload-media', ['file' => UploadedFile::fake()->create('x.exe', 10, 'application/octet-stream')], ['Accept' => 'application/json'])->assertStatus(422);
    $this->postJson('/bio/media/links', ['type' => 'audio', 'title' => 'Demo', 'content' => $audio['url']])->assertOk();
    $this->postJson('/bio/media/links', ['type' => 'pdf', 'title' => 'Menu', 'content' => $pdf['url']])->assertOk();
    $this->postJson('/bio/media/links', ['type' => 'audio', 'title' => 'Outside', 'content' => 'https://evil.example/a.mp3'])->assertStatus(422);
    $html = $this->get('/b/media')->assertOk()->getContent();
    expect($html)->toContain('<audio controls')->toContain($audio['url'])->toContain('class="bio-pdf link-block"')->toContain($pdf['url']);
});

test('a carousel keeps its own pictures and renders them with captions', function () {
    [$user, $page] = blockPage('carou');
    $this->actingAs($user)->postJson('/bio/carou/links', ['type' => 'carousel', 'title' => 'Studio', 'settings' => ['images' => [['url' => '/storage/bio-images/a.jpg', 'caption' => 'The desk'], ['url' => 'https://cdn.example/b.jpg', 'caption' => '']]]])->assertOk();
    $this->postJson('/bio/carou/links', ['type' => 'carousel', 'title' => 'Bad', 'settings' => ['images' => [['url' => 'javascript:alert(1)']]]])->assertStatus(422);
    $html = $this->get('/b/carou')->assertOk()->getContent();
    expect($html)->toContain('class="bio-carousel')->toContain('The desk')->toContain('/storage/bio-images/a.jpg')->toContain('https://cdn.example/b.jpg');
});
