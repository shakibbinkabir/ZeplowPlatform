<?php

namespace App\Filament\Actions;

use App\Models\Site;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ResyncAllAction
{
    public static function make(): Action
    {
        return Action::make('resync_all')
            ->label('Resync All Content')
            ->icon('heroicon-o-arrow-path')
            ->requiresConfirmation()
            ->form([
                \Filament\Forms\Components\Select::make('site_id')
                    ->label('Site')
                    ->options(Site::pluck('name', 'id'))
                    ->required(),
            ])
            ->action(function (array $data) {
                $site = Site::findOrFail($data['site_id']);
                $total = 0;

                foreach ($site->pages()->where('is_published', true)->get() as $page) {
                    $page->touch();
                    $total++;
                }

                foreach ($site->projects()->where('is_published', true)->get() as $project) {
                    $project->touch();
                    $total++;
                }

                foreach ($site->blogPosts()->where('is_published', true)->get() as $post) {
                    $post->touch();
                    $total++;
                }

                foreach ($site->testimonials()->where('is_published', true)->get() as $testimonial) {
                    $testimonial->touch();
                    $total++;
                }

                foreach ($site->teamMembers()->get() as $member) {
                    $member->touch();
                    $total++;
                }

                if ($site->config) {
                    $site->config->touch();
                    $total++;
                }

                Notification::make()
                    ->title("Resync complete: {$total} items synced for {$site->name}")
                    ->success()
                    ->send();
            });
    }
}
