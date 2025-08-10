<?php

namespace App\Filament\Resources\SlotEquipementResource\Pages;

use App\Filament\Resources\SlotEquipementResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSlotEquipement extends EditRecord
{
    protected static string $resource = SlotEquipementResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
