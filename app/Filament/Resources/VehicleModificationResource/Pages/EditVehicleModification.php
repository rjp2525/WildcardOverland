<?php

namespace App\Filament\Resources\VehicleModificationResource\Pages;

use App\Filament\Resources\VehicleModificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehicleModification extends EditRecord
{
    protected static string $resource = VehicleModificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
