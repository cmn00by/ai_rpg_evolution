<?php

namespace App\Filament\Resources\PersonnageResource\Pages;

use App\Filament\Resources\PersonnageResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPersonnage extends EditRecord
{
    protected static string $resource = PersonnageResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
