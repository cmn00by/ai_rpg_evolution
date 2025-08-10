<?php

namespace App\Events;

use App\Models\BoutiqueItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BoutiqueRestocked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public BoutiqueItem $boutiqueItem;
    public int $restockedQuantity;

    public function __construct(BoutiqueItem $boutiqueItem, int $restockedQuantity)
    {
        $this->boutiqueItem = $boutiqueItem;
        $this->restockedQuantity = $restockedQuantity;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('boutique.' . $this->boutiqueItem->boutique_id);
    }

    public function broadcastWith()
    {
        return [
            'boutique_item_id' => $this->boutiqueItem->id,
            'objet_id' => $this->boutiqueItem->objet_id,
            'new_stock' => $this->boutiqueItem->stock,
            'restocked_quantity' => $this->restockedQuantity,
            'timestamp' => now()->toISOString()
        ];
    }
}