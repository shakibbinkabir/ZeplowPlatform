<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteConfigResource\Pages;
use App\Models\SiteConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SiteConfigResource extends Resource
{
    protected static ?string $model = SiteConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Site')
                    ->schema([
                        Forms\Components\Select::make('site_id')
                            ->relationship('site', 'name')
                            ->required()
                            ->unique(ignoreRecord: true),
                    ]),

                Forms\Components\Section::make('Navigation')
                    ->schema([
                        Forms\Components\Repeater::make('nav_items')
                            ->required()
                            ->schema([
                                Forms\Components\TextInput::make('label')
                                    ->required(),

                                Forms\Components\TextInput::make('url')
                                    ->required(),

                                Forms\Components\Toggle::make('is_external')
                                    ->default(false),
                            ])
                            ->columns(3),
                    ]),

                Forms\Components\Section::make('Footer')
                    ->schema([
                        Forms\Components\Repeater::make('footer_links')
                            ->schema([
                                Forms\Components\TextInput::make('group_title')
                                    ->required(),

                                Forms\Components\Repeater::make('links')
                                    ->schema([
                                        Forms\Components\TextInput::make('label')
                                            ->required(),

                                        Forms\Components\TextInput::make('url')
                                            ->required(),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\TextInput::make('footer_text')
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Call to Action')
                    ->schema([
                        Forms\Components\TextInput::make('cta_text')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('cta_url')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Contact & Social')
                    ->schema([
                        Forms\Components\KeyValue::make('social_links'),

                        Forms\Components\TextInput::make('contact_email')
                            ->email(),

                        Forms\Components\TextInput::make('contact_phone')
                            ->maxLength(50),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('site.name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('cta_text')
                    ->searchable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiteConfigs::route('/'),
            'create' => Pages\CreateSiteConfig::route('/create'),
            'edit' => Pages\EditSiteConfig::route('/{record}/edit'),
        ];
    }
}
