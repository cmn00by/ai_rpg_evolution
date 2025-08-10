<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class BoutiqueItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'boutique_id',
        'objet_id',
        'stock',
        'price_override',
        'allow_buy',
        'allow_sell',
        'rarity_min',
        'rarity_max',
        'restock_rule',
        'last_restock'
    ];

    protected $casts = [
        'price_override' => 'decimal:2',
        'allow_buy' => 'boolean',
        'allow_sell' => 'boolean',
        'restock_rule' => 'array',
        'last_restock' => 'datetime'
    ];

    public function boutique(): BelongsTo
    {
        return $this->belongsTo(Boutique::class);
    }

    public function objet(): BelongsTo
    {
        return $this->belongsTo(Objet::class);
    }

    public function calculateFinalPrice(string $type = 'buy', ?Personnage $personnage = null): float
    {
        $boutique = $this->boutique;
        $objet = $this->objet->load('rarete');
        
        // Prix de base
        if ($this->price_override) {
            $basePrice = (float) $this->price_override;
        } else {
            $basePrice = $type === 'buy' ? 
                ($objet->buy_price ?? $objet->sell_price * 2) : 
                ($objet->sell_price ?? $objet->buy_price * 0.5);
            
            // Multiplicateur de rareté
            $rarityOrder = $objet->rarete ? $objet->rarete->order : 1;
            $rarityMultiplier = $this->getRarityMultiplier($rarityOrder);
            $basePrice *= $rarityMultiplier;
        }
        
        // Prix dynamiques basés sur le stock
        $basePrice = $this->applyDynamicPricing($basePrice, $type);
        
        if ($type === 'buy') {
            // Ajouter les taxes
            $taxes = $boutique->calculateTaxes($basePrice);
            // Soustraire les remises
            $discount = $boutique->calculateDiscount($basePrice, $personnage);
            return max(0, $basePrice + $taxes - $discount);
        } else {
            // Pour la vente, appliquer une remise (le joueur vend moins cher)
            $sellRate = $boutique->getConfig('sell_rate', 0.7); // 70% du prix d'achat par défaut
            return $basePrice * $sellRate;
        }
    }

    private function getRarityMultiplier(?int $rarity): float
    {
        if ($rarity === null) {
            return 1.0;
        }
        
        $multipliers = [
            1 => 1.0,    // Commun
            2 => 1.5,    // Peu commun
            3 => 2.0,    // Rare
            4 => 3.0,    // Épique
            5 => 5.0     // Légendaire
        ];
        
        return $multipliers[$rarity] ?? 1.0;
    }

    private function applyDynamicPricing(float $basePrice, string $type): float
    {
        $boutique = $this->boutique;
        $dynamicConfig = $boutique->getConfig('dynamic_pricing');
        
        if (!$dynamicConfig || !$dynamicConfig['enabled']) {
            return $basePrice;
        }
        
        // Ajustement basé sur le stock
        $stockThreshold = $dynamicConfig['stock_threshold'] ?? 5;
        $priceIncrease = $dynamicConfig['low_stock_increase'] ?? 0.2; // +20%
        
        if ($this->stock <= $stockThreshold && $type === 'buy') {
            $basePrice *= (1 + $priceIncrease);
        }
        
        return $basePrice;
    }

    public function needsRestock(): bool
    {
        if (!$this->restock_rule) {
            return false;
        }
        
        $rule = $this->restock_rule;
        $frequency = $rule['freq'] ?? 'daily';
        $restockTime = $rule['at'] ?? '03:00';
        
        $now = Carbon::now();
        $lastRestock = $this->last_restock ? Carbon::parse($this->last_restock) : null;
        
        switch ($frequency) {
            case 'hourly':
                return !$lastRestock || $lastRestock->diffInHours($now) >= 1;
            
            case 'daily':
                if (!$lastRestock) {
                    return true;
                }
                
                $todayRestockTime = Carbon::today()->setTimeFromTimeString($restockTime);
                if ($now->gte($todayRestockTime) && $lastRestock->lt($todayRestockTime)) {
                    return true;
                }
                break;
            
            case 'weekly':
                return !$lastRestock || $lastRestock->diffInWeeks($now) >= 1;
        }
        
        return false;
    }

    public function performRestock(): bool
    {
        if (!$this->needsRestock()) {
            return false;
        }
        
        $rule = $this->restock_rule;
        $restockQty = $rule['qty'] ?? 10;
        $stockCap = $rule['cap'] ?? 50;
        
        $newStock = min($this->stock + $restockQty, $stockCap);
        
        $this->update([
            'stock' => $newStock,
            'last_restock' => Carbon::now()
        ]);
        
        // Émettre un événement de réapprovisionnement
        event(new \App\Events\BoutiqueRestocked($this, $newStock - $this->stock));
        
        return true;
    }

    public function scopeAvailableForPurchase($query)
    {
        return $query->where('allow_buy', true)
                    ->where('stock', '>', 0);
    }

    public function scopeAvailableForSale($query)
    {
        return $query->where('allow_sell', true);
    }
}