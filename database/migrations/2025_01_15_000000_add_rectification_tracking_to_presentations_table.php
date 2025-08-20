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
            // Campo para tracking de la última verificación de rectificación
            $table->timestamp('last_rectification_check_at')->nullable()->after('rectification_approved_at');
            
            // Campo para el estado de la última verificación SSN
            $table->string('last_ssn_status')->nullable()->after('last_rectification_check_at');
            
            // Campo para contar intentos de verificación
            $table->unsignedInteger('rectification_check_attempts')->default(0)->after('last_ssn_status');
            
            // Índices para optimizar consultas del cron
            $table->index(['estado', 'last_rectification_check_at']);
            $table->index(['estado', 'tipo_entrega', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('presentations', function (Blueprint $table) {
            $table->dropIndex(['estado', 'last_rectification_check_at']);
            $table->dropIndex(['estado', 'tipo_entrega', 'created_at']);
            
            $table->dropColumn([
                'last_rectification_check_at',
                'last_ssn_status',
                'rectification_check_attempts'
            ]);
        });
    }
};
