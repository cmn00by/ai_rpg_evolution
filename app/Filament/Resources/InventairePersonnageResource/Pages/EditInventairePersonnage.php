<?php

namespace App\Filament\Resources\InventairePersonnageResource\Pages;

use App\Filament\Resources\InventairePersonnageResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventairePersonnage extends EditRecord
{
    protected static string $resource = InventairePersonnageResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
