<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteConfig extends Model
{
    protected $table = 'site_configs';

    protected $fillable = [
        'site_id', 'nav_items', 'footer_links', 'footer_text',
        'cta_text', 'cta_url', 'social_links', 'contact_email', 'contact_phone',
    ];

    protected $casts = [
        'nav_items' => 'array',
        'footer_links' => 'array',
        'social_links' => 'array',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
