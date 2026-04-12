<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Project extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'site_id', 'title', 'slug', 'one_liner', 'client_name', 'industry',
        'url', 'challenge', 'solution', 'outcome', 'tech_stack', 'images',
        'tags', 'featured', 'is_published', 'sort_order',
    ];

    protected $casts = [
        'tech_stack' => 'array',
        'images' => 'array',
        'tags' => 'array',
        'featured' => 'boolean',
        'is_published' => 'boolean',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(400)->height(300)->sharpen(10);

        $this->addMediaConversion('medium')
            ->width(800)->height(600);

        $this->addMediaConversion('large')
            ->width(1600)->height(1200);

        $this->addMediaConversion('large-webp')
            ->width(1600)->height(1200)->format('webp');
    }
}
