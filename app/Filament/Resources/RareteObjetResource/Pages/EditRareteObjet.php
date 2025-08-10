<?php

namespace App\Filament\Resources\RareteObjetResource\Pages;

use App\Filament\Resources\RareteObjetResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRareteObjet extends EditRecord
{
    protected static string $resource = RareteObjetResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
