<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleModificationResource\Pages;
use App\Filament\Resources\VehicleModificationResource\RelationManagers;
use App\Models\VehicleModification;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Pelmered\FilamentMoneyField\Forms\Components\MoneyInput;

class VehicleModificationResource extends Resource
{
    protected static ?string $model = VehicleModification::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                MoneyInput::make('cost'),
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
