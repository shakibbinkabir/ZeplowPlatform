<?php

namespace App\Services;

use App\Jobs\SyncContentJob;
use App\Jobs\SyncConfigJob;
use App\Jobs\DeleteContentJob;
use App\Models\SyncLog;

class SyncService
{
    public function syncContent(
        string $siteKey,
        string $contentType,
        string $slug,
        array $data,
        ?string $publishedAt = null
    ): void {
        $log = SyncLog::create([
            'site_key' => $siteKey,
            'content_type' => $contentType,
            'content_slug' => $slug,
            'status' => 'pending',
        ]);

        SyncContentJob::dispatch($log->id, $siteKey, $contentType, $slug, $data, $publishedAt);
    }

    public function syncConfig(string $siteKey, array $configData): void
    {
        $log = SyncLog::create([
            'site_key' => $siteKey,
            'content_type' => 'site_config',
            'content_slug' => $siteKey,
            'status' => 'pending',
        ]);

        SyncConfigJob::dispatch($log->id, $siteKey, $configData);
    }

    public function deleteContent(string $siteKey, string $contentType, string $slug): void
    {
        $log = SyncLog::create([
            'site_key' => $siteKey,
            'content_type' => $contentType,
            'content_slug' => $slug,
            'status' => 'pending',
        ]);

        DeleteContentJob::dispatch($log->id, $siteKey, $contentType, $slug);
    }
}
