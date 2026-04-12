<?php

namespace App\Filament\Resources\SiteConfigResource\Pages;

use App\Filament\Resources\SiteConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSiteConfigs extends ListRecords
{
    protected static string $resource = SiteConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
