<?php

namespace App\Filament\Resources\SlotEquipementResource\Pages;

use App\Filament\Resources\SlotEquipementResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSlotEquipements extends ListRecords
{
    protected static string $resource = SlotEquipementResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
