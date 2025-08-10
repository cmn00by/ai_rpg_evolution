<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AchatHistorique extends Model
{
    use HasFactory;

    protected $fillable = [
        'personnage_id',
        'boutique_id',
        'objet_id',
        'qty',
        'unit_price',
        'total_price',
        'type',
        'meta_json',
        'ip_address'
    ];

    protected $casts = [
        'qty' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'meta_json' => 'array'
    ];

    public function personnage(): BelongsTo
    {
        return $this->belongsTo(Personnage::class);
    }

    public function boutique(): BelongsTo
    {
        return $this->belongsTo(Boutique::class);
    }

    public function objet(): BelongsTo
    {
        return $this->belongsTo(Objet::class);
    }

    public function getMeta(string $key, $default = null)
    {
        return data_get($this->meta_json, $key, $default);
    }

    public function setMeta(string $key, $value): void
    {
        $meta = $this->meta_json ?? [];
        data_set($meta, $key, $value);
        $this->meta_json = $meta;
    }

    public function scopePurchases($query)
    {
        return $query->where('type', 'buy');
    }

    public function scopeSales($query)
    {
        return $query->where('type', 'sell');
    }

    public function scopeForPersonnage($query, int $personnageId)
    {
        return $query->where('personnage_id', $personnageId);
    }

    public function scopeForBoutique($query, int $boutiqueId)
    {
        return $query->where('boutique_id', $boutiqueId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    public static function getTotalSpentByPersonnage(int $personnageId, ?string $period = null): float
    {
        $query = static::forPersonnage($personnageId)->purchases();
        
        switch ($period) {
            case 'today':
                $query->today();
                break;
            case 'week':
                $query->thisWeek();
                break;
            case 'month':
                $query->thisMonth();
                break;
        }
        
        return $query->sum('total_price');
    }

    public static function getTotalEarnedByPersonnage(int $personnageId, ?string $period = null): float
    {
        $query = static::forPersonnage($personnageId)->sales();
        
        switch ($period) {
            case 'today':
                $query->today();
                break;
            case 'week':
                $query->thisWeek();
                break;
            case 'month':
                $query->thisMonth();
                break;
        }
        
        return $query->sum('total_price');
    }

    public static function getBoutiqueRevenue(int $boutiqueId, ?string $period = null): float
    {
        $query = static::forBoutique($boutiqueId)->purchases();
        
        switch ($period) {
            case 'today':
                $query->today();
                break;
            case 'week':
                $query->thisWeek();
                break;
            case 'month':
                $query->thisMonth();
                break;
        }
        
        return $query->sum('total_price');
    }
}