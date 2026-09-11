<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Trip;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\{
    DatePicker,
    DateTimePicker,
    RichEditor,
    TextInput,
    Toggle,
    Section,
    Select
};
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\TripResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TripResource\RelationManagers;

class TripResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->autofocus()->required(),
                TextInput::make('headline'),
                RichEditor::make('summary'),
                RichEditor::make('content'),
                DatePicker::make('start_date'),
                DatePicker::make('end_date'),
                Section::make('Publishing')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'reviewing' => 'Reviewing',
                                'published' => 'Published',
                            ])
                            ->required(),
                        DateTimePicker::make('published_at'),
                    ]),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
            'index' => Pages\ListTrips::route('/'),
            'create' => Pages\CreateTrip::route('/create'),
            'edit' => Pages\EditTrip::route('/{record}/edit'),
        ];
    }
}
