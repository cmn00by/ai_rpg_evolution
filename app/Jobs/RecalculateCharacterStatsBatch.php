<?php

namespace App\Jobs;

use App\Models\Personnage;
use App\Models\Attribut;
use App\Events\CharacterStatsChanged;
use App\Services\CharacterStatsCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class RecalculateCharacterStatsBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $backoff = [30, 60, 120]; // Backoff exponentiel

    public function __construct(
        public int $attributId,
        public string $reason,
        public int $batchSize = 1000
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $startTime = microtime(true);
        $processedCount = 0;
        $affectedPersonnageIds = collect();

        Log::info('Starting character stats recalculation batch', [
            'attribut_id' => $this->attributId,
            'reason' => $this->reason,
            'batch_size' => $this->batchSize
        ]);

        // Si l'attribut a été supprimé, ne pas recalculer
        if ($this->reason === 'deleted') {
            Log::info('Skipping recalculation for deleted attribute', [
                'attribut_id' => $this->attributId
            ]);
            return;
        }

        try {
            // Traiter par chunks pour éviter l'explosion mémoire
            Personnage::chunk($this->batchSize, function (Collection $personnages) use (&$processedCount, &$affectedPersonnageIds) {
                $this->processPersonnageBatch($personnages, $affectedPersonnageIds);
                $processedCount += $personnages->count();
                
                Log::debug('Processed batch', [
                    'processed_count' => $processedCount,
                    'batch_size' => $personnages->count()
                ]);
            });

            $duration = microtime(true) - $startTime;
            $throughput = $processedCount > 0 ? $processedCount / $duration : 0;

            Log::info('Character stats recalculation completed', [
                'attribut_id' => $this->attributId,
                'reason' => $this->reason,
                'processed_count' => $processedCount,
                'duration_seconds' => round($duration, 2),
                'throughput_per_second' => round($throughput, 2)
            ]);

            // Émettre l'événement de changement des stats par lots
            if ($affectedPersonnageIds->isNotEmpty()) {
                event(new CharacterStatsChanged($affectedPersonnageIds, $this->attributId));
            }

        } catch (\Exception $e) {
            Log::error('Character stats recalculation failed', [
                'attribut_id' => $this->attributId,
                'reason' => $this->reason,
                'error' => $e->getMessage(),
                'processed_count' => $processedCount
            ]);
            
            throw $e;
        }
    }

    private function processPersonnageBatch(Collection $personnages, Collection &$affectedPersonnageIds): void
    {
        $calculator = app(CharacterStatsCalculator::class);
        $attribut = Attribut::find($this->attributId);
        
        if (!$attribut) {
            Log::warning('Attribut not found for recalculation', ['attribut_id' => $this->attributId]);
            return;
        }
        
        $cacheEntries = [];
        $now = now();

        foreach ($personnages as $personnage) {
            $finalValue = $calculator->calculateFinalValue($personnage, $attribut);
            
            $cacheEntries[] = [
                'personnage_id' => $personnage->id,
                'attribut_id' => $this->attributId,
                'final_value' => $finalValue,
                'needs_recalculation' => false,
                'calculated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            
            $affectedPersonnageIds->push($personnage->id);
        }

        // Upsert en une seule requête pour la performance
        if (!empty($cacheEntries)) {
            DB::table('personnage_attributs_cache')->upsert(
                $cacheEntries,
                ['personnage_id', 'attribut_id'],
                ['final_value', 'needs_recalculation', 'calculated_at', 'updated_at']
            );
        }
    }

    // Les méthodes de calcul ont été déplacées vers CharacterStatsCalculator
}