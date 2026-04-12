<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Site extends Model
{
    protected $fillable = [
        'name', 'key', 'domain', 'tagline', 'description', 'seo_defaults',
    ];

    protected $casts = [
        'seo_defaults' => 'array',
    ];

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function config(): HasOne
    {
        return $this->hasOne(SiteConfig::class);
    }
}
