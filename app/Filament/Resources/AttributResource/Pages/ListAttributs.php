<?php

namespace App\Filament\Resources\AttributResource\Pages;

use App\Filament\Resources\AttributResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttributs extends ListRecords
{
    protected static string $resource = AttributResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
