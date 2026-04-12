<?php

namespace App\Providers;

use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Project;
use App\Models\SiteConfig;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Observers\BlogPostObserver;
use App\Observers\PageObserver;
use App\Observers\ProjectObserver;
use App\Observers\SiteConfigObserver;
use App\Observers\TeamMemberObserver;
use App\Observers\TestimonialObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Page::observe(PageObserver::class);
        Project::observe(ProjectObserver::class);
        BlogPost::observe(BlogPostObserver::class);
        Testimonial::observe(TestimonialObserver::class);
        TeamMember::observe(TeamMemberObserver::class);
        SiteConfig::observe(SiteConfigObserver::class);
    }
}
