<?php

use App\Http\Controllers\AbuseReportController;
use App\Http\Controllers\Api\HopBioController;
use App\Http\Controllers\BrandAssetController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ReminderController;
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
Route::get('/login', fn (Request $r) => redirect()->to('/auth/sso'.(is_string($r->query('next')) && $r->query('next') !== '' ? '?next='.rawurlencode($r->query('next')) : '')))->name('login');
Route::get('/register', fn () => redirect()->away(User::hubUrl('register')))->name('register');
// Email verification is done at the hub; `verified` routes send unverified members there.
Route::get('/email/verify', fn () => redirect()->away(User::hubUrl('settings')))->name('verification.notice');
// Abuse reports for bio pages. Stored in the abuse_reports table shared with LinkHop,
// so the HOP control panel moderates both apps from one queue.
Route::get('/report-abuse', [AbuseReportController::class, 'create'])->name('report.create');
Route::post('/report-abuse', [AbuseReportController::class, 'store'])->middleware('throttle:5,10,report-abuse')->name('report.store');

// Root redirect fallback for hel.ink/b/ and /b when routed here (U-05).
Route::match(['GET', 'HEAD'], '/b', function (Request $request) {
    return redirect()->away(rtrim((string) config('app.url'), '/').'/'.($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301);
});
Route::match(['GET', 'HEAD'], '/b/', function (Request $request) {
    return redirect()->away(rtrim((string) config('app.url'), '/').'/'.($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301);
});

// Health check for the watchdog: a plain answer (the framework's default /up page loads a CDN script and font).
Route::get('/up', fn () => response('ok', 200, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'no-store']));

// hop.recheck: the public pages that show the signed-in account confirm a stale session with the hub (silent, once per
// hop-auth.recheck.max_age). Never on the public bio pages (/b/{slug}) or other hot paths.
Route::get('/', [HomeController::class, 'index'])->middleware('hop.recheck')->name('home');
Route::get('/changelog', ChangelogController::class)->middleware('hop.recheck')->name('changelog');
// Dedicated pages on BioHop's own domain (U-07); menus link here, not to the hub.
foreach (PageController::PAGES as $page) {
    Route::get('/'.$page, [PageController::class, 'show'])->defaults('page', $page)->middleware('hop.recheck')->name('pages.'.$page);
}
Route::get('/status', [PageController::class, 'status'])->middleware('hop.recheck')->name('pages.status');
Route::get('/check-slug-public', [HomeController::class, 'checkSlug'])->middleware('throttle:60,1')->name('bio.check-slug.public');
// The hub launcher (hub.hel.ink/apps/biohop) and the other apps' HOP family links land here after
// sign-in. Send people to BioHop's own dashboard; sending them back to the hub left them stuck there.
Route::get('/dashboard', fn () => redirect()->route('bio.index'))->name('dashboard');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// robots.txt is an answer, not a static file, so the Sitemap line carries this install's address. Public bio pages (/b/...) stay open;
// the sign-in-only areas are kept out of search.
Route::get('/robots.txt', fn () => response(
    "User-agent: *\n"
    ."Disallow: /bio/\nDisallow: /settings\nDisallow: /dashboard\nDisallow: /admin\nDisallow: /auth/\nDisallow: /login\nDisallow: /logout\nDisallow: /internal/\nDisallow: /hop/\nDisallow: /api/\n"
    .'Sitemap: '.route('sitemap')."\n",
    200,
    ['Content-Type' => 'text/plain; charset=UTF-8'],
))->name('robots');

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

        // The hub's chained logout ends on `return` (when it is a family app), so the person lands back here.
        return redirect()->away(User::hubUrl('logout').'?'.http_build_query(['return' => route('home')]));
    })->name('logout');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::get('/settings/export', [SettingsController::class, 'export'])->middleware('throttle:5,1,export')->name('settings.export');
    // "Hide · Shows again next week" on a reminder card (hop-ui.js posts the card's key here).
    Route::post('/reminders/dismiss', [ReminderController::class, 'dismiss'])->middleware('throttle:30,1')->name('reminders.dismiss');

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
