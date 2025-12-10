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
            // Aumentar el tamaño de las columnas de precios de decimal(8,2) a decimal(14,2)
            // para permitir valores más grandes (hasta 999999999999.99)
            $table->decimal('precio_compra', 14, 2)->nullable()->change();
            $table->decimal('precio_venta', 14, 2)->nullable()->change();
            $table->decimal('precio_pase_vt', 14, 2)->nullable()->change();
            $table->decimal('precio_vt_a', 14, 2)->nullable()->change();
            $table->decimal('precio_vt_b', 14, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_operations', function (Blueprint $table) {
            // Revertir al tamaño original
            $table->decimal('precio_compra', 8, 2)->nullable()->change();
            $table->decimal('precio_venta', 8, 2)->nullable()->change();
            $table->decimal('precio_pase_vt', 8, 2)->nullable()->change();
            $table->decimal('precio_vt_a', 8, 2)->nullable()->change();
            $table->decimal('precio_vt_b', 8, 2)->nullable()->change();
        });
    }
};
