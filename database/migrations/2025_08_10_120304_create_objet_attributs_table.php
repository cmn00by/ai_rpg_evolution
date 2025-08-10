<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('objet_attributs', function (Blueprint $table) {
            $table->foreignId('objet_id')->constrained('objets')->onDelete('cascade');
            $table->foreignId('attribut_id')->constrained('attributs')->onDelete('cascade');
            $table->enum('modifier_type', ['flat', 'percent']);
            $table->decimal('modifier_value', 8, 2);
            $table->timestamps();
            
            // Clé primaire composite
            $table->primary(['objet_id', 'attribut_id', 'modifier_type']);
            
            $table->index(['objet_id']);
            $table->index(['attribut_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('objet_attributs');
    }
};