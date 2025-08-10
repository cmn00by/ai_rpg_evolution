<?php

namespace App\Events;

use App\Models\Personnage;
use App\Models\Objet;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryMerged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Personnage $personnage;
    public Objet $objet;
    public int $quantity;

    /**
     * Create a new event instance.
     */
    public function __construct(Personnage $personnage, Objet $objet, int $quantity)
    {
        $this->personnage = $personnage;
        $this->objet = $objet;
        $this->quantity = $quantity;
    }
}