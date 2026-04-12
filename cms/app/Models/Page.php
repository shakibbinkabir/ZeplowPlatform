<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Page extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'site_id', 'title', 'slug', 'template', 'content',
        'seo_title', 'seo_description', 'is_published', 'published_at', 'sort_order',
    ];

    protected $casts = [
        'content' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('og_image')->singleFile();
    }
}
