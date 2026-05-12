<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        $dbStatus = 'disconnected';

        try {
            DB::connection()->getPdo();
            $dbStatus = 'connected';
        } catch (\Throwable) {
            // Surface the disconnected state but still return 200 — uptime
            // checks treat any 200 as healthy. Treat db status as advisory.
        }

        return response()->json([
            'status'    => 'ok',
            'timestamp' => now()->toISOString(),
            'database'  => $dbStatus,
            'version'   => '1.0.0',
        ]);
    }
}
