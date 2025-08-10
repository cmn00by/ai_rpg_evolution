<?php

namespace App\Services;

use App\Models\Boutique;
use App\Models\BoutiqueItem;
use App\Models\Personnage;
use App\Models\Objet;
use App\Models\AchatHistorique;
use App\Models\InventaireItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\BoutiqueException;

class BoutiqueService
{
    public function purchaseItem(
        Personnage $personnage,
        Boutique $boutique,
        BoutiqueItem $boutiqueItem,
        int $quantity = 1,
        ?string $ipAddress = null
    ): AchatHistorique {
        return DB::transaction(function () use ($personnage, $boutique, $boutiqueItem, $quantity, $ipAddress) {
            // Verrouiller les lignes pour éviter les conditions de course
            $boutiqueItem = BoutiqueItem::where('id', $boutiqueItem->id)
                ->lockForUpdate()
                ->first();
            
            $personnage = Personnage::where('id', $personnage->id)
                ->lockForUpdate()
                ->first();
            
            // Vérifications préliminaires
            $this->validatePurchase($personnage, $boutique, $boutiqueItem, $quantity);
            
            $objet = $boutiqueItem->objet;
            $unitPrice = $boutiqueItem->calculateFinalPrice('buy', $personnage);
            $totalPrice = $unitPrice * $quantity;
            
            // Vérifier le solde
            if ($personnage->gold < $totalPrice) {
                throw new BoutiqueException("Solde insuffisant. Requis: {$totalPrice}, Disponible: {$personnage->gold}");
            }
            
            // Sauvegarder l'état avant transaction
            $soldeAvant = $personnage->gold;
            
            // Débiter l'or
            $personnage->gold -= $totalPrice;
            $personnage->save();
            
            // Ajouter à l'inventaire
            $this->addToInventory($personnage, $objet, $quantity);
            
            // Décrémenter le stock
            $boutiqueItem->stock -= $quantity;
            $boutiqueItem->save();
            
            // Créer l'historique
            $achatHistorique = AchatHistorique::create([
                'personnage_id' => $personnage->id,
                'boutique_id' => $boutique->id,
                'objet_id' => $objet->id,
                'qty' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'type' => 'buy',
                'meta_json' => [
                    'solde_avant' => $soldeAvant,
                    'solde_apres' => $personnage->gold,
                    'taxes' => $boutique->calculateTaxes($unitPrice * $quantity),
                    'remises' => $boutique->calculateDiscount($unitPrice * $quantity, $personnage),
                    'prix_base' => $unitPrice,
                    'timestamp' => now()->toISOString()
                ],
                'ip_address' => $ipAddress
            ]);
            
            // Log de l'achat
            Log::info('Achat effectué', [
                'personnage_id' => $personnage->id,
                'boutique_id' => $boutique->id,
                'objet_id' => $objet->id,
                'quantity' => $quantity,
                'total_price' => $totalPrice,
                'achat_id' => $achatHistorique->id
            ]);
            
            return $achatHistorique;
        });
    }
    
    public function sellItem(
        Personnage $personnage,
        Boutique $boutique,
        Objet $objet,
        int $quantity = 1,
        ?string $ipAddress = null
    ): AchatHistorique {
        return DB::transaction(function () use ($personnage, $boutique, $objet, $quantity, $ipAddress) {
            // Verrouiller les lignes
            $personnage = Personnage::where('id', $personnage->id)
                ->lockForUpdate()
                ->first();
            
            // Vérifier si la boutique accepte la vente de cet objet
            $boutiqueItem = BoutiqueItem::where('boutique_id', $boutique->id)
                ->where('objet_id', $objet->id)
                ->where('allow_sell', true)
                ->lockForUpdate()
                ->first();
            
            if (!$boutiqueItem) {
                throw new BoutiqueException("Cette boutique n'accepte pas la vente de cet objet.");
            }
            
            // Vérifications préliminaires
            $this->validateSale($personnage, $boutique, $objet, $quantity);
            
            $unitPrice = $boutiqueItem->calculateFinalPrice('sell', $personnage);
            $totalPrice = $unitPrice * $quantity;
            
            // Sauvegarder l'état avant transaction
            $soldeAvant = $personnage->gold;
            
            // Retirer de l'inventaire
            $this->removeFromInventory($personnage, $objet, $quantity);
            
            // Créditer l'or
            $personnage->gold += $totalPrice;
            $personnage->save();
            
            // Incrémenter le stock (si modèle "boucle fermée")
            if ($boutique->getConfig('closed_loop', false)) {
                $boutiqueItem->stock += $quantity;
                $boutiqueItem->save();
            }
            
            // Créer l'historique
            $achatHistorique = AchatHistorique::create([
                'personnage_id' => $personnage->id,
                'boutique_id' => $boutique->id,
                'objet_id' => $objet->id,
                'qty' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'type' => 'sell',
                'meta_json' => [
                    'solde_avant' => $soldeAvant,
                    'solde_apres' => $personnage->gold,
                    'prix_base' => $unitPrice,
                    'timestamp' => now()->toISOString()
                ],
                'ip_address' => $ipAddress
            ]);
            
            // Log de la vente
            Log::info('Vente effectuée', [
                'personnage_id' => $personnage->id,
                'boutique_id' => $boutique->id,
                'objet_id' => $objet->id,
                'quantity' => $quantity,
                'total_price' => $totalPrice,
                'achat_id' => $achatHistorique->id
            ]);
            
            return $achatHistorique;
        });
    }
    
    private function validatePurchase(
        Personnage $personnage,
        Boutique $boutique,
        BoutiqueItem $boutiqueItem,
        int $quantity
    ): void {
        // Vérifier que la boutique est active
        if (!$boutique->is_active) {
            throw new BoutiqueException("Cette boutique est fermée.");
        }
        
        // Vérifier que l'achat est autorisé
        if (!$boutiqueItem->allow_buy) {
            throw new BoutiqueException("Cet objet n'est pas disponible à l'achat.");
        }
        
        // Vérifier le stock
        if ($boutiqueItem->stock < $quantity) {
            throw new BoutiqueException("Stock insuffisant. Disponible: {$boutiqueItem->stock}, Demandé: {$quantity}");
        }
        
        // Vérifier que l'objet est autorisé dans cette boutique
        if (!$boutique->isObjectAllowed($boutiqueItem->objet)) {
            throw new BoutiqueException("Cet objet n'est pas autorisé dans cette boutique.");
        }
        
        // Vérifier les limites de transaction
        $this->checkTransactionLimits($personnage, $boutique, $boutiqueItem->objet, $quantity, 'buy');
    }
    
    private function validateSale(
        Personnage $personnage,
        Boutique $boutique,
        Objet $objet,
        int $quantity
    ): void {
        // Vérifier que la boutique est active
        if (!$boutique->is_active) {
            throw new BoutiqueException("Cette boutique est fermée.");
        }
        
        // Vérifier que l'objet est autorisé dans cette boutique
        if (!$boutique->isObjectAllowed($objet)) {
            throw new BoutiqueException("Cette boutique n'accepte pas ce type d'objet.");
        }
        
        // Vérifier les limites de transaction
        $this->checkTransactionLimits($personnage, $boutique, $objet, $quantity, 'sell');
    }
    
    private function checkTransactionLimits(
        Personnage $personnage,
        Boutique $boutique,
        Objet $objet,
        int $quantity,
        string $type
    ): void {
        $limits = $boutique->getConfig('limits', []);
        
        // Limite par transaction
        $maxPerTransaction = $limits['max_per_transaction'] ?? null;
        if ($maxPerTransaction && $quantity > $maxPerTransaction) {
            throw new BoutiqueException("Quantité maximale par transaction: {$maxPerTransaction}");
        }
        
        // Limite quotidienne
        $maxPerDay = $limits['max_per_day'] ?? null;
        if ($maxPerDay) {
            $todayTotal = AchatHistorique::forPersonnage($personnage->id)
                ->where('boutique_id', $boutique->id)
                ->where('objet_id', $objet->id)
                ->where('type', $type)
                ->today()
                ->sum('qty');
            
            if (($todayTotal + $quantity) > $maxPerDay) {
                throw new BoutiqueException("Limite quotidienne atteinte. Maximum: {$maxPerDay}, Déjà utilisé: {$todayTotal}");
            }
        }
    }
    
    private function addToInventory(Personnage $personnage, Objet $objet, int $quantity): void
    {
        $inventaire = $personnage->inventaire;
        if (!$inventaire) {
            $inventaire = $personnage->inventaire()->create();
        }

        $inventaireItem = InventaireItem::where('inventaire_id', $inventaire->id)
            ->where('objet_id', $objet->id)
            ->where('is_equipped', false)
            ->first();
        
        if ($inventaireItem && $objet->stackable) {
            // Fusionner avec l'item existant
            $inventaireItem->quantity += $quantity;
            $inventaireItem->save();
        } else {
            // Créer un nouvel item d'inventaire
            InventaireItem::create([
                'inventaire_id' => $inventaire->id,
                'objet_id' => $objet->id,
                'quantity' => $quantity,
                'durability' => $objet->base_durability,
                'is_equipped' => false
            ]);
        }
    }
    
    private function removeFromInventory(Personnage $personnage, Objet $objet, int $quantity): void
    {
        $inventaire = $personnage->inventaire;
        if (!$inventaire) {
            throw new BoutiqueException("Le personnage n'a pas d'inventaire.");
        }

        $inventaireItems = InventaireItem::where('inventaire_id', $inventaire->id)
            ->where('objet_id', $objet->id)
            ->where('is_equipped', false)
            ->orderBy('created_at')
            ->get();
        
        $totalAvailable = $inventaireItems->sum('quantity');
        
        if ($totalAvailable < $quantity) {
            throw new BoutiqueException("Quantité insuffisante dans l'inventaire. Disponible: {$totalAvailable}, Demandé: {$quantity}");
        }
        
        $remainingToRemove = $quantity;
        
        foreach ($inventaireItems as $item) {
            if ($remainingToRemove <= 0) {
                break;
            }
            
            if ($item->quantity <= $remainingToRemove) {
                $remainingToRemove -= $item->quantity;
                $item->delete();
            } else {
                $item->quantity -= $remainingToRemove;
                $item->save();
                $remainingToRemove = 0;
            }
        }
    }
    
    public function performAutomaticRestock(): int
    {
        $restockedCount = 0;
        
        $boutiqueItems = BoutiqueItem::whereNotNull('restock_rule')
            ->with(['boutique', 'objet'])
            ->get();
        
        foreach ($boutiqueItems as $item) {
            try {
                if ($item->performRestock()) {
                    $restockedCount++;
                    
                    Log::info('Réapprovisionnement automatique', [
                        'boutique_item_id' => $item->id,
                        'boutique_id' => $item->boutique_id,
                        'objet_id' => $item->objet_id,
                        'new_stock' => $item->stock
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Erreur lors du réapprovisionnement', [
                    'boutique_item_id' => $item->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $restockedCount;
    }
}