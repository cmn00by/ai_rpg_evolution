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
        Schema::table('personnages', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('reputation');
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personnages', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_active']);
            $table->dropColumn('is_active');
        });
    }
};