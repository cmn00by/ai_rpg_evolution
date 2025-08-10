<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Attribut;

class AttributeCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Attribut $attribut
    ) {}
}