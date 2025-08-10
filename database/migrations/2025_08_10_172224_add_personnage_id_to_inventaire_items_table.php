<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventaire_items', function (Blueprint $table) {
            $table->foreignId('personnage_id')->nullable()->after('inventaire_id')->constrained('personnages')->onDelete('cascade');
            $table->index('personnage_id');
        });

        // Remplir la colonne personnage_id avec les données existantes
        DB::statement('
            UPDATE inventaire_items 
            SET personnage_id = (
                SELECT personnage_id 
                FROM inventaires 
                WHERE inventaires.id = inventaire_items.inventaire_id
            )
        ');

        // Rendre la colonne non-nullable après l'avoir remplie
        Schema::table('inventaire_items', function (Blueprint $table) {
            $table->foreignId('personnage_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventaire_items', function (Blueprint $table) {
            $table->dropForeign(['personnage_id']);
            $table->dropIndex(['personnage_id']);
            $table->dropColumn('personnage_id');
        });
    }
};
