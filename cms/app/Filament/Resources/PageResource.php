<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use App\Models\Site;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Content';

    public static function form(Form $form): Form
    {
        return $form
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

                Forms\Components\Select::make('template')
                    ->required()
                    ->options([
                        'home' => 'Home',
                        'about' => 'About',
                        'services' => 'Services',
                        'work' => 'Work',
                        'process' => 'Process',
                        'insights' => 'Insights',
                        'contact' => 'Contact',
                        'ventures' => 'Ventures',
                        'careers' => 'Careers',
                        'default' => 'Default',
                    ]),

                Forms\Components\Repeater::make('content')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options([
                                'hero' => 'Hero Banner',
                                'text' => 'Text Section',
                                'cards' => 'Card Grid',
                                'cta' => 'Call to Action',
                                'image' => 'Single Image',
                                'gallery' => 'Image Gallery',
                                'testimonials' => 'Testimonials',
                                'team' => 'Team Members',
                                'projects' => 'Project Grid',
                                'stats' => 'Statistics',
                                'divider' => 'Divider',
                                'raw_html' => 'Raw HTML',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('data', [])),

                        Forms\Components\Group::make()
                            ->statePath('data')
                            ->schema(fn (Forms\Get $get) => match ($get('type')) {
                                'hero' => static::heroFields(),
                                'text' => static::textFields(),
                                'cards' => static::cardsFields(),
                                'cta' => static::ctaFields(),
                                'image' => static::imageFields(),
                                'gallery' => static::galleryFields(),
                                'testimonials' => static::testimonialsFields(),
                                'team' => static::teamFields(),
                                'projects' => static::projectsFields(),
                                'stats' => static::statsFields(),
                                'divider' => static::dividerFields(),
                                'raw_html' => static::rawHtmlFields(),
                                default => [],
                            }),
                    ])
                    ->columnSpanFull()
                    ->collapsible()
                    ->reorderableWithButtons()
                    ->itemLabel(fn (array $state): ?string => ($state['type'] ?? null)
                        ? ucfirst($state['type']) . ': ' . ($state['data']['heading'] ?? $state['data']['alt_text'] ?? '')
                        : null
                    ),

                Forms\Components\Section::make('SEO')
                    ->schema([
                        Forms\Components\TextInput::make('seo_title')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('seo_description')
                            ->maxLength(500),

                        Forms\Components\SpatieMediaLibraryFileUpload::make('og_image')
                            ->collection('og_image'),
                    ])
                    ->collapsed(),

                Forms\Components\Toggle::make('is_published')
                    ->default(false),

                Forms\Components\DateTimePicker::make('published_at'),

                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('site.name')
                    ->sortable(),

                Tables\Columns\TextColumn::make('template')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('site_id')
                    ->relationship('site', 'name'),

                Tables\Filters\TernaryFilter::make('is_published'),

                Tables\Filters\SelectFilter::make('template')
                    ->options([
                        'home' => 'Home',
                        'about' => 'About',
                        'services' => 'Services',
                        'work' => 'Work',
                        'process' => 'Process',
                        'insights' => 'Insights',
                        'contact' => 'Contact',
                        'ventures' => 'Ventures',
                        'careers' => 'Careers',
                        'default' => 'Default',
                    ]),
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
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }

    // ── Content Block Field Definitions ─────────────────────────────────

    protected static function heroFields(): array
    {
        return [
            Forms\Components\TextInput::make('heading')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('subheading')
                ->maxLength(255),

            Forms\Components\TextInput::make('cta_text')
                ->maxLength(100)
                ->requiredWith('cta_url'),

            Forms\Components\TextInput::make('cta_url')
                ->maxLength(500)
                ->requiredWith('cta_text'),

            Forms\Components\ColorPicker::make('background_color')
                ->regex('/^#[0-9A-Fa-f]{6}$/'),
        ];
    }

    protected static function textFields(): array
    {
        return [
            Forms\Components\TextInput::make('heading')
                ->maxLength(255),

            Forms\Components\RichEditor::make('body')
                ->required()
                ->columnSpanFull(),
        ];
    }

    protected static function cardsFields(): array
    {
        return [
            Forms\Components\TextInput::make('heading')
                ->maxLength(255),

            Forms\Components\Repeater::make('cards')
                ->required()
                ->minItems(1)
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('description')
                        ->required()
                        ->maxLength(1000),
                    Forms\Components\TextInput::make('link_text')
                        ->maxLength(100),
                    Forms\Components\TextInput::make('link_url')
                        ->maxLength(500),
                ])
                ->columnSpanFull(),
        ];
    }

    protected static function ctaFields(): array
    {
        return [
            Forms\Components\TextInput::make('heading')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('description')
                ->maxLength(500),

            Forms\Components\TextInput::make('button_text')
                ->required()
                ->maxLength(100),

            Forms\Components\TextInput::make('button_url')
                ->required()
                ->maxLength(500),

            Forms\Components\Select::make('style')
                ->options([
                    'primary' => 'Primary',
                    'secondary' => 'Secondary',
                ])
                ->required(),
        ];
    }

    protected static function imageFields(): array
    {
        return [
            Forms\Components\FileUpload::make('image')
                ->required()
                ->image()
                ->maxSize(5120),

            Forms\Components\TextInput::make('alt_text')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('caption')
                ->maxLength(255),

            Forms\Components\Toggle::make('full_width'),
        ];
    }

    protected static function galleryFields(): array
    {
        return [
            Forms\Components\Repeater::make('images')
                ->required()
                ->minItems(1)
                ->schema([
                    Forms\Components\FileUpload::make('image')
                        ->required()
                        ->image()
                        ->maxSize(5120),
                    Forms\Components\TextInput::make('alt_text')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('caption')
                        ->maxLength(255),
                ])
                ->columnSpanFull(),
        ];
    }

    protected static function testimonialsFields(): array
    {
        return [
            Forms\Components\TextInput::make('heading')
                ->maxLength(255),

            Forms\Components\Toggle::make('use_all')
                ->live(),

            Forms\Components\Select::make('selected_ids')
                ->multiple()
                ->options(fn () => Testimonial::pluck('name', 'id'))
                ->hidden(fn (Forms\Get $get) => $get('use_all')),
        ];
    }

    protected static function teamFields(): array
    {
        return [
            Forms\Components\TextInput::make('heading')
                ->maxLength(255),

            Forms\Components\Toggle::make('use_all')
                ->live(),

            Forms\Components\Select::make('selected_ids')
                ->multiple()
                ->options(fn () => TeamMember::pluck('name', 'id'))
                ->hidden(fn (Forms\Get $get) => $get('use_all')),
        ];
    }

    protected static function projectsFields(): array
    {
        return [
            Forms\Components\TextInput::make('heading')
                ->maxLength(255),

            Forms\Components\TextInput::make('count')
                ->numeric(),

            Forms\Components\Toggle::make('featured_only'),
        ];
    }

    protected static function statsFields(): array
    {
        return [
            Forms\Components\Repeater::make('stats')
                ->required()
                ->minItems(1)
                ->schema([
                    Forms\Components\TextInput::make('number')
                        ->required()
                        ->maxLength(50),
                    Forms\Components\TextInput::make('label')
                        ->required()
                        ->maxLength(100),
                    Forms\Components\TextInput::make('suffix')
                        ->maxLength(20),
                ])
                ->columnSpanFull(),
        ];
    }

    protected static function dividerFields(): array
    {
        return [
            Forms\Components\Select::make('style')
                ->options([
                    'line' => 'Line',
                    'space' => 'Space',
                    'gradient' => 'Gradient',
                ])
                ->required(),
        ];
    }

    protected static function rawHtmlFields(): array
    {
        return [
            Forms\Components\Textarea::make('html')
                ->required()
                ->maxLength(10000)
                ->columnSpanFull(),
        ];
    }
}
