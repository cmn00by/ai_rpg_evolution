<?php

namespace App\Filament\Resources\AttributResource\Pages;

use App\Filament\Resources\AttributResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttribut extends EditRecord
{
    protected static string $resource = AttributResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
