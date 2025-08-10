<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Boutique extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'config_json',
        'is_active'
    ];

    protected $casts = [
        'config_json' => 'array',
        'is_active' => 'boolean'
    ];

    public function boutiqueItems(): HasMany
    {
        return $this->hasMany(BoutiqueItem::class);
    }

    public function achatHistoriques(): HasMany
    {
        return $this->hasMany(AchatHistorique::class);
    }

    public function getConfig(string $key, $default = null)
    {
        return data_get($this->config_json, $key, $default);
    }

    public function setConfig(string $key, $value): void
    {
        $config = $this->config_json ?? [];
        data_set($config, $key, $value);
        $this->config_json = $config;
    }

    public function isObjectAllowed(Objet $objet): bool
    {
        $config = $this->config_json ?? [];
        
        // Vérifier les slots autorisés
        if (isset($config['allowed_slots']) && !empty($config['allowed_slots'])) {
            if (!in_array($objet->slot->slug ?? null, $config['allowed_slots'])) {
                return false;
            }
        }
        
        // Vérifier les raretés autorisées
        if (isset($config['allowed_rarities']) && !empty($config['allowed_rarities'])) {
            if (!in_array($objet->rarete->slug ?? null, $config['allowed_rarities'])) {
                return false;
            }
        }
        
        // Vérifier la blacklist
        if (isset($config['blacklist']) && in_array($objet->id, $config['blacklist'])) {
            return false;
        }
        
        // Vérifier la whitelist (si définie, seuls les objets whitelistés sont autorisés)
        if (isset($config['whitelist']) && !empty($config['whitelist'])) {
            return in_array($objet->id, $config['whitelist']);
        }
        
        return true;
    }

    public function calculateTaxes(float $basePrice): float
    {
        $taxRate = $this->getConfig('tax_rate', 0);
        return $basePrice * ($taxRate / 100);
    }

    public function calculateDiscount(float $basePrice, ?Personnage $personnage = null): float
    {
        $discountRate = $this->getConfig('discount_rate', 0);
        $totalDiscount = $basePrice * ($discountRate / 100);
        
        // Remise basée sur la réputation du personnage (optionnel)
        if ($personnage && isset($this->config_json['reputation_discount'])) {
            $reputationRate = $this->getConfig('reputation_discount.rate', 0);
            $maxReputation = $this->getConfig('reputation_discount.max_reputation', 100);
            $reputation = min($personnage->reputation ?? 0, $maxReputation);
            $reputationDiscount = $basePrice * ($reputation * $reputationRate / 100);
            $totalDiscount += $reputationDiscount;
        }
        
        return $totalDiscount;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}