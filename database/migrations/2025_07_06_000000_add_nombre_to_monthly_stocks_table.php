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
        Schema::table('monthly_stocks', function (Blueprint $table) {
            // Agregar campo nombre al inicio de la tabla
            $table->string('nombre', 255)->after('presentation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_stocks', function (Blueprint $table) {
            $table->dropColumn('nombre');
        });
    }
}; 