<?php

use App\Models\User;
use Hop\Bio\Models\BioLink;
use Hop\Bio\Models\BioPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit', '../packages/bio/tests');

/**
 * A public bio page that passes the search-engine bar (App\Support\BioIndexability): enough blocks and a real description.
 * For tests that need a page in the sitemap or without a noindex tag.
 */
function indexablePage(User $user, string $slug, array $extra = [], int $blocks = 3): BioPage
{
    $page = BioPage::create($extra + [
        'user_id' => $user->id, 'slug' => $slug, 'title' => ucfirst($slug), 'is_published' => true, 'allow_indexing' => true,
        'seo_description' => 'A page with enough words in its description to be offered to search engines.',
    ]);
    for ($i = 0; $i < $blocks; $i++) {
        BioLink::create(['bio_page_id' => $page->id, 'type' => 'link', 'title' => 'Link '.$i, 'url' => 'https://example.com/'.$i, 'order' => $i, 'is_active' => true]);
    }

    return $page;
}
