<?php

namespace App\Filament\Resources\VehicleModificationResource\Pages;

use App\Filament\Resources\VehicleModificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicleModifications extends ListRecords
{
    protected static string $resource = VehicleModificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
