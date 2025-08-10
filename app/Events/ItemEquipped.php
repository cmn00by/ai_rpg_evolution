<?php

namespace App\Events;

use App\Models\Personnage;
use App\Models\InventaireItem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ItemEquipped
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Personnage $personnage;
    public InventaireItem $item;

    /**
     * Create a new event instance.
     */
    public function __construct(Personnage $personnage, InventaireItem $item)
    {
        $this->personnage = $personnage;
        $this->item = $item;
    }
}