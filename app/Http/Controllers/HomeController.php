<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        ]);
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

        if (in_array($slug, self::RESERVED_SLUGS, true)) {
            return response()->json([
                'slug' => $slug,
                'available' => false,
                'message' => 'Slug ini dicadangkan untuk sistem.',
            ]);
        }

        $taken = BioPage::where('slug', $slug)->exists();

        return response()->json([
            'slug' => $slug,
            'available' => ! $taken,
            'message' => $taken ? 'Slug ini sudah digunakan.' : 'Slug ini tersedia!',
        ]);
    }
}
