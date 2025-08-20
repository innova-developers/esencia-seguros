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
        // Agregar campo version si no existe
        if (!Schema::hasColumn('presentations', 'version')) {
            Schema::table('presentations', function (Blueprint $table) {
                $table->unsignedInteger('version')->default(1)->after('tipo_entrega');
            });
        }

        // Asignar versiones a presentaciones existentes
        $this->assignVersionsToExistingPresentations();

        // Eliminar todas las claves únicas existentes (excepto PRIMARY)
        $this->dropAllUniqueKeys();

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
        try {
            // Eliminar la nueva clave única
            Schema::table('presentations', function (Blueprint $table) {
                $table->dropUnique('presentations_unique_with_version');
            });

            // Restaurar la clave única anterior
            Schema::table('presentations', function (Blueprint $table) {
                $table->unique(['codigo_compania', 'cronograma', 'tipo_entrega']);
            });
        } catch (\Exception $e) {
            // Si hay error, continuar
        }

        // Eliminar campo version si existe
        if (Schema::hasColumn('presentations', 'version')) {
            Schema::table('presentations', function (Blueprint $table) {
                $table->dropColumn('version');
            });
        }
    }

    /**
     * Eliminar todas las claves únicas existentes
     */
    private function dropAllUniqueKeys(): void
    {
        try {
            // Obtener todas las claves únicas de la tabla
            $indexes = DB::select("
                SELECT INDEX_NAME 
                FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'presentations' 
                AND NON_UNIQUE = 0
                AND INDEX_NAME != 'PRIMARY'
            ");

            foreach ($indexes as $index) {
                $indexName = $index->INDEX_NAME;
                DB::statement("ALTER TABLE presentations DROP INDEX `{$indexName}`");
            }
        } catch (\Exception $e) {
            // Si hay error, continuar
        }
    }

    /**
     * Asignar versiones a presentaciones existentes
     */
    private function assignVersionsToExistingPresentations(): void
    {
        try {
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
        } catch (\Exception $e) {
            // Si hay error, continuar
        }
    }
};
