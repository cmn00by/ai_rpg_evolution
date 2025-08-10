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
        Schema::create('personnage_attributs', function (Blueprint $table) {
            $table->foreignId('personnage_id')->constrained('personnages')->cascadeOnDelete();
            $table->foreignId('attribut_id')->constrained('attributs')->cascadeOnDelete();
            $table->decimal('value', 12, 4)->default(0);

            $table->primary(['personnage_id','attribut_id']);
            $table->index(['attribut_id','personnage_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnage_attributs');
    }
};
