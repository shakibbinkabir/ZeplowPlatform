<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\DeployService;
use Illuminate\Http\JsonResponse;

class DeployController extends Controller
{
    public function __construct(private DeployService $deployService) {}

    public function trigger(string $siteKey): JsonResponse
    {
        $success = $this->deployService->trigger($siteKey, 'manual');

        return response()->json([
            'status'   => $success ? 'triggered' : 'failed',
            'site_key' => $siteKey,
        ], $success ? 200 : 500);
    }
}
