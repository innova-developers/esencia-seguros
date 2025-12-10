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
            // Aumentar el tamaño de la columna tasa de decimal(5,3) a decimal(10,3)
            // para permitir valores más grandes (hasta 9999999.999)
            $table->decimal('tasa', 10, 3)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weekly_operations', function (Blueprint $table) {
            // Revertir al tamaño original
            $table->decimal('tasa', 5, 3)->nullable()->change();
        });
    }
};
