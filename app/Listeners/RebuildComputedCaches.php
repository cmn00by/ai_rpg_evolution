<?php

namespace App\Listeners;

use App\Events\AttributeCreated;
use App\Events\AttributeUpdated;
use App\Events\AttributeDeleted;
use App\Events\CharacterStatsChanged;
use App\Jobs\RecalculateCharacterStatsBatch;
use App\Models\Personnage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RebuildComputedCaches implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'default';

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
        $this->invalidateAndRecalculateAll($attribut->id, 'created');
    }

    private function handleUpdated($attribut, $event): void
    {
        $this->invalidateAndRecalculateAll($attribut->id, 'updated');
    }

    private function handleDeleted($attribut): void
    {
        // Nettoyer le cache pour cet attribut
        $deletedCacheCount = DB::table('personnage_attributs_cache')
            ->where('attribut_id', $attribut->id)
            ->delete();

        Log::info('Cache entries deleted for attribut', [
            'attribut_id' => $attribut->id,
            'deleted_cache_count' => $deletedCacheCount
        ]);

        // Recalculer tous les personnages car les formules peuvent changer
        $this->invalidateAndRecalculateAll($attribut->id, 'deleted');
    }

    private function invalidateAndRecalculateAll(int $attributId, string $reason): void
    {
        $totalPersonnages = Personnage::count();
        
        if ($totalPersonnages === 0) {
            Log::info('No characters to recalculate', ['attribut_id' => $attributId]);
            return;
        }

        Log::info('Starting cache rebuild', [
            'attribut_id' => $attributId,
            'reason' => $reason,
            'total_characters' => $totalPersonnages
        ]);

        // Invalider le cache existant (optionnel, le job peut écraser)
        if ($reason !== 'deleted') {
            DB::table('personnage_attributs_cache')
                ->where('attribut_id', $attributId)
                ->update(['needs_recalculation' => true]);
        }

        // Dispatcher le job de recalcul par batches
        RecalculateCharacterStatsBatch::dispatchAfterResponse(
            $attributId,
            $reason
        );
    }
}