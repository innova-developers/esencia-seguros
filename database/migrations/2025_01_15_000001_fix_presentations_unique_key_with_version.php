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
        Schema::table('presentations', function (Blueprint $table) {
            // Agregar campo version si no existe
            if (!Schema::hasColumn('presentations', 'version')) {
                $table->unsignedInteger('version')->default(1)->after('tipo_entrega');
            }
        });

        // Asignar versiones a presentaciones existentes
        $this->assignVersionsToExistingPresentations();

        // Eliminar la clave única anterior
        Schema::table('presentations', function (Blueprint $table) {
            $table->dropUnique(['codigo_compania', 'cronograma', 'tipo_entrega']);
        });

        // Agregar la nueva clave única que incluye versión
        Schema::table('presentations', function (Blueprint $table) {
            $table->unique(['codigo_compania', 'cronograma', 'tipo_entrega', 'version'], 'presentations_unique_with_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presentations', function (Blueprint $table) {
            // Eliminar la nueva clave única
            $table->dropUnique('presentations_unique_with_version');
        });

        Schema::table('presentations', function (Blueprint $table) {
            // Restaurar la clave única anterior
            $table->unique(['codigo_compania', 'cronograma', 'tipo_entrega']);
        });

        Schema::table('presentations', function (Blueprint $table) {
            // Eliminar campo version si existe
            if (Schema::hasColumn('presentations', 'version')) {
                $table->dropColumn('version');
            }
        });
    }

    /**
     * Asignar versiones a presentaciones existentes
     */
    private function assignVersionsToExistingPresentations(): void
    {
        // Obtener todas las presentaciones agrupadas por clave única
        $presentations = DB::table('presentations')
            ->select('codigo_compania', 'cronograma', 'tipo_entrega')
            ->groupBy('codigo_compania', 'cronograma', 'tipo_entrega')
            ->get();

        foreach ($presentations as $group) {
            // Obtener todas las presentaciones de este grupo ordenadas por fecha de creación
            $groupPresentations = DB::table('presentations')
                ->where('codigo_compania', $group->codigo_compania)
                ->where('cronograma', $group->cronograma)
                ->where('tipo_entrega', $group->tipo_entrega)
                ->orderBy('created_at', 'asc')
                ->get();

            // Asignar versiones secuenciales
            $version = 1;
            foreach ($groupPresentations as $presentation) {
                DB::table('presentations')
                    ->where('id', $presentation->id)
                    ->update(['version' => $version]);
                $version++;
            }
        }
    }
};
