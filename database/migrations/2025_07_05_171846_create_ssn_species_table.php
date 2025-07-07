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
        Schema::create('ssn_species', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_ssn', 20)->unique(); // Código SSN del activo
            $table->string('tipo_especie', 2); // TP, ON, AC, FF, FC, OP
            $table->string('descripcion', 255);
            $table->string('emisor', 255)->nullable();
            $table->string('serie', 50)->nullable();
            $table->string('moneda', 3)->default('ARS');
            $table->boolean('activo')->default(true);
            $table->json('metadata')->nullable(); // Datos adicionales específicos de la especie
            $table->timestamps();

            $table->index(['tipo_especie', 'activo']);
            $table->index('codigo_ssn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssn_species');
    }
};
