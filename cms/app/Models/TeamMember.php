<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TeamMember extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'team_members';

    protected $fillable = [
        'site_id', 'name', 'role', 'bio', 'photo', 'linkedin',
        'email', 'is_founder', 'sort_order',
    ];

    protected $casts = [
        'is_founder' => 'boolean',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }
}
