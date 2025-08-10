<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('boutiques', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('config_json')->nullable(); // slots/raretés, blacklist/whitelist, taxes, remises
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['is_active']);
            $table->index(['slug']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('boutiques');
    }
};