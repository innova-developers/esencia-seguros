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
        Schema::create('ssn_banks', function (Blueprint $table) {
            $table->id();
            $table->string('bic', 11)->unique(); // Código BIC del banco
            $table->string('nombre_banco', 255);
            $table->string('codigo_banco', 10)->nullable(); // Código interno del banco
            $table->string('pais', 3)->default('AR');
            $table->boolean('activo')->default(true);
            $table->json('metadata')->nullable(); // Datos adicionales del banco
            $table->timestamps();

            $table->index(['bic', 'activo']);
            $table->index('nombre_banco');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssn_banks');
    }
};
