<?php

namespace App\Filament\Resources\SiteConfigResource\Pages;

use App\Filament\Resources\SiteConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSiteConfig extends EditRecord
{
    protected static string $resource = SiteConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
