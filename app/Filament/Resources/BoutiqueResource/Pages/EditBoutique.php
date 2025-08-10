<?php

namespace App\Filament\Resources\BoutiqueResource\Pages;

use App\Filament\Resources\BoutiqueResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBoutique extends EditRecord
{
    protected static string $resource = BoutiqueResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
