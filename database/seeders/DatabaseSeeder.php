<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuario administrador
        \App\Domain\Models\User::updateOrCreate(
            ['email' => 'admin@esenciaseguros.com'],
            [
                'name' => 'Admin Esencia',
                'email' => 'admin@esenciaseguros.com',
                'password' => bcrypt('password123'),
            ]
        );

        // Ejecutar seeders de catálogos SSN
        $this->call([
            SsnSpeciesSeeder::class,
            SsnAffectationsSeeder::class,
            SsnBanksSeeder::class,
            SsnDepositTypesSeeder::class,
            SsnSgrCodesSeeder::class,
        ]);
    }
}
