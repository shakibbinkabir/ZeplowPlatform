<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiKey
{
    public function handle(Request $request, Closure $next, string $scope = 'internal'): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['error' => 'Missing API key'], 401);
        }

        $apiKey = ApiKey::where('key_hash', hash('sha256', $token))
            ->where('is_active', true)
            ->where('scope', $scope)
            ->first();

        if (! $apiKey) {
            return response()->json(['error' => 'Invalid API key'], 403);
        }

        $apiKey->update(['last_used_at' => now()]);

        return $next($request);
    }
}
