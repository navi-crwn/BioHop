<?php

use Hop\Bio\Http\Controllers\BioLinkController;
use Hop\Bio\Http\Controllers\BioPageController;
use Hop\Bio\Http\Controllers\BioRevisionController;
use Hop\Bio\Http\Controllers\BioShortlinkController;
use Hop\Bio\Http\Controllers\BioWebhookController;
use Hop\Bio\Http\Controllers\PublicBioController;
use Hop\Bio\Http\Middleware\UploadStorageGate;
use Hop\Bio\Support\UploadStorage;
use Illuminate\Support\Facades\Route;

// Public pages: hel.ink/b/{slug}. The host app's ip.ban middleware keeps banned visitors out.
Route::middleware(['web', 'ip.ban'])->prefix('b')->name('bio.public.')->group(function () {
    Route::get('/shop/{slug}', [PublicBioController::class, 'shop'])->name('shop');
    Route::match(['get', 'post'], '/{slug}', [PublicBioController::class, 'show'])->name('show');
    Route::get('/{slug}/qr', [PublicBioController::class, 'qr'])->name('qr');
    Route::get('/{slug}/manifest.webmanifest', [PublicBioController::class, 'manifest'])->name('manifest');
    Route::get('/click/{bioLink:public_id}', [PublicBioController::class, 'click'])->middleware('throttle:bio-click')->name('click');
    Route::post('/poll/{bioLink:public_id}', [PublicBioController::class, 'vote'])->middleware('throttle:20,10')->name('poll');
    Route::post('/form/{bioLink:public_id}', [PublicBioController::class, 'submitForm'])->middleware('throttle:20,10')->name('form');
    Route::post('/click/{bioLink:public_id}/pin', [PublicBioController::class, 'pin'])->middleware('throttle:bio-click')->name('click.pin');
});

// The editor, for signed-in members. `hop.auth` (hop/auth) sends guests through the hub (HOP ID);
// `verified` needs a verified email, which the hub handles (route verification.notice points there).
Route::middleware(['web', 'ip.ban', 'hop.auth', UploadStorageGate::class])->prefix('bio')->name('bio.')->group(function () {
    Route::get('/', [BioPageController::class, 'index'])->name('index');
    Route::get('/create', [BioPageController::class, 'create'])->name('create');
    Route::get('/check-slug', [BioPageController::class, 'checkSlug'])->name('check-slug');
    Route::get('/upload-status', fn () => response()->json(app(UploadStorage::class)->status())->header('Cache-Control', 'no-store'))->name('upload-status');
    Route::post('/', [BioPageController::class, 'store'])->middleware('verified')->name('store');
    Route::post('/wizard', [BioPageController::class, 'storeWizard'])->middleware('verified')->name('wizard');
    Route::get('/import', [BioPageController::class, 'importForm'])->name('import');
    Route::post('/import', [BioPageController::class, 'import'])->middleware(['verified', 'throttle:6,1'])->name('import.store');
    Route::get('/{bioPage}/edit', [BioPageController::class, 'edit'])->name('edit');
    Route::put('/{bioPage}', [BioPageController::class, 'update'])->middleware('verified')->name('update');
    Route::delete('/{bioPage}', [BioPageController::class, 'destroy'])->name('destroy');
    Route::get('/{bioPage}/analytics', [BioPageController::class, 'analytics'])->name('analytics');
    Route::post('/{bioPage}/duplicate', [BioPageController::class, 'duplicate'])->middleware('verified')->name('duplicate');
    Route::post('/{bioPage}/upload-image', [BioPageController::class, 'uploadImage'])->name('upload-image');
    Route::post('/{bioPage}/upload-media', [BioPageController::class, 'uploadMedia'])->name('upload-media');
    Route::post('/{bioPage}/upload-avatar', [BioPageController::class, 'uploadAvatar'])->name('upload-avatar');
    Route::post('/{bioPage}/upload-thumbnail', [BioPageController::class, 'uploadThumbnail'])->name('upload-thumbnail');
    Route::post('/{bioPage}/upload-icon', [BioPageController::class, 'uploadIcon'])->name('upload-icon');
    Route::post('/{bioPage}/upload-qr-logo', [BioPageController::class, 'uploadQrLogo'])->name('upload-qr-logo');
    Route::get('/{bioPage}/preview', [BioPageController::class, 'preview'])->name('preview');
    // Saved versions of a page: {revision} must belong to {bioPage}
    Route::prefix('{bioPage}/revisions')->name('revisions.')->scopeBindings()->group(function () {
        Route::get('/', [BioRevisionController::class, 'index'])->name('index');
        Route::post('/', [BioRevisionController::class, 'store'])->middleware('verified')->name('store');
        Route::post('/{revision}/restore', [BioRevisionController::class, 'restore'])->middleware('verified')->name('restore');
    });
    // Webhook for form entries: deliveries must belong to {bioPage}
    Route::prefix('{bioPage}/webhook')->name('webhook.')->scopeBindings()->group(function () {
        Route::get('/', [BioWebhookController::class, 'index'])->name('index');
        Route::post('/test', [BioWebhookController::class, 'test'])->middleware(['verified', 'throttle:10,1'])->name('test');
        Route::post('/secret', [BioWebhookController::class, 'rotate'])->middleware('verified')->name('rotate');
        Route::post('/{delivery}/resend', [BioWebhookController::class, 'resend'])->middleware(['verified', 'throttle:20,1'])->name('resend');
    });
    // Short links for blocks, through the ShortlinkCreator contract (BioHop: LinkHop's API with the member's token).
    Route::get('/links/search', [BioShortlinkController::class, 'search'])->name('links.search');
    Route::post('/shorten', [BioShortlinkController::class, 'store'])
        ->middleware(['verified', 'throttle:shorten'])
        ->name('shorten');
    // scopeBindings: {bioLink} must belong to {bioPage}, otherwise 404
    Route::prefix('{bioPage}/links')->name('links.')->scopeBindings()->group(function () {
        Route::post('/', [BioLinkController::class, 'store'])->middleware('verified')->name('store');
        Route::put('/{bioLink}', [BioLinkController::class, 'update'])->middleware('verified')->name('update');
        Route::delete('/{bioLink}', [BioLinkController::class, 'destroy'])->name('destroy');
        Route::post('/fetch-meta', [BioLinkController::class, 'fetchMeta'])->middleware(['verified', 'throttle:20,1'])->name('fetch-meta');
        Route::post('/reorder', [BioLinkController::class, 'reorder'])->middleware('verified')->name('reorder');
        Route::post('/{bioLink}/toggle', [BioLinkController::class, 'toggle'])->middleware('verified')->name('toggle');
        Route::post('/{bioLink}/archive', [BioLinkController::class, 'archive'])->middleware('verified')->name('archive');
        Route::post('/{bioLink}/restore', [BioLinkController::class, 'restore'])->middleware('verified')->name('restore');
        Route::get('/{bioLink}/leads.csv', [BioLinkController::class, 'leadsCsv'])->name('leads.csv');
        Route::get('/{bioLink}/poll', [BioLinkController::class, 'pollResults'])->name('poll.results');
        Route::delete('/{bioLink}/poll', [BioLinkController::class, 'pollReset'])->middleware('verified')->name('poll.reset');
        Route::delete('/{bioLink}/leads', [BioLinkController::class, 'leadsDestroy'])->middleware('verified')->name('leads.destroy');
    });
});

// The same shortener door under /api for the editor's older JavaScript (web session, not an API token).
Route::middleware(['api', 'web', 'hop.auth', 'verified', 'throttle:shorten'])
    ->post('/api/bio/shorten', [BioShortlinkController::class, 'store'])->name('bio.api.shorten');
