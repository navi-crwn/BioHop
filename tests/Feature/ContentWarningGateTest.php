<?php

namespace Tests\Feature;

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentWarningGateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_page_with_no_warning_is_accessible_directly(): void
    {
        $page = BioPage::create([
            'user_id' => $this->user->id,
            'slug' => 'clean-page',
            'title' => 'Clean Bio',
            'is_published' => true,
            'content_warning' => 'none',
        ]);

        $response = $this->get('/b/'.$page->slug);
        $response->assertOk();
        $response->assertDontSee('Sensitive Content Warning');
        $response->assertDontSee('Age Verification Required');
        $response->assertSee('Clean Bio');
    }

    public function test_sensitive_page_displays_content_warning_gate_and_reason(): void
    {
        $page = BioPage::create([
            'user_id' => $this->user->id,
            'slug' => 'sensitive-page',
            'title' => 'Horror Game Discussion',
            'is_published' => true,
            'content_warning' => 'sensitive',
            'warning_reason' => 'Contains discussions of fictional violence and gore.',
        ]);

        $response = $this->get('/b/'.$page->slug);
        $response->assertOk();
        $response->assertSee('Sensitive Content Warning');
        $response->assertSee('Contains discussions of fictional violence and gore.');
        $response->assertSee('I understand — Continue');
        $response->assertSee('Remember for 24 hours on this device');
        $response->assertDontSee('legal age');
    }

    public function test_sensitive_page_can_be_unlocked_via_form_post_without_js(): void
    {
        $page = BioPage::create([
            'user_id' => $this->user->id,
            'slug' => 'unlocked-sensitive',
            'title' => 'Sensitive Story',
            'is_published' => true,
            'content_warning' => 'sensitive',
        ]);

        // POST confirmation
        $postResponse = $this->post('/b/'.$page->slug, [
            'age_confirm' => 'yes',
        ]);

        $postResponse->assertStatus(303);
        $postResponse->assertSessionHas("bio_age_verified_{$page->id}", true);

        // Follow redirect with session
        $followResponse = $this->get('/b/'.$page->slug);
        $followResponse->assertOk();
        $followResponse->assertSee('Sensitive Story');
        $followResponse->assertDontSee('Sensitive Content Warning');
    }

    public function test_remember_24h_cookie_bypasses_gate_on_subsequent_visits(): void
    {
        config(['session.domain' => '.hel.ink']);
        $page = BioPage::create([
            'user_id' => $this->user->id,
            'slug' => 'remember-me-bio',
            'title' => '18+ Mature Art',
            'is_published' => true,
            'content_warning' => 'adult',
        ]);

        // Submit with remember=1
        $postResponse = $this->post('/b/'.$page->slug, [
            'age_confirm' => 'yes',
            'remember' => '1',
        ]);

        $postResponse->assertStatus(303);
        $postResponse->assertCookie("hop_gate_bio_{$page->id}", '1');
        $cookie = collect($postResponse->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === "hop_gate_bio_{$page->id}");
        $this->assertNull($cookie->getDomain());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertEqualsWithDelta(86400, $cookie->getExpiresTime() - time(), 10);

        // New request simulating fresh session with cookie
        $this->flushSession();
        $cookieResponse = $this->withCookie("hop_gate_bio_{$page->id}", '1')
            ->get('/b/'.$page->slug);

        $cookieResponse->assertOk();
        $cookieResponse->assertSee('18+ Mature Art');
        $cookieResponse->assertDontSee('Age Verification Required');
    }

    public function test_legacy_is_adult_content_flag_maps_to_adult_gate(): void
    {
        $page = BioPage::create([
            'user_id' => $this->user->id,
            'slug' => 'legacy-adult-page',
            'title' => 'Legacy Adult Bio',
            'is_published' => true,
            'is_adult_content' => true,
        ]);

        $this->assertTrue($page->isAdult());
        $this->assertEquals('adult', $page->content_warning);

        $response = $this->get('/b/'.$page->slug);
        $response->assertOk();
        $response->assertSee('Age Verification Required');
        $response->assertSee('18+ Age Verification Required');
    }

    public function test_sitemap_excludes_sensitive_and_adult_pages(): void
    {
        BioPage::create([
            'user_id' => $this->user->id,
            'slug' => 'public-clean',
            'title' => 'Public Bio',
            'is_published' => true,
            'allow_indexing' => true,
            'content_warning' => 'none',
        ]);

        BioPage::create([
            'user_id' => $this->user->id,
            'slug' => 'public-sensitive',
            'title' => 'Sensitive Bio',
            'is_published' => true,
            'allow_indexing' => true,
            'content_warning' => 'sensitive',
        ]);

        BioPage::create([
            'user_id' => $this->user->id,
            'slug' => 'public-adult',
            'title' => 'Adult Bio',
            'is_published' => true,
            'allow_indexing' => true,
            'content_warning' => 'adult',
        ]);

        $response = $this->get('/sitemap.xml');
        $response->assertOk();
        $response->assertSee('public-clean');
        $response->assertDontSee('public-sensitive');
        $response->assertDontSee('public-adult');
    }
}
