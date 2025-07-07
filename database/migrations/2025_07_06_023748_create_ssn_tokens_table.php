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
        Schema::create('ssn_tokens', function (Blueprint $table) {
            $table->id();
            $table->text('token')->nullable();
            $table->datetime('expiration')->nullable();
            $table->boolean('is_mock')->default(false);
            $table->string('username')->nullable(); // Usuario que hizo login
            $table->string('cia')->nullable(); // Compañía
            $table->timestamps();
            
            // Índice para búsquedas rápidas
            $table->index(['expiration', 'is_mock']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssn_tokens');
    }
};
