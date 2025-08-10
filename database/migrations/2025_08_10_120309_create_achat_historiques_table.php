<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('achat_historiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnage_id')->constrained()->onDelete('cascade');
            $table->foreignId('boutique_id')->constrained()->onDelete('cascade');
            $table->foreignId('objet_id')->constrained('objets')->onDelete('cascade');
            $table->integer('qty');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->enum('type', ['buy', 'sell']);
            $table->json('meta_json')->nullable(); // solde avant/après, taxe, etc.
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            
            $table->index(['personnage_id', 'type']);
            $table->index(['boutique_id', 'type']);
            $table->index(['created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('achat_historiques');
    }
};