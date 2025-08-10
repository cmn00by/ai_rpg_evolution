<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inventaire_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventaire_id')->constrained('inventaires')->onDelete('cascade');
            $table->foreignId('objet_id')->constrained('objets')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->integer('durability')->nullable();
            $table->boolean('is_equipped')->default(false);
            $table->timestamps();
            
            // Index composés pour les performances
            $table->index(['inventaire_id', 'objet_id']);
            $table->index(['inventaire_id', 'is_equipped']);
            $table->index(['objet_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventaire_items');
    }
};