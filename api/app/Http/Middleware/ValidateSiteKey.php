<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateSiteKey
{
    private const ALLOWED_SITE_KEYS = ['parent', 'narrative', 'logic'];

    public function handle(Request $request, Closure $next): Response
    {
        $siteKey = $request->route('siteKey');

        if (! in_array($siteKey, self::ALLOWED_SITE_KEYS, true)) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return $next($request);
    }
}
