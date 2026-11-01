<?php

namespace Tests\Feature;

use App\Console\Commands\OfficialPage;
use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Hop\Ui\Assets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OfficialPageCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_writes_nothing_and_needs_an_owner_for_a_new_page(): void
    {
        $this->artisan('bio:official-page')->assertFailed();
        $user = User::factory()->create();
        $this->artisan('bio:official-page', ['--user' => $user->email])->expectsOutputToContain('Dry run')->assertSuccessful();
        $this->assertDatabaseMissing('bio_pages', ['slug' => 'hel']);
    }

    public function test_it_lists_every_public_app_and_can_run_again_without_duplicates(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->artisan('bio:official-page', ['--user' => $user->email, '--apply' => true])->assertSuccessful();
        $page = BioPage::where('slug', 'hel')->firstOrFail();
        Storage::disk('public')->assertExists(OfficialPage::AVATAR);
        $this->assertSame(OfficialPage::AVATAR, $page->avatar_url);
        $this->assertTrue($page->is_published);
        $this->assertSame('HEL.ink', $page->title);
        foreach (Assets::family() as $app) {
            $this->assertDatabaseHas('bio_links', ['bio_page_id' => $page->id, 'type' => 'link', 'title' => $app['name'], 'url' => $app['url']]);
        }
        $this->assertDatabaseHas('bio_links', ['bio_page_id' => $page->id, 'type' => 'faq']);
        $count = BioLink::where('bio_page_id', $page->id)->count();

        BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Added by hand', 'url' => 'https://example.com', 'order' => 99]);
        $this->artisan('bio:official-page', ['--apply' => true])->assertSuccessful();
        $this->assertSame($count + 1, BioLink::where('bio_page_id', $page->id)->count());
        $this->assertDatabaseHas('bio_links', ['bio_page_id' => $page->id, 'title' => 'Added by hand']);

        $this->get('/b/hel')->assertOk()->assertSee('LinkHop')->assertSee('NoteHop')->assertSee('Hop in');
        $this->get('/')->assertOk()->assertSee('class="bio-live"', false);
    }
}
