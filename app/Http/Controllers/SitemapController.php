<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Seo;
use Hop\Bio\Models\BioPage;
use Illuminate\Http\Response;

/**
 * sitemap.xml: BioHop's own public pages (home, info pages, status, changelog), each once with its nine language
 * alternates and x-default (App\Support\Seo, the family rule), then the public bio pages (hel.ink/b/{slug}) that allow
 * indexing, as before. Signed-in screens (/bio, /settings) are never listed.
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

        $bioPages = BioPage::query()
            ->where('is_published', true)
            ->whereHas('user', fn ($q) => $q->where('status', '!=', User::STATUS_SUSPENDED))
            ->where('allow_indexing', true)
            ->where('is_adult_content', false)
            ->where(function ($q) {
                $q->whereNull('content_warning')->orWhere('content_warning', 'none');
            })
            ->latest('updated_at')
            ->limit(1000)
            ->get(['slug', 'updated_at']);
        foreach ($bioPages as $bio) {
            $xml .= '  <url>';
            $xml .= '<loc>'.htmlspecialchars(public_url('b/'.$bio->slug)).'</loc>';
            $xml .= '<lastmod>'.$bio->updated_at->toW3cString().'</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.6</priority>';
            $xml .= '</url>'."\n";
        }

        return response($xml.'</urlset>'."\n", 200, ['Content-Type' => 'application/xml']);
    }
}
