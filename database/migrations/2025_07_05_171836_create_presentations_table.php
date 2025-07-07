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
        Schema::create('presentations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('codigo_compania', 4);
            $table->string('cronograma', 7); // YYYY-WW para semanal, YYYY-MM para mensual
            $table->enum('tipo_entrega', ['Semanal', 'Mensual']);
            $table->enum('estado', [
                'VACIO',
                'CARGADO', 
                'PRESENTADO',
                'RECTIFICACION_PENDIENTE',
                'A_RECTIFICAR'
            ])->default('VACIO');
            
            // Campos para tracking
            $table->string('ssn_response_id')->nullable(); // ID de respuesta de SSN
            $table->json('ssn_response_data')->nullable(); // Respuesta completa de SSN
            $table->timestamp('presented_at')->nullable(); // Cuándo se presentó
            $table->timestamp('confirmed_at')->nullable(); // Cuándo se confirmó
            $table->timestamp('rectification_requested_at')->nullable(); // Cuándo se solicitó rectificación
            $table->timestamp('rectification_approved_at')->nullable(); // Cuándo se aprobó rectificación
            
            // Campos para archivos
            $table->string('original_file_path')->nullable(); // Ruta del archivo Excel original
            $table->string('json_file_path')->nullable(); // Ruta del JSON generado
            $table->string('original_filename')->nullable(); // Nombre original del archivo
            
            // Campos de validación
            $table->json('validation_errors')->nullable(); // Errores de validación
            $table->text('notes')->nullable(); // Notas adicionales
            
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['codigo_compania', 'cronograma', 'tipo_entrega']);
            $table->index(['estado', 'tipo_entrega']);
            $table->index('presented_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presentations');
    }
};
