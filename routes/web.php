<?php

use App\Http\Controllers\AbuseReportController;
use App\Http\Controllers\Api\HopBioController;
use App\Http\Controllers\BrandAssetController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SitemapController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
| BioHop routes. The bio pages themselves (public /b/{slug}, the editor under
| /bio) are registered by the hop/bio package (packages/bio/routes/bio.php);
| sign-in (/auth/sso, callback, logout) by hop/auth. Here: the shell around them.
*/

// Signing in happens at the hub. `login` is the name Laravel's auth middleware redirects to.
Route::get('/login', fn (Request $r) => redirect()->to('/auth/sso'.($r->query('next') ? '?next='.rawurlencode($r->query('next')) : '')))->name('login');
Route::get('/register', fn () => redirect()->away(User::hubUrl('register')))->name('register');
// Email verification is done at the hub; `verified` routes send unverified members there.
Route::get('/email/verify', fn () => redirect()->away(User::hubUrl('settings')))->name('verification.notice');
// Abuse reports for bio pages. Stored in the abuse_reports table shared with LinkHop,
// so the HOP control panel moderates both apps from one queue.
Route::get('/report-abuse', [AbuseReportController::class, 'create'])->name('report.create');
Route::post('/report-abuse', [AbuseReportController::class, 'store'])->middleware('throttle:5,10,report-abuse')->name('report.store');

// Root redirect fallback for hel.ink/b/ and /b when routed here (U-05).
Route::match(['GET', 'HEAD'], '/b', function (Request $request) {
    return redirect()->away('https://bio.hel.ink/'.($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301);
});
Route::match(['GET', 'HEAD'], '/b/', function (Request $request) {
    return redirect()->away('https://bio.hel.ink/'.($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301);
});

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/changelog', ChangelogController::class)->name('changelog');
// Dedicated pages on BioHop's own domain (U-07); menus link here, not to the hub.
foreach (PageController::PAGES as $page) {
    Route::get('/'.$page, [PageController::class, 'show'])->defaults('page', $page)->name('pages.'.$page);
}
Route::get('/status', [PageController::class, 'status'])->name('pages.status');
Route::get('/check-slug-public', [HomeController::class, 'checkSlug'])->middleware('throttle:60,1')->name('bio.check-slug.public');
Route::get('/dashboard', fn () => redirect()->away(User::hubUrl('dashboard').'#biohop'))->name('dashboard');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Brand images from resources/brand (BrandAssetController); URLs carry a version that changes with the files.
$brandFile = '[a-z0-9-]+\.(png|svg|ico)';
Route::get('/brand/{v}/logo.svg', [BrandAssetController::class, 'show'])->defaults('file', 'logo.svg')->where('v', '[a-f0-9]{8}')->name('brand.logo');
Route::get('/brand/{v}/favicon.svg', [BrandAssetController::class, 'show'])->defaults('file', 'favicon.svg')->where('v', '[a-f0-9]{8}')->name('brand.favicon');
Route::get('/brand/{v}/{file}', [BrandAssetController::class, 'show'])->where(['v' => '[a-f0-9]{8}', 'file' => $brandFile])->name('brand.asset');
Route::get('/brand/{file}', [BrandAssetController::class, 'legacy'])->where('file', $brandFile);

Route::middleware('hop.auth')->group(function () {
    // The sidebar's sign-out: end the session here, then the hub's (which signs out of every app).
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->away(User::hubUrl('logout'));
    })->name('logout');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::get('/settings/export', [SettingsController::class, 'export'])->middleware('throttle:5,1,export')->name('settings.export');

    // The admin pages moved to the HOP control center (Imperium), which works
    // through the signed /api/hop/* API. /admin/* sends admins there.
    Route::middleware('admin')->group(function () {
        $control = function () {
            if ($url = config('hop.control_url')) {
                return redirect()->away($url);
            }

            return view('admin-retired');
        };
        Route::get('/admin', $control)->name('admin.control');
        Route::get('/admin/{any}', $control)->where('any', '.*');
    });
});

Route::get('/internal/accounts/{user}/summary', [HopBioController::class, 'summary'])
    ->whereNumber('user')
    ->middleware(['hub.signed', 'throttle:60,1,internal'])
    ->name('internal.accounts.summary');
Route::delete('/internal/accounts/{user}', [HopBioController::class, 'purge'])
    ->whereNumber('user')
    ->middleware(['hub.signed', 'throttle:60,1,internal'])
    ->name('internal.accounts.purge');
Route::post('/hop/events', [HopBioController::class, 'events'])
    ->middleware('throttle:600,1,hop')
    ->name('hop.events');
