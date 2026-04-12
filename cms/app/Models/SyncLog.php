<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $table = 'sync_logs';

    protected $fillable = [
        'site_key', 'content_type', 'content_slug',
        'status', 'attempt_count', 'last_error', 'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];
}
