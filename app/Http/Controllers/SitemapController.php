<?php

namespace App\Http\Controllers;

use Hop\Bio\Models\BioPage;
use Illuminate\Http\Response;

/** sitemap.xml of the public bio pages (hel.ink/b/{slug}) that allow indexing. */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $bioPages = BioPage::query()
            ->where('is_published', true)
            ->where('allow_indexing', true)
            ->where('is_adult_content', false)
            ->latest('updated_at')
            ->limit(1000)
            ->get(['slug', 'updated_at']);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($bioPages as $bio) {
            $xml .= '<url>';
            $xml .= '<loc>'.htmlspecialchars(public_url('b/'.$bio->slug)).'</loc>';
            $xml .= '<lastmod>'.$bio->updated_at->toW3cString().'</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.6</priority>';
            $xml .= '</url>';
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
