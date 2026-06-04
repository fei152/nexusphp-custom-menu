<?php

namespace App\Filament\Resources\System;

use App\Filament\Resources\System\CustomMenuItemResource\Pages\ManageCustomMenuItems;
use App\Http\Middleware\Locale;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use NexusPlugin\CustomMenu\Models\MenuItem;

class CustomMenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-bars-3';

    protected static string | \UnitEnum | null $navigationGroup = 'System';

    protected static ?int $navigationSort = 11;

    public static function getNavigationLabel(): string
    {
        return __('label.menu.label');
    }

    public static function getBreadcrumb(): string
    {
        return self::getNavigationLabel();
    }

    private static function buildLocaleSchema(string $name): array
    {
        $schema = [];

        foreach (Locale::$languageMaps as $lang => $locale) {
            $schema[] = TextInput::make("$name.$lang")
                ->label($lang)
                ->required()
                ->maxLength(100);
        }

        return $schema;
    }

    public static function form(Schema $schema): Schema
    {
        $textLocaleSchema = self::buildLocaleSchema('text');

        return $schema
            ->components([
                Section::make(__('label.menu_item.text'))
                    ->schema($textLocaleSchema)
                    ->columns(count($textLocaleSchema)),
                TextInput::make('url')
                    ->label(__('label.menu_item.url'))
                    ->required()
                    ->maxLength(500),
                Select::make('parent_id')
                    ->label(__('label.menu_item.parent_id'))
                    ->options(fn ($record = null) => MenuItem::parentOptions($record?->id))
                    ->default(0)
                    ->required(),
                Select::make('target')
                    ->label(__('label.menu_item.target'))
                    ->options(MenuItem::targetOptions())
                    ->default(MenuItem::TARGET_SELF)
                    ->required(),
                Select::make('min_class')
                    ->label(__('label.menu_item.min_class'))
                    ->options(User::listClass())
                    ->default(User::CLASS_PEASANT)
                    ->required(),
                TextInput::make('sort')
                    ->label(__('label.priority'))
                    ->numeric()
                    ->default(0)
                    ->helperText(__('label.priority_help')),
                TextInput::make('style')
                    ->label(__('label.menu_item.style'))
                    ->default('')
                    ->dehydrateStateUsing(fn ($state) => $state ?? '')
                    ->maxLength(500),
                Toggle::make('enabled')
                    ->label(__('label.enabled'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('display_text')
                    ->label(__('label.menu_item.menu'))
                    ->searchable(query: fn (Builder $query, string $search) => $query->where('text', 'like', "%{$search}%")),
                TextColumn::make('parent_text')
                    ->label(__('label.menu_item.parent_id'))
                    ->state(fn (MenuItem $record) => $record->parent_id ? $record->parent?->display_text : null)
                    ->placeholder('--'),
                TextColumn::make('url')
                    ->label(__('label.menu_item.url'))
                    ->searchable(),
                TextColumn::make('min_class_text')
                    ->label(__('label.menu_item.min_class')),
                TextColumn::make('sort')
                    ->label(__('label.priority'))
                    ->sortable(),
                IconColumn::make('enabled')
                    ->label(__('label.enabled'))
                    ->boolean(),
            ])
            ->defaultSort('sort', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('parent')
            ->orderByDesc('sort')
            ->orderBy('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCustomMenuItems::route('/'),
        ];
    }
}
