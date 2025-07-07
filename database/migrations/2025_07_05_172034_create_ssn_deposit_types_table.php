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
        Schema::create('ssn_deposit_types', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 3)->unique(); // Código del tipo de depósito
            $table->string('descripcion', 255);
            $table->text('detalle')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['codigo', 'activo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssn_deposit_types');
    }
};
