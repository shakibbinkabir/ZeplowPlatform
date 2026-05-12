<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeployLog extends Model
{
    protected $table = 'deploy_logs';

    protected $fillable = [
        'site_key',
        'trigger_source',
        'status',
        'response_code',
        'response_body',
        'last_error',
    ];
}
