<?php

namespace App\Filament\Resources\AchatHistoriqueResource\Pages;

use App\Filament\Resources\AchatHistoriqueResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAchatHistorique extends EditRecord
{
    protected static string $resource = AchatHistoriqueResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
