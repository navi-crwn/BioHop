<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * BioHop's dedicated pages on its own domain (U-07). Menus link here, not to the hub;
 * the hub keeps only account pages (sign-in, profile, API tokens).
 */
class PageController extends Controller
{
    public const PAGES = ['features', 'about', 'help', 'faq', 'contact', 'terms', 'privacy'];

    public function show(string $page): View
    {
        abort_unless(in_array($page, self::PAGES, true), 404);

        return view('pages.'.$page);
    }

    /** Live status from this server: the app answers, the database and the queue. */
    public function status()
    {
        try {
            DB::select('select 1');
            $db = true;
        } catch (\Throwable) {
            $db = false;
        }
        $beat = cache('queue:heartbeat');
        $heartbeat = is_array($beat) ? ($beat['at'] ?? null) : $beat;

        return response()->view('pages.status', [
            'db' => $db,
            'queue' => $heartbeat !== null,
            'heartbeat' => $heartbeat,
        ], $db ? 200 : 503)->header('Cache-Control', 'no-store');
    }
}
