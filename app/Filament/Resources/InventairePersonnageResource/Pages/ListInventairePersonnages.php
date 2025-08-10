<?php

namespace App\Filament\Resources\InventairePersonnageResource\Pages;

use App\Filament\Resources\InventairePersonnageResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventairePersonnages extends ListRecords
{
    protected static string $resource = InventairePersonnageResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
