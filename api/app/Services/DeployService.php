<?php

namespace App\Services;

use App\Models\DeployLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeployService
{
    /**
     * Window during which a duplicate deploy hook for the same site is suppressed.
     * Cloudflare Pages is already building from the previous trigger, so re-firing
     * just wastes a hook call and clutters deploy_logs.
     */
    private const DEBOUNCE_SECONDS = 60;

    public function trigger(string $siteKey, string $triggerSource = 'content_sync'): bool
    {
        $hookUrl = config("services.cloudflare.deploy_hooks.{$siteKey}");

        if (! $hookUrl) {
            Log::warning("No deploy hook configured for site: {$siteKey}");
            return false;
        }

        $debounceKey = "deploy_debounce:{$siteKey}";

        if (Cache::has($debounceKey)) {
            Log::info("Deploy hook debounced for {$siteKey} (trigger: {$triggerSource})");

            DeployLog::create([
                'site_key'       => $siteKey,
                'trigger_source' => $triggerSource,
                'status'         => 'debounced',
            ]);

            return true;
        }

        $log = DeployLog::create([
            'site_key'       => $siteKey,
            'trigger_source' => $triggerSource,
            'status'         => 'triggered',
        ]);

        try {
            $response = Http::timeout(10)->post($hookUrl);

            $log->update([
                'status'        => $response->successful() ? 'success' : 'failed',
                'response_code' => $response->status(),
                'response_body' => substr($response->body(), 0, 500),
            ]);

            if ($response->successful()) {
                Cache::put($debounceKey, true, self::DEBOUNCE_SECONDS);
            }

            return $response->successful();
        } catch (\Throwable $e) {
            $log->update([
                'status'     => 'failed',
                'last_error' => $e->getMessage(),
            ]);

            Log::error("Deploy hook failed for {$siteKey}", [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
