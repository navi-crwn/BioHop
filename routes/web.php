<?php

use App\Http\Controllers\Admin\AdminBioController;
use App\Http\Controllers\BrandAssetController;
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
// Abuse reports are handled by LinkHop (shared abuse_reports table); the public bio page links here.
Route::get('/report-abuse', fn () => redirect()->away(rtrim((string) config('hop.apps.linkhop.url'), '/').'/report-abuse'))->name('report.create');

Route::get('/', fn () => Auth::check() ? redirect()->route('bio.index') : redirect()->to('/auth/sso'))->name('home');
Route::get('/dashboard', fn () => redirect()->route('bio.index'))->name('dashboard');
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

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.bio.index'))->name('home');
        Route::get('/bio', [AdminBioController::class, 'index'])->name('bio.index');
        Route::get('/bio/monitor', [AdminBioController::class, 'monitor'])->name('bio.monitor');
        Route::get('/bio/export', [AdminBioController::class, 'export'])->name('bio.export');
        Route::get('/bio/{bioPage}', [AdminBioController::class, 'show'])->name('bio.show');
        Route::delete('/bio/{bioPage}', [AdminBioController::class, 'destroy'])->name('bio.destroy');
        Route::post('/bio/{bioPage}/toggle', [AdminBioController::class, 'togglePublish'])->name('bio.toggle');
        Route::post('/bio/bulk-delete', [AdminBioController::class, 'bulkDelete'])->name('bio.bulk-delete');
    });
});
