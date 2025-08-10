<?php

namespace App\Listeners;

use App\Events\ItemEquipped;
use App\Events\ItemUnequipped;
use App\Events\ItemBroken;
use App\Services\CharacterStatsCacheManager;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class InvalidateStatsOnEquipmentChange
{
    protected CharacterStatsCacheManager $cacheManager;

    public function __construct(CharacterStatsCacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            ItemEquipped::class,
            [InvalidateStatsOnEquipmentChange::class, 'handleItemEquipped']
        );

        $events->listen(
            ItemUnequipped::class,
            [InvalidateStatsOnEquipmentChange::class, 'handleItemUnequipped']
        );

        $events->listen(
            ItemBroken::class,
            [InvalidateStatsOnEquipmentChange::class, 'handleItemBroken']
        );
    }

    /**
     * Handle item equipped event
     */
    public function handleItemEquipped(ItemEquipped $event): void
    {
        $this->invalidateAffectedAttributes($event->personnage, $event->item);
        
        Log::info("Stats cache invalidated for character {$event->personnage->name} after equipping {$event->item->objet->name}");
    }

    /**
     * Handle item unequipped event
     */
    public function handleItemUnequipped(ItemUnequipped $event): void
    {
        $this->invalidateAffectedAttributes($event->personnage, $event->item);
        
        Log::info("Stats cache invalidated for character {$event->personnage->name} after unequipping {$event->item->objet->name}");
    }

    /**
     * Handle item broken event
     */
    public function handleItemBroken(ItemBroken $event): void
    {
        $this->invalidateAffectedAttributes($event->personnage, $event->item);
        
        Log::info("Stats cache invalidated for character {$event->personnage->name} after item {$event->item->objet->name} broke");
    }

    /**
     * Invalidate cache for attributes affected by the item
     */
    private function invalidateAffectedAttributes($personnage, $item): void
    {
        // Obtenir tous les attributs modifiés par cet objet
        $affectedAttributs = $item->objet->attributModifiers()->with('attribut')->get();
        
        foreach ($affectedAttributs as $modifier) {
            $this->cacheManager->invalidateCache($personnage, $modifier->attribut);
        }
        
        // Invalider aussi les attributs dérivés qui pourraient dépendre des attributs modifiés
        $this->invalidateDerivedAttributes($personnage, $affectedAttributs);
    }

    /**
     * Invalidate derived attributes that might depend on modified attributes
     */
    private function invalidateDerivedAttributes($personnage, $modifiedAttributes): void
    {
        // Obtenir tous les attributs dérivés
        $derivedAttributes = \App\Models\Attribut::where('type', 'derived')->get();
        
        foreach ($derivedAttributes as $derivedAttr) {
            // Pour simplifier, on invalide tous les attributs dérivés
            // Une optimisation future pourrait analyser les dépendances
            $this->cacheManager->invalidateCache($personnage, $derivedAttr);
        }
    }
}