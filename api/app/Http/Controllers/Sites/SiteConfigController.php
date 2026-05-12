<?php

namespace App\Http\Controllers\Sites;

use App\Http\Controllers\Controller;
use App\Models\SiteConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SiteConfigController extends Controller
{
    public function show(string $siteKey): JsonResponse
    {
        $cacheKey = "site:{$siteKey}:config";

        $data = Cache::remember($cacheKey, 3600, function () use ($siteKey) {
            $config = SiteConfig::where('site_key', $siteKey)->firstOrFail();

            return array_merge(
                ['site_key' => $config->site_key],
                $config->config
            );
        });

        return response()->json($data)->header('Cache-Control', 'public, max-age=3600');
    }
}
