<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ReservedSlugs;
use Hop\Bio\Models\BioPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Slugs reserved by system routes and services to prevent collision.
     *
     * @var array<int, string>
     */
    private const RESERVED_SLUGS = [
        'admin',
        'api',
        'b',
        'dashboard',
        'login',
        'register',
        'settings',
        'up',
        'brand',
        'report-abuse',
        'sitemap',
        'logout',
        'terms',
        'privacy',
        'about',
        'help',
        'status',
        'hop',
        'linkhop',
        'pixelhop',
        'notehop',
        'biohop',
    ];

    /**
     * Render public BioHop homepage.
     */
    public function index(): View
    {
        return view('landing', [
            'user' => Auth::user(),
            'hubUrl' => User::hubUrl('dashboard').'#biohop',
            'themes' => self::galleryThemes(),
        ]);
    }

    /** Editor presets shown in the home page gallery (the real ones, so the preview matches). @return array<string, array<string, string>> */
    private static function galleryThemes(): array
    {
        $keys = ['galaxy', 'aurora', 'midnight', 'ocean', 'sunset', 'cherry', 'lavender', 'ice', 'neon', 'cyberpunk', 'matrix', 'minimal-light'];
        $presets = (array) config('bio-themes.presets', []);
        $out = [];
        foreach ($keys as $key) {
            if (isset($presets[$key]['background'])) {
                $out[$key] = array_intersect_key($presets[$key], array_flip(['name', 'background', 'text', 'bio', 'link_bg', 'link_text', 'link_border']));
            }
        }

        return $out ?: ['galaxy' => ['name' => 'Galaxy', 'background' => '#2d1b69', 'text' => '#fff', 'bio' => '#e0aaff', 'link_bg' => 'rgba(255,255,255,.15)', 'link_text' => '#fff']];
    }

    /**
     * Public endpoint to check slug availability in real time.
     */
    public function checkSlug(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ]);

        $slug = strtolower($validated['slug']);

        if (in_array($slug, self::RESERVED_SLUGS, true) || ReservedSlugs::isReserved($slug)) {
            return response()->json([
                'slug' => $slug,
                'available' => false,
                'message' => __('This name is reserved.'),
            ]);
        }

        $taken = BioPage::where('slug', $slug)->exists();

        return response()->json([
            'slug' => $slug,
            'available' => ! $taken,
            'message' => $taken ? __('This name is taken.') : __('This name is available!'),
        ]);
    }
}
