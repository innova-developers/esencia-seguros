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
        Schema::create('weekly_operations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('presentation_id');
            $table->enum('tipo_operacion', ['C', 'V', 'J', 'P']); // Compra, Venta, Canje, Plazo Fijo
            
            // Campos comunes para todos los tipos
            $table->string('tipo_especie', 2)->nullable(); // TP, ON, AC, FF, FC, OP
            $table->string('codigo_especie', 20)->nullable();
            $table->decimal('cant_especies', 20, 6)->nullable(); // 14 enteros + 6 decimales para FCI
            $table->string('codigo_afectacion', 3)->nullable();
            $table->enum('tipo_valuacion', ['T', 'V'])->nullable(); // Técnico, Mercado
            $table->string('fecha_movimiento', 8)->nullable(); // DDMMYYYY
            $table->string('fecha_liquidacion', 8)->nullable(); // DDMMYYYY
            
            // Campos específicos para COMPRA
            $table->decimal('precio_compra', 8, 2)->nullable();
            
            // Campos específicos para VENTA
            $table->string('fecha_pase_vt', 8)->nullable(); // DDMMYYYY
            $table->decimal('precio_pase_vt', 8, 2)->nullable();
            $table->decimal('precio_venta', 8, 2)->nullable();
            
            // Campos específicos para CANJE (Especie A)
            $table->string('tipo_especie_a', 2)->nullable();
            $table->string('codigo_especie_a', 20)->nullable();
            $table->decimal('cant_especies_a', 20, 6)->nullable();
            $table->string('codigo_afectacion_a', 3)->nullable();
            $table->enum('tipo_valuacion_a', ['T', 'V'])->nullable();
            $table->string('fecha_vt_a', 8)->nullable(); // DDMMYYYY
            $table->decimal('precio_vt_a', 8, 2)->nullable();
            
            // Campos específicos para CANJE (Especie B)
            $table->string('tipo_especie_b', 2)->nullable();
            $table->string('codigo_especie_b', 20)->nullable();
            $table->decimal('cant_especies_b', 20, 6)->nullable();
            $table->string('codigo_afectacion_b', 3)->nullable();
            $table->enum('tipo_valuacion_b', ['T', 'V'])->nullable();
            $table->string('fecha_vt_b', 8)->nullable(); // DDMMYYYY
            $table->decimal('precio_vt_b', 8, 2)->nullable();
            
            // Campos específicos para PLAZO FIJO
            $table->string('tipo_pf', 3)->nullable(); // Tipo de depósito
            $table->string('bic', 11)->nullable(); // Código BIC del banco
            $table->string('cdf', 22)->nullable(); // Certificado del depósito
            $table->string('fecha_constitucion', 8)->nullable(); // DDMMYYYY
            $table->string('fecha_vencimiento', 8)->nullable(); // DDMMYYYY
            $table->string('moneda', 3)->nullable(); // ARS, USD, etc.
            $table->decimal('valor_nominal_origen', 14, 0)->nullable();
            $table->decimal('valor_nominal_nacional', 14, 0)->nullable();
            $table->enum('tipo_tasa', ['F', 'V'])->nullable(); // Fija, Variable
            $table->decimal('tasa', 5, 3)->nullable(); // 2 enteros, 3 decimales
            $table->boolean('titulo_deuda')->nullable(); // 1 si es título de deuda pública
            $table->string('codigo_titulo', 20)->nullable();
            
            // Campos de tracking
            $table->json('validation_errors')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();

            $table->foreign('presentation_id')->references('id')->on('presentations')->onDelete('cascade');
            $table->index(['presentation_id', 'tipo_operacion']);
            $table->index('fecha_movimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_operations');
    }
};
