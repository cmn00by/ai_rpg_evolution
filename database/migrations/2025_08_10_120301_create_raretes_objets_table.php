<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('raretes_objets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('order')->default(0);
            $table->string('color_hex', 7); // Format #RRGGBB
            $table->decimal('multiplier', 5, 2)->default(1.00); // Facteur par défaut
            $table->timestamps();
            
            $table->index('order');
        });
    }

    public function down()
    {
        Schema::dropIfExists('raretes_objets');
    }
};