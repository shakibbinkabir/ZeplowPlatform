<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\SiteConfig;
use App\Services\DeployService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ConfigSyncController extends Controller
{
    public function __construct(private DeployService $deployService) {}

    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_key' => 'required|string|max:50',
            'config'   => 'required|array',
        ]);

        SiteConfig::updateOrCreate(
            ['site_key' => $validated['site_key']],
            [
                'config'    => $validated['config'],
                'synced_at' => now(),
            ]
        );

        Cache::forget("site:{$validated['site_key']}:config");

        $this->deployService->trigger($validated['site_key'], 'config_sync');

        return response()->json([
            'status'   => 'synced',
            'site_key' => $validated['site_key'],
        ]);
    }
}
