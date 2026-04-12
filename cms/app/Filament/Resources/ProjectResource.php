<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Support\Str;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Content';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General')
                    ->schema([
                        Forms\Components\Select::make('site_id')
                            ->relationship('site', 'name')
                            ->required(),

                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, Forms\Get $get) {
                                return $rule->where('site_id', $get('site_id'));
                            }),

                        Forms\Components\Textarea::make('one_liner')
                            ->required()
                            ->maxLength(500),
                    ]),

                Forms\Components\Section::make('Client Details')
                    ->schema([
                        Forms\Components\TextInput::make('client_name')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('industry')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('url')
                            ->url()
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Case Study')
                    ->schema([
                        Forms\Components\Textarea::make('challenge'),

                        Forms\Components\Textarea::make('solution'),

                        Forms\Components\Textarea::make('outcome'),
                    ]),

                Forms\Components\Section::make('Tech & Tags')
                    ->schema([
                        Forms\Components\TagsInput::make('tech_stack'),

                        Forms\Components\TagsInput::make('tags'),
                    ]),

                Forms\Components\Section::make('Media')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('images')
                            ->collection('images')
                            ->multiple()
                            ->required()
                            ->image()
                            ->maxSize(5120),
                    ]),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('featured')
                            ->default(false),

                        Forms\Components\Toggle::make('is_published')
                            ->default(false),

                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('site.name')
                    ->sortable(),

                Tables\Columns\TextColumn::make('client_name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('industry')
                    ->searchable(),

                Tables\Columns\IconColumn::make('featured')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_published')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('site_id')
                    ->relationship('site', 'name')
                    ->label('Site'),

                Tables\Filters\TernaryFilter::make('is_published'),

                Tables\Filters\TernaryFilter::make('featured'),

                Tables\Filters\SelectFilter::make('industry')
                    ->options(fn () => \App\Models\Project::whereNotNull('industry')
                        ->distinct()
                        ->pluck('industry', 'industry')
                        ->toArray()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
