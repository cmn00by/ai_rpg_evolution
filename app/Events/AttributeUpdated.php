<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Attribut;

class AttributeUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Attribut $attribut,
        public array $originalAttributes = []
    ) {}

    public function hasImpactingChanges(): bool
    {
        $impactingFields = ['type', 'default_value', 'min_value', 'max_value', 'is_visible'];
        
        foreach ($impactingFields as $field) {
            if ($this->attribut->wasChanged($field)) {
                return true;
            }
        }
        
        return false;
    }
}