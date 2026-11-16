<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\BioIndexability;
use App\Support\Seo;
use Hop\Bio\Models\BioPage;
use Illuminate\Http\Response;

/**
 * sitemap.xml: BioHop's own public pages (home, info pages, status, changelog), each once with its nine language
 * alternates and x-default (App\Support\Seo, the family rule), then the public bio pages (hel.ink/b/{slug}) that pass
 * App\Support\BioIndexability (indexing allowed, enough blocks, a real description, not flagged). Signed-in screens (/bio, /settings) are never listed.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";
        foreach (['', ...PageController::PAGES, 'status', 'changelog'] as $path) {
            $xml .= '  <url>'."\n".'    <loc>'.e(Seo::url($path)).'</loc>'."\n";
            foreach (Seo::alternates($path) as $code => $href) {
                $xml .= '    <xhtml:link rel="alternate" hreflang="'.e($code).'" href="'.e($href).'"/>'."\n";
            }
            $xml .= '  </url>'."\n";
        }

        $flagged = BioIndexability::flaggedPageIds();
        $bioPages = BioIndexability::scopeAllowed(BioPage::query())
            ->whereHas('user', fn ($q) => $q->where('status', '!=', User::STATUS_SUSPENDED))
            ->withCount(['links as blocks_count' => BioIndexability::countableBlocks()])
            ->latest('updated_at')
            ->limit(1000)
            ->get(['id', 'slug', 'bio', 'seo_description', 'is_published', 'allow_indexing', 'is_adult_content', 'content_warning', 'updated_at'])
            // The quality bar is shared with the page's own robots tag (BioIndexability): thin pages stay out of the sitemap.
            ->filter(fn (BioPage $p) => BioIndexability::passes($p, $p->blocks_count, in_array($p->id, $flagged, true)));
        foreach ($bioPages as $bio) {
            $xml .= '  <url>';
            $xml .= '<loc>'.htmlspecialchars(public_url('b/'.strtolower($bio->slug))).'</loc>';
            $xml .= '<lastmod>'.$bio->updated_at->toW3cString().'</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.6</priority>';
            $xml .= '</url>'."\n";
        }

        return response($xml.'</urlset>'."\n", 200, ['Content-Type' => 'application/xml']);
    }
}
