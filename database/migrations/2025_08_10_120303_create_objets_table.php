<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('objets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('rarete_id')->constrained('raretes_objets')->onDelete('cascade');
            $table->foreignId('slot_id')->nullable()->constrained('slots_equipement')->onDelete('set null');
            $table->boolean('stackable')->default(false);
            $table->integer('base_durability')->nullable();
            $table->decimal('buy_price', 10, 2)->default(0);
            $table->decimal('sell_price', 10, 2)->default(0);
            $table->timestamps();
            
            $table->index(['rarete_id', 'slot_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('objets');
    }
};