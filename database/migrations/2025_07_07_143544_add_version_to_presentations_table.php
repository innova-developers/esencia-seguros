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
        Schema::table('presentations', function (Blueprint $table) {
            // Agregar campo version
            $table->unsignedInteger('version')->default(1)->after('tipo_entrega');
            
            // Eliminar la restricción única existente
            $table->dropUnique(['codigo_compania', 'cronograma', 'tipo_entrega']);
            
            // Crear nueva restricción única que incluya version
            $table->unique(['codigo_compania', 'cronograma', 'tipo_entrega', 'version'], 'presentations_unique_with_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presentations', function (Blueprint $table) {
            // Eliminar la nueva restricción única
            $table->dropUnique('presentations_unique_with_version');
            
            // Restaurar la restricción única original
            $table->unique(['codigo_compania', 'cronograma', 'tipo_entrega']);
            
            // Eliminar el campo version
            $table->dropColumn('version');
        });
    }
};
