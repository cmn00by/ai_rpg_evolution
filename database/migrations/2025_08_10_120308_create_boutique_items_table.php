<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('boutique_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boutique_id')->constrained()->onDelete('cascade');
            $table->foreignId('objet_id')->constrained('objets')->onDelete('cascade');
            $table->integer('stock')->default(0);
            $table->decimal('price_override', 10, 2)->nullable();
            $table->boolean('allow_buy')->default(true);
            $table->boolean('allow_sell')->default(false);
            $table->integer('rarity_min')->nullable();
            $table->integer('rarity_max')->nullable();
            $table->json('restock_rule')->nullable(); // {"freq":"daily", "at":"03:00", "qty":10, "cap":50}
            $table->timestamp('last_restock')->nullable();
            $table->timestamps();
            
            $table->unique(['boutique_id', 'objet_id']);
            $table->index(['boutique_id', 'allow_buy']);
            $table->index(['boutique_id', 'allow_sell']);
            $table->index(['stock']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('boutique_items');
    }
};