<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleModificationResource\Pages;
use App\Models\VehicleModification;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VehicleModificationResource extends Resource
{
    protected static ?string $model = VehicleModification::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                /**
                 * name
                 * vendor
                 * purchased from
                 * description
                 * purchase date
                 * install date
                 * cost
                 * url
                 */
                TextInput::make('name')
                    ->required(),
                TextInput::make('vendor'),
                TextInput::make('purchased_from'),
                TextInput::make('description'),
                DatePicker::make('purchase_date'),
                DatePicker::make('install_date'),
                /**
                 * `cost` is stored as an integer number of minor units (cents)
                 * but is entered and displayed in major units (dollars).
                 */
                TextInput::make('cost')
                    ->numeric()
                    ->inputMode('decimal')
                    ->step(0.01)
                    ->minValue(0)
                    ->prefix('$')
                    ->formatStateUsing(fn (?int $state): ?string => filled($state)
                        ? number_format($state / 100, 2, '.', '')
                        : null)
                    ->dehydrateStateUsing(fn (?string $state): ?int => filled($state)
                        ? (int) round(((float) $state) * 100)
                        : null),
                TextInput::make('url')
                    ->url(),
                Toggle::make('shown_on_timeline')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicleModifications::route('/'),
            'create' => Pages\CreateVehicleModification::route('/create'),
            'edit' => Pages\EditVehicleModification::route('/{record}/edit'),
        ];
    }
}
