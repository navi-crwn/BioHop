<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * /settings: the account itself lives at the hub; here only what is BioHop's:
 * an export of the member's bio pages (the hub's "export my data" links to it).
 */
class SettingsController extends Controller
{
    public function index(): View
    {
        return view('settings');
    }

    public function export(Request $request): JsonResponse
    {
        $user = $request->user();
        $pages = $user->bioPages()->with('links')->get()->map(fn ($page) => [
            'page' => $page->makeHidden(['password'])->toArray(),
            'public_url' => public_url('b/'.$page->slug),
            'blocks' => $page->links->map(fn ($l) => $l->toArray())->all(),
        ]);

        return response()->json([
            'app' => 'BioHop',
            'exported_at' => now()->toIso8601String(),
            'user' => ['id' => $user->id, 'email' => $user->email, 'name' => $user->name],
            'bio_pages' => $pages,
        ], 200, ['Content-Disposition' => 'attachment; filename="biohop-export.json"'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
