<?php

namespace App\Filament\Resources\InventairePersonnageResource\Pages;

use App\Filament\Resources\InventairePersonnageResource;
use App\Models\Personnage;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateInventairePersonnage extends CreateRecord
{
    protected static string $resource = InventairePersonnageResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Récupérer l'inventaire_id du personnage sélectionné
        if (isset($data['personnage_id'])) {
            $personnage = Personnage::find($data['personnage_id']);

            if ($personnage) {
                // Créer un inventaire pour le personnage s'il n'existe pas
                $inventaire = $personnage->inventaire ?? $personnage->inventaire()->create();

                $data['inventaire_id'] = $inventaire->id;
            }
        }
        
        // Renommer 'quantite' en 'quantity' pour correspondre au modèle
        if (isset($data['quantite'])) {
            $data['quantity'] = $data['quantite'];
            unset($data['quantite']);
        }
        
        // Renommer 'durability_current' en 'durability' pour correspondre au modèle
        if (isset($data['durability_current'])) {
            $data['durability'] = $data['durability_current'];
            unset($data['durability_current']);
        }
        
        return $data;
    }
}
