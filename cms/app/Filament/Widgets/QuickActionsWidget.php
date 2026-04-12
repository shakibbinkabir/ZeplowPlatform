<?php

namespace App\Filament\Widgets;

use App\Models\Site;
use App\Services\SyncService;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.quick-actions-widget';

    protected int | string | array $columnSpan = 'full';

    public function resyncAllAction(): Action
    {
        return Action::make('resyncAll')
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
