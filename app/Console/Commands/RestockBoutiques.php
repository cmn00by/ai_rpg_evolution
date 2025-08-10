<?php

namespace App\Console\Commands;

use App\Services\BoutiqueService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RestockBoutiques extends Command
{
    protected $signature = 'boutiques:restock';
    protected $description = 'Effectue le réapprovisionnement automatique des boutiques';

    public function __construct(private BoutiqueService $boutiqueService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Début du réapprovisionnement automatique des boutiques...');
        
        try {
            $restockedCount = $this->boutiqueService->performAutomaticRestock();
            
            $this->info("Réapprovisionnement terminé. {$restockedCount} articles réapprovisionnés.");
            
            Log::info('Réapprovisionnement automatique terminé', [
                'restocked_count' => $restockedCount,
                'timestamp' => now()->toISOString()
            ]);
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Erreur lors du réapprovisionnement: ' . $e->getMessage());
            
            Log::error('Erreur lors du réapprovisionnement automatique', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return self::FAILURE;
        }
    }
}