<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('weekly_operations', function (Blueprint $table) {
            $table->string('fecha_movimiento', 12)->change();
            $table->string('fecha_liquidacion', 12)->nullable()->change();
        });
    }
    public function down(): void
    {
        Schema::table('weekly_operations', function (Blueprint $table) {
            $table->string('fecha_movimiento', 7)->change();
            $table->string('fecha_liquidacion', 7)->nullable()->change();
        });
    }
}; 