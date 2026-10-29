<?php

use App\Http\Controllers\Api\HopBioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| HOP App Protocol v1 (BioHop)
|--------------------------------------------------------------------------
| Signed server-to-server endpoints for Imperium (`/api/hop/*`) and account
| lifecycle events (`/api/hop/events`).
*/
Route::post('/hop/events', [HopBioController::class, 'events'])->middleware('throttle:600,1,hop');

Route::middleware(['hop.signed', 'throttle:600,1,hop', 'hop.admin_client'])->prefix('hop')->group(function () {
    Route::get('/health', [HopBioController::class, 'health']);
    Route::get('/manifest', [HopBioController::class, 'manifest']);
    Route::get('/stats', [HopBioController::class, 'stats']);
    Route::get('/content', [HopBioController::class, 'index']);
    Route::get('/content/{type}/{id}', [HopBioController::class, 'show'])->where(['type' => 'page|bio|block', 'id' => '[0-9]+']);
    Route::post('/content/{type}/{id}/actions', [HopBioController::class, 'act'])->where(['type' => 'page|bio|block', 'id' => '[0-9]+']);
    Route::get('/reports', [HopBioController::class, 'reports']);
    Route::post('/reports/{kind}/{id}/actions', [HopBioController::class, 'reportAct'])->where(['kind' => 'flag|report', 'id' => '[0-9]+']);
    Route::post('/reports/{id}/actions', [HopBioController::class, 'reportAct'])->where(['id' => '[0-9]+']);
    Route::get('/settings', [HopBioController::class, 'settings']);
    Route::put('/settings', [HopBioController::class, 'saveSettings']);
    Route::get('/search', [HopBioController::class, 'search']);
    Route::get('/audit', [HopBioController::class, 'audit']);
});
