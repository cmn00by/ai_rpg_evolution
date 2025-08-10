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
        Schema::table('personnage_attributs_cache', function (Blueprint $table) {
            $table->boolean('needs_recalculation')->default(false)->after('final_value');
            $table->timestamp('calculated_at')->nullable()->after('needs_recalculation');
            
            // Modifier le type de final_value pour supporter les décimaux
            $table->decimal('final_value', 10, 4)->default(0)->change();
            
            // Ajouter des index pour les performances
            $table->index(['attribut_id', 'needs_recalculation']);
            $table->index('calculated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personnage_attributs_cache', function (Blueprint $table) {
            $table->dropIndex(['attribut_id', 'needs_recalculation']);
            $table->dropIndex(['calculated_at']);
            $table->dropColumn(['needs_recalculation', 'calculated_at']);
            $table->unsignedBigInteger('final_value')->change();
        });
    }
};
