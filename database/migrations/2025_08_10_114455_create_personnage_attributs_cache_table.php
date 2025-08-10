<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('personnage_attributs_cache', function (Blueprint $table) {
            $table->foreignId('personnage_id')->constrained('personnages')->cascadeOnDelete();
            $table->foreignId('attribut_id')->constrained('attributs')->cascadeOnDelete();
            $table->unsignedBigInteger('final_value'); // int final après arrondi
            $table->timestamps();

            $table->primary(['personnage_id','attribut_id']);
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnage_attributs_cache');
    }
};
