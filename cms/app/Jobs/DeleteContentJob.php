<?php

namespace App\Jobs;

use App\Models\SyncLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeleteContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        private int $syncLogId,
        private string $siteKey,
        private string $contentType,
        private string $slug,
    ) {}

    public function handle(): void
    {
        $apiUrl = config('services.zeplow_api.url');
        $apiKey = config('services.zeplow_api.key');

        $log = SyncLog::find($this->syncLogId);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept' => 'application/json',
                ])
                ->delete("{$apiUrl}/internal/v1/content/sync", [
                    'site_key' => $this->siteKey,
                    'content_type' => $this->contentType,
                    'slug' => $this->slug,
                ]);

            if ($response->successful()) {
                $log->update([
                    'status' => 'success',
                    'attempt_count' => $this->attempts(),
                    'synced_at' => now(),
                ]);
                return;
            }

            $log->update([
                'attempt_count' => $this->attempts(),
                'last_error' => "HTTP {$response->status()}: {$response->body()}",
            ]);

            throw new \RuntimeException("API returned {$response->status()}");

        } catch (\Exception $e) {
            $log->update([
                'attempt_count' => $this->attempts(),
                'last_error' => $e->getMessage(),
                'status' => $this->attempts() >= $this->tries ? 'failed' : 'pending',
            ]);

            Log::error("Content delete sync failed (attempt {$this->attempts()}/{$this->tries})", [
                'site_key' => $this->siteKey,
                'content_type' => $this->contentType,
                'slug' => $this->slug,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
