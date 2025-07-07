<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('presentations', function (Blueprint $table) {
            $table->string('cronograma', 12)->change();
        });
    }
    public function down(): void
    {
        Schema::table('presentations', function (Blueprint $table) {
            $table->string('cronograma', 7)->change();
        });
    }
}; 