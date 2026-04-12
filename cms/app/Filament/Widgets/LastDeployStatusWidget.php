<?php

namespace App\Filament\Widgets;

use App\Models\Site;
use App\Models\SyncLog;
use Filament\Widgets\Widget;

class LastDeployStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.last-deploy-status-widget';

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $sites = Site::all()->map(function ($site) {
            $lastSync = SyncLog::where('site_key', $site->key)
                ->orderByDesc('created_at')
                ->first();

            $lastSuccess = SyncLog::where('site_key', $site->key)
                ->where('status', 'success')
                ->orderByDesc('synced_at')
                ->first();

            return [
                'name' => $site->name,
                'key' => $site->key,
                'last_sync_status' => $lastSync?->status,
                'last_sync_error' => $lastSync?->last_error,
                'last_success_at' => $lastSuccess?->synced_at?->diffForHumans(),
                'last_success_time' => $lastSuccess?->synced_at?->format('Y-m-d H:i:s'),
            ];
        });

        return ['sites' => $sites];
    }
}
