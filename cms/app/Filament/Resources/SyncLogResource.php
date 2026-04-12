<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SyncLogResource\Pages;
use App\Models\SyncLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SyncLogResource extends Resource
{
    protected static ?string $model = SyncLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Settings';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('site_key')
                    ->searchable(),

                Tables\Columns\TextColumn::make('content_type')
                    ->searchable(),

                Tables\Columns\TextColumn::make('content_slug')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'success',
                        'warning' => 'pending',
                        'danger' => 'failed',
                    ]),

                Tables\Columns\TextColumn::make('attempt_count')
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_error')
                    ->limit(50),

                Tables\Columns\TextColumn::make('synced_at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'success' => 'Success',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                    ]),

                Tables\Filters\SelectFilter::make('site_key')
                    ->options(fn () => SyncLog::query()->distinct()->pluck('site_key', 'site_key')->toArray()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSyncLogs::route('/'),
        ];
    }
}
