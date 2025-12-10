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
        Schema::table('weekly_operations', function (Blueprint $table) {
            // Asegurar que los campos comunes sean nullable para permitir plazos fijos
            // que no tienen estos campos
            $table->string('tipo_especie', 2)->nullable()->change();
            $table->string('codigo_especie', 20)->nullable()->change();
            $table->decimal('cant_especies', 20, 6)->nullable()->change();
            $table->enum('tipo_valuacion', ['T', 'V'])->nullable()->change();
            $table->string('fecha_movimiento', 8)->nullable()->change();
            $table->string('fecha_liquidacion', 8)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_operations', function (Blueprint $table) {
            // Revertir cambios (aunque en realidad estos campos ya deberían ser nullable)
            $table->string('tipo_especie', 2)->nullable(false)->change();
            $table->string('codigo_especie', 20)->nullable(false)->change();
            $table->decimal('cant_especies', 20, 6)->nullable(false)->change();
            $table->enum('tipo_valuacion', ['T', 'V'])->nullable(false)->change();
            $table->string('fecha_movimiento', 8)->nullable(false)->change();
            $table->string('fecha_liquidacion', 8)->nullable(false)->change();
        });
    }
};
