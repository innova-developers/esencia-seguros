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
        Schema::create('monthly_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('presentation_id');
            $table->enum('tipo', ['I', 'P', 'C']); // Inversiones, Plazo Fijo, Cheque Pago Diferido
            
            // Campos para INVERSIONES (tipo = 'I')
            $table->string('tipo_especie', 2)->nullable(); // TP, ON, AC, FF, FC, OP
            $table->string('codigo_especie', 20)->nullable();
            $table->decimal('cantidad_devengado_especies', 20, 6)->nullable();
            $table->decimal('cantidad_percibido_especies', 20, 6)->nullable();
            $table->string('codigo_afectacion', 3)->nullable();
            $table->enum('tipo_valuacion', ['T', 'V'])->nullable(); // Técnico, Mercado
            $table->boolean('con_cotizacion')->nullable(); // 1: Sí, 0: No
            $table->boolean('libre_disponibilidad')->nullable(); // 1: Sí, 0: No
            $table->boolean('emisor_grupo_economico')->nullable(); // 1: Sí, 0: No
            $table->boolean('emisor_art_ret')->nullable(); // 1: Sí, 0: No
            $table->decimal('prevision_desvalorizacion', 14, 0)->nullable();
            $table->decimal('valor_contable', 14, 0)->nullable();
            $table->string('fecha_pase_vt', 8)->nullable(); // DDMMYYYY
            $table->decimal('precio_pase_vt', 8, 2)->nullable();
            $table->boolean('en_custodia')->nullable(); // 1: Sí, 0: No
            $table->boolean('financiera')->nullable(); // 1: Sí, 0: No
            $table->decimal('valor_financiero', 14, 0)->nullable();
            
            // Campos para PLAZO FIJO (tipo = 'P')
            $table->string('tipo_pf', 3)->nullable(); // Tipo de depósito
            $table->string('bic', 11)->nullable(); // Código BIC del banco
            $table->string('cdf', 22)->nullable(); // Certificado del depósito
            $table->string('fecha_constitucion', 8)->nullable(); // DDMMYYYY
            $table->string('fecha_vencimiento_pf', 8)->nullable(); // DDMMYYYY para plazo fijo
            $table->string('moneda', 3)->nullable(); // ARS, USD, etc.
            $table->decimal('valor_nominal_origen', 14, 0)->nullable();
            $table->decimal('valor_nominal_nacional', 14, 0)->nullable();
            $table->enum('tipo_tasa', ['F', 'V'])->nullable(); // Fija, Variable
            $table->decimal('tasa', 5, 3)->nullable(); // 2 enteros, 3 decimales
            $table->boolean('titulo_deuda')->nullable(); // 1 si es título de deuda pública
            $table->string('codigo_titulo', 20)->nullable();
            
            // Campos para CHEQUE PAGO DIFERIDO (tipo = 'C')
            $table->string('codigo_sgr', 3)->nullable(); // Código SGR
            $table->string('codigo_cheque', 22)->nullable(); // Código del cheque
            $table->string('fecha_emision', 8)->nullable(); // DDMMYYYY
            $table->string('fecha_vencimiento_cheque', 8)->nullable(); // DDMMYYYY para cheque
            $table->decimal('valor_nominal', 14, 0)->nullable();
            $table->decimal('valor_adquisicion', 14, 0)->nullable();
            $table->string('fecha_adquisicion', 8)->nullable(); // DDMMYYYY
            
            // Campos de tracking
            $table->json('validation_errors')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();

            $table->foreign('presentation_id')->references('id')->on('presentations')->onDelete('cascade');
            $table->index(['presentation_id', 'tipo']);
            $table->index('codigo_especie');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_stocks');
    }
};
