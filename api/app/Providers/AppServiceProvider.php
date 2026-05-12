<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        // Public API: 60/min default, 300/min for verified Cloudflare build agents.
        // ResolveBuildAgent middleware flips the is_build_agent request attribute.
        RateLimiter::for('public_api', function (Request $request) {
            if ($request->attributes->get('is_build_agent')) {
                return Limit::perMinute(300)->by($request->ip());
            }
            return Limit::perMinute(60)->by($request->ip());
        });

        // Contact form: extra-strict 5/min per IP, layered on top of public_api.
        RateLimiter::for('contact_form', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
