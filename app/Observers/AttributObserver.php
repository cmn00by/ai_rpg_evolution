<?php

namespace App\Observers;

use App\Models\Attribut;
use App\Events\AttributeCreated;
use App\Events\AttributeUpdated;
use App\Events\AttributeDeleted;
use Illuminate\Support\Facades\Log;

class AttributObserver
{
    public function created(Attribut $attribut): void
    {
        Log::info('Attribut created', ['id' => $attribut->id, 'name' => $attribut->name]);
        
        event(new AttributeCreated($attribut));
    }

    public function updated(Attribut $attribut): void
    {
        $originalAttributes = $attribut->getOriginal();
        $event = new AttributeUpdated($attribut, $originalAttributes);
        
        // Ne déclencher la cascade que si des champs impactants ont changé
        if ($event->hasImpactingChanges()) {
            Log::info('Attribut updated with impacting changes', [
                'id' => $attribut->id,
                'name' => $attribut->name,
                'changes' => $attribut->getChanges()
            ]);
            
            event($event);
        } else {
            Log::debug('Attribut updated without impacting changes', [
                'id' => $attribut->id,
                'changes' => $attribut->getChanges()
            ]);
        }
    }

    public function deleted(Attribut $attribut): void
    {
        Log::info('Attribut deleted', ['id' => $attribut->id, 'name' => $attribut->name]);
        
        event(new AttributeDeleted($attribut));
    }

    public function restored(Attribut $attribut): void
    {
        Log::info('Attribut restored', ['id' => $attribut->id, 'name' => $attribut->name]);
        
        // Traiter comme une création pour restaurer les relations
        event(new AttributeCreated($attribut));
    }
}