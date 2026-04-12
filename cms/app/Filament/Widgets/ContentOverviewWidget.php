<?php

namespace App\Filament\Widgets;

use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $publishedPages = Page::where('is_published', true)->count();
        $draftPages = Page::where('is_published', false)->count();

        return [
            Stat::make('Pages', $publishedPages + $draftPages)
                ->description("{$publishedPages} published, {$draftPages} drafts")
                ->icon('heroicon-o-document-text'),
            Stat::make('Projects', Project::count())
                ->description(Project::where('is_published', true)->count() . ' published')
                ->icon('heroicon-o-briefcase'),
            Stat::make('Blog Posts', BlogPost::count())
                ->description(BlogPost::where('is_published', true)->count() . ' published')
                ->icon('heroicon-o-pencil-square'),
        ];
    }
}
