<?php

namespace Tests\Feature;

use App\Models\User;
use Hop\Bio\Models\BioPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OfficialPageTest extends TestCase
{
    use RefreshDatabase;

    private function page(string $slug, array $extra = []): BioPage
    {
        return BioPage::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'slug' => $slug,
            'title' => strtoupper($slug),
            'is_published' => true,
            'content_warning' => 'none',
        ], $extra));
    }

    public function test_only_the_official_page_asked_as_an_embed_may_be_framed(): void
    {
        $this->page('hel');
        $this->page('mira');

        $embed = $this->get('/b/hel?embed=1')->assertOk();
        $this->assertStringContainsString("frame-ancestors 'self'", $embed->headers->get('Content-Security-Policy'));
        $this->assertStringNotContainsString("frame-ancestors 'none'", $embed->headers->get('Content-Security-Policy'));
        $this->assertNull($embed->headers->get('X-Frame-Options'));
        $embed->assertSee('<base target="_blank">', false)->assertSee('noindex', false);

        foreach (['/b/hel', '/b/mira?embed=1', '/b/mira'] as $url) {
            $response = $this->get($url)->assertOk();
            $this->assertStringContainsString("frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'), $url);
            $this->assertSame('DENY', $response->headers->get('X-Frame-Options'), $url);
        }
    }

    public function test_an_embedded_view_is_not_counted_as_a_visit(): void
    {
        $page = $this->page('hel');

        $this->get('/b/hel?embed=1')->assertOk();
        $this->assertSame(0, (int) $page->fresh()->view_count);

        $this->get('/b/hel')->assertOk();
        $this->assertSame(1, (int) $page->fresh()->view_count);
    }

    public function test_the_home_shows_the_live_page_only_while_it_is_published(): void
    {
        Cache::flush();
        $this->get('/')->assertOk()->assertDontSee('class="bio-live"', false);

        $this->page('hel');
        Cache::flush();
        $this->get('/')->assertOk()->assertSee('class="bio-live"', false)->assertSee('/b/hel?embed=1', false);

        BioPage::where('slug', 'hel')->update(['password_enabled' => true]);
        Cache::flush();
        $this->get('/')->assertOk()->assertDontSee('class="bio-live"', false);
    }
}
