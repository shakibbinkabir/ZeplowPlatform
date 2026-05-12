<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identifies Cloudflare Pages build agents by their X-Build-Token header
 * and flags the request so the public_api rate limiter can apply the higher
 * 300/min tier. Invalid or missing tokens are silently ignored — the
 * request just falls through to the default 60/min limit.
 */
class ResolveBuildAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Build-Token');

        if ($token) {
            $apiKey = ApiKey::where('key_hash', hash('sha256', $token))
                ->where('scope', 'build')
                ->where('is_active', true)
                ->first();

            if ($apiKey) {
                $request->attributes->set('is_build_agent', true);
                $apiKey->update(['last_used_at' => now()]);
            }
        }

        return $next($request);
    }
}
