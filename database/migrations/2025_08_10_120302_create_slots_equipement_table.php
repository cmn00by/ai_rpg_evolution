<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('slots_equipement', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('max_per_slot')->default(1); // Ex. 2 anneaux
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('slots_equipement');
    }
};