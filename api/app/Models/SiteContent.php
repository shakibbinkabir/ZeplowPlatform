<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteContent extends Model
{
    protected $table = 'site_content';

    protected $fillable = [
        'site_key',
        'content_type',
        'slug',
        'data',
        'published_at',
        'synced_at',
    ];

    protected $casts = [
        'data'         => 'array',
        'published_at' => 'datetime',
        'synced_at'    => 'datetime',
    ];
}
