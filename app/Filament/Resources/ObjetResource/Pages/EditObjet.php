<?php

namespace App\Filament\Resources\ObjetResource\Pages;

use App\Filament\Resources\ObjetResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditObjet extends EditRecord
{
    protected static string $resource = ObjetResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
