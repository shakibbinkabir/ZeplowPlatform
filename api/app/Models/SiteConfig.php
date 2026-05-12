<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteConfig extends Model
{
    protected $table = 'site_configs';

    protected $fillable = [
        'site_key',
        'config',
        'synced_at',
    ];

    protected $casts = [
        'config'    => 'array',
        'synced_at' => 'datetime',
    ];
}
