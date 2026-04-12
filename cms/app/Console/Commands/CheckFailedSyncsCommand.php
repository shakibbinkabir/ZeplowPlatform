<?php

namespace App\Console\Commands;

use App\Models\SyncLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckFailedSyncsCommand extends Command
{
    protected $signature = 'sync:check-failed';
    protected $description = 'Check for failed syncs in the last 7 days and send a summary email';

    public function handle(): int
    {
        $failedSyncs = SyncLog::where('status', 'failed')
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        if ($failedSyncs->isEmpty()) {
            $this->info('No failed syncs in the last 7 days.');
            return self::SUCCESS;
        }

        $summary = $failedSyncs->map(function ($log) {
            return "[{$log->created_at}] {$log->site_key}/{$log->content_type}/{$log->content_slug} — {$log->last_error}";
        })->implode("\n");

        Mail::raw(
            "Failed Syncs Summary (last 7 days):\n\n{$summary}\n\nTotal: {$failedSyncs->count()} failed sync(s).\n\nUse the 'Resync All' action in the CMS dashboard to retry.",
            function ($message) use ($failedSyncs) {
                $message->to('hello@zeplow.com')
                    ->subject("Zeplow CMS: {$failedSyncs->count()} Failed Sync(s) This Week");
            }
        );

        $this->warn("{$failedSyncs->count()} failed sync(s) found. Summary email sent to hello@zeplow.com.");
        return self::SUCCESS;
    }
}
