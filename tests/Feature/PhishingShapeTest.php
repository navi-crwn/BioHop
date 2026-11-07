<?php

use App\Models\AbuseFlag;
use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// The address handed to victims of the Telegram account-takeover kit on 2026-11-05, and the
// same kit after it moved to a readable subdomain. LinkHop blocks both by the shape of the
// address (app/Support/PhishingShape.php); BioHop stores and redirects bio links, so it must
// answer the same way or a bio block becomes the way around a LinkHop block.
const KIT_PATH_URL = 'https://pylinss.jehenmy.com/wqamhcsom?r=gnqhLD&t=1793793002';
const KIT_SHAPE_URL = 'https://xkpqtrvz.brand-new-site.com/qwrtzpsk?r=1&t=2';

// An ordinary shop page, and an ordinary page that happens to carry two short tracking parameters.
const ORDINARY_PRODUCT_URL = 'https://mecivietnam.com/products/san-nang-thuy-luc';
const ORDINARY_TRACKING_URL = 'https://example.com/downloads?r=1&t=2';

function phishingPage(string $slug = 'safe-links'): array
{
    $user = User::factory()->create();
    $page = BioPage::create(['user_id' => $user->id, 'slug' => $slug, 'title' => 'Links']);

    return [$user, $page];
}

beforeEach(function () {
    config(['services.turnstile.secret' => null, 'services.turnstile.site_key' => null]);
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

it('refuses a bio block whose destination is the kit path', function () {
    [$user, $page] = phishingPage();

    $this->actingAs($user)
        ->postJson("/bio/{$page->slug}/links", ['type' => 'link', 'title' => 'x', 'url' => KIT_PATH_URL])
        ->assertUnprocessable()->assertJsonValidationErrors('url');

    expect(BioLink::where('bio_page_id', $page->id)->count())->toBe(0);
});

it('refuses a bio block whose destination has the kit shape', function (string $url) {
    [$user, $page] = phishingPage();

    $this->actingAs($user)
        ->postJson("/bio/{$page->slug}/links", ['type' => 'link', 'title' => 'x', 'url' => $url])
        ->assertUnprocessable()->assertJsonValidationErrors('url');
})->with([
    'kit path on an ordinary domain' => ['https://example.com/wqamhcsom?r=1&t=2'],
    'random subdomain, path and tracking' => [KIT_SHAPE_URL],
    'kit address as seen in the incident' => [KIT_PATH_URL],
]);

it('still accepts ordinary destinations', function (string $url) {
    [$user, $page] = phishingPage();

    $this->actingAs($user)
        ->postJson("/bio/{$page->slug}/links", ['type' => 'link', 'title' => 'Products', 'url' => $url])
        ->assertOk();
})->with([
    'shop product page' => [ORDINARY_PRODUCT_URL],
    'readable page with two tracking parameters' => [ORDINARY_TRACKING_URL],
    'github profile' => ['https://github.com/navi-crwn'],
]);

it('refuses the kit in a bio page social link', function () {
    [$user, $page] = phishingPage('social-page');

    $this->actingAs($user)
        ->putJson("/bio/{$page->slug}", [
            'title' => 'Links',
            'social_links' => [['platform' => 'website', 'value' => KIT_PATH_URL]],
        ])
        ->assertUnprocessable()->assertJsonValidationErrors('social_links.0.value');
});

it('unpublishes a bio block that points at the kit when the scan runs', function () {
    [$user, $page] = phishingPage('scanned-page');
    $block = BioLink::create([
        'bio_page_id' => $page->id,
        'type' => 'link',
        'title' => 'Promo',
        'url' => KIT_PATH_URL,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $this->artisan('abuse:scan', ['--full' => true])->assertSuccessful();

    $signals = (array) AbuseFlag::where('subject_type', 'bio_link')->where('subject_id', $block->id)->value('signals');

    expect($block->fresh()->is_active)->toBeFalse()
        ->and($signals)->toContain('phishing_shape', 'kit_path');
});

it('leaves an ordinary bio block active when the scan runs', function () {
    [$user, $page] = phishingPage('ordinary-page');
    $block = BioLink::create([
        'bio_page_id' => $page->id,
        'type' => 'link',
        'title' => 'Products',
        'url' => ORDINARY_PRODUCT_URL,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $this->artisan('abuse:scan', ['--full' => true])->assertSuccessful();

    expect($block->fresh()->is_active)->toBeTrue()
        ->and(AbuseFlag::where('subject_id', $block->id)->exists())->toBeFalse();
});
