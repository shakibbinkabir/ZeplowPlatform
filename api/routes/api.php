<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\Internal\ConfigSyncController;
use App\Http\Controllers\Internal\ContentSyncController;
use App\Http\Controllers\Internal\DeployController;
use App\Http\Controllers\Sites\ContactController;
use App\Http\Controllers\Sites\SiteBlogController;
use App\Http\Controllers\Sites\SiteConfigController;
use App\Http\Controllers\Sites\SitePageController;
use App\Http\Controllers\Sites\SiteProjectController;
use App\Http\Controllers\Sites\SiteTeamController;
use App\Http\Controllers\Sites\SiteTestimonialController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
|
| Open, no auth, no rate limit. Hit by UptimeRobot every 5 minutes.
|
*/
Route::get('/health', [HealthController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Public API Routes (Next.js build-time consumers + browser contact form)
|--------------------------------------------------------------------------
|
| Stack order matters: build_agent first (so the rate limiter sees the
| is_build_agent attribute), then throttle, then site_key whitelist.
|
| - Default rate: 60/min per IP (public_api limiter)
| - Build agents with valid X-Build-Token: 300/min
| - Contact form: extra 5/min layered on top
|
*/
Route::prefix('sites/v1/{siteKey}')
    ->middleware(['build_agent', 'throttle:public_api', 'site_key'])
    ->group(function () {
        Route::get('/config',          [SiteConfigController::class, 'show']);
        Route::get('/pages',           [SitePageController::class, 'index']);
        Route::get('/pages/{slug}',    [SitePageController::class, 'show']);
        Route::get('/projects',        [SiteProjectController::class, 'index']);
        Route::get('/projects/{slug}', [SiteProjectController::class, 'show']);
        Route::get('/blog',            [SiteBlogController::class, 'index']);
        Route::get('/blog/{slug}',     [SiteBlogController::class, 'show']);
        Route::get('/testimonials',    [SiteTestimonialController::class, 'index']);
        Route::get('/team',            [SiteTeamController::class, 'index']);

        Route::post('/contact', [ContactController::class, 'store'])
            ->middleware('throttle:contact_form');
    });

/*
|--------------------------------------------------------------------------
| Internal API Routes (CMS → API sync)
|--------------------------------------------------------------------------
|
| Bearer-token authenticated. The CMS is the only intended caller.
|
*/
Route::prefix('internal/v1')
    ->middleware('api_key:internal')
    ->group(function () {
        Route::post('/content/sync',             [ContentSyncController::class, 'sync']);
        Route::delete('/content/sync',           [ContentSyncController::class, 'delete']);
        Route::post('/content/sync-all',         [ContentSyncController::class, 'syncAll']);
        Route::post('/config/sync',              [ConfigSyncController::class, 'sync']);
        Route::post('/deploy/trigger/{siteKey}', [DeployController::class, 'trigger']);
    });
