<?php

namespace App\Providers;

use App\Bio\LinkHopShortlinks;
use App\Bio\SafetyAdapter;
use App\Http\Controllers\Api\HopBioController;
use App\Http\Controllers\BrandAssetController;
use App\Services\GeoIpService;
use App\Services\UserAgentService;
use App\Support\VisitorHash;
use Hop\Auth\HopEvents;
use Hop\Bio\Contracts\GeoLookup;
use Hop\Bio\Contracts\ShortlinkCreator;
use Hop\Bio\Contracts\UrlSafety;
use Hop\Bio\Contracts\UserAgentParser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // What the hop/bio package may call in this app (Hop\Bio\Contracts); nothing else.
        $this->app->bind(UrlSafety::class, SafetyAdapter::class);
        $this->app->bind(GeoLookup::class, GeoIpService::class);
        $this->app->bind(UserAgentParser::class, UserAgentService::class);
        $this->app->bind(ShortlinkCreator::class, LinkHopShortlinks::class);
    }

    public function boot(): void
    {
        URL::defaults(['v' => BrandAssetController::version()]); // brand image URLs change with the images
        URL::forceRootUrl(config('app.url'));
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
        if ($proxies = config('app.trusted_proxies')) {
            TrustProxies::at(match ($proxies) {
                '*' => '*',
                'cloudflare' => [...config('app.cloudflare_ips'), '127.0.0.1', '::1'],
                default => array_map('trim', explode(',', $proxies)),
            });
        }
        // Account events from the hub (POST /hop/events and /api/hop/events). hop/auth already ends the sessions and rotates
        // the remember token on session.revoked and account.deleted; this is what BioHop adds. A suspension needs nothing
        // more: CheckSuspended signs the account out on its next request (account.status has no handler here).
        HopEvents::on('account.deleted', function (array $data): void {
            app(HopBioController::class)->purgeAccountFiles((int) ($data['sub'] ?? $data['hop_id'] ?? 0), (array) ($data['bio_uploads'] ?? []));
        });
        // Defined here (not in routes) so they exist when routes are cached.
        RateLimiter::for('shorten', fn (Request $request) => $request->user()
            ? Limit::perMinute(30)->by('user:'.$request->user()->id)
            : Limit::perMinute(10)->by(VisitorHash::make($request->ip())));
        // Flood guard only: bio click limits normally just stop counting (see PublicBioController::click).
        RateLimiter::for('bio-click', fn (Request $request) => Limit::perMinute(300)->by('bio-click:'.VisitorHash::make($request->ip())));
        // The shared tables the hub and LinkHop own exist in production; tests create them here.
        if ($this->app->runningUnitTests()) {
            $this->loadMigrationsFrom(database_path('migrations/testing'));
        }
    }
}
