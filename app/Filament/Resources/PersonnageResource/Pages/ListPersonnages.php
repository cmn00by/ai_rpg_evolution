<?php

namespace App\Filament\Resources\PersonnageResource\Pages;

use App\Filament\Resources\PersonnageResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPersonnages extends ListRecords
{
    protected static string $resource = PersonnageResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
