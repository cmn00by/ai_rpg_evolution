<?php

namespace App\Listeners;

use App\Events\AttributeCreated;
use App\Events\AttributeUpdated;
use App\Events\AttributeDeleted;
use App\Models\Classe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SyncAttributeToClasses implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'high';

    public function handle(AttributeCreated|AttributeUpdated|AttributeDeleted $event): void
    {
        $attribut = $event->attribut;
        
        match (true) {
            $event instanceof AttributeCreated => $this->handleCreated($attribut),
            $event instanceof AttributeUpdated => $this->handleUpdated($attribut, $event),
            $event instanceof AttributeDeleted => $this->handleDeleted($attribut),
        };
    }

    private function handleCreated($attribut): void
    {
        $classes = Classe::all();
        $now = now();
        $rows = [];

        foreach ($classes as $classe) {
            $rows[] = [
                'classe_id' => $classe->id,
                'attribut_id' => $attribut->id,
                'base_value' => $attribut->default_value ?? 0,
            ];
        }

        if (!empty($rows)) {
            DB::table('classe_attributs')->upsert(
                $rows,
                ['classe_id', 'attribut_id'],
                ['base_value']
            );
        }

        Log::info('Attribut synced to classes on creation', [
            'attribut_id' => $attribut->id,
            'classes_count' => count($rows)
        ]);
    }

    private function handleUpdated($attribut, $event): void
    {
        // Ajuster les valeurs si nécessaire (ex. caps, bornes)
        if ($attribut->wasChanged(['default_value', 'min_value', 'max_value'])) {
            // Pas de mise à jour nécessaire car pas de timestamps
            Log::info('Attribut class relations updated', [
                'attribut_id' => $attribut->id,
                'changes' => $attribut->getChanges()
            ]);
        }
    }

    private function handleDeleted($attribut): void
    {
        $deletedCount = DB::table('classe_attributs')
            ->where('attribut_id', $attribut->id)
            ->delete();

        Log::info('Attribut class relations deleted', [
            'attribut_id' => $attribut->id,
            'deleted_count' => $deletedCount
        ]);
    }
}