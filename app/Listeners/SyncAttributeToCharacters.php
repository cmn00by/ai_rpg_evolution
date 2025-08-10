<?php

namespace App\Listeners;

use App\Events\AttributeCreated;
use App\Events\AttributeUpdated;
use App\Events\AttributeDeleted;
use App\Models\Personnage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SyncAttributeToCharacters implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'high';

    public function handle(AttributeCreated|AttributeUpdated|AttributeDeleted $event): void
    {
        $attribut = $event->attribut;
        
        // Ne pas créer d'entrées pivot pour les attributs dérivés
        if ($attribut->type === 'derived') {
            Log::debug('Skipping character sync for derived attribute', [
                'attribut_id' => $attribut->id,
                'type' => $attribut->type
            ]);
            return;
        }
        
        match (true) {
            $event instanceof AttributeCreated => $this->handleCreated($attribut),
            $event instanceof AttributeUpdated => $this->handleUpdated($attribut, $event),
            $event instanceof AttributeDeleted => $this->handleDeleted($attribut),
        };
    }

    private function handleCreated($attribut): void
    {
        $personnages = Personnage::all();
        $now = now();
        $rows = [];

        foreach ($personnages as $personnage) {
            $rows[] = [
                'personnage_id' => $personnage->id,
                'attribut_id' => $attribut->id,
                'value' => 0, // Valeur par défaut neutre
            ];
        }

        if (!empty($rows)) {
            DB::table('personnage_attributs')->upsert(
                $rows,
                ['personnage_id', 'attribut_id'],
                ['value']
            );
        }

        Log::info('Attribut synced to characters on creation', [
            'attribut_id' => $attribut->id,
            'characters_count' => count($rows)
        ]);
    }

    private function handleUpdated($attribut, $event): void
    {
        // Marquer les relations comme mises à jour
        if ($attribut->wasChanged(['type', 'min_value', 'max_value'])) {
            // Pas de mise à jour nécessaire car pas de timestamps
            Log::info('Attribut character relations updated', [
                'attribut_id' => $attribut->id,
                'changes' => $attribut->getChanges()
            ]);
        }
    }

    private function handleDeleted($attribut): void
    {
        $deletedCount = DB::table('personnage_attributs')
            ->where('attribut_id', $attribut->id)
            ->delete();

        Log::info('Attribut character relations deleted', [
            'attribut_id' => $attribut->id,
            'deleted_count' => $deletedCount
        ]);
    }
}