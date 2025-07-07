<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Domain\Models\SsnSpecie;

class SsnSpeciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $species = [
            // Títulos Públicos (TP)
            [
                'codigo_ssn' => 'TP001',
                'tipo_especie' => 'TP',
                'descripcion' => 'Bono del Tesoro Nacional',
                'emisor' => 'Tesoro Nacional',
                'serie' => 'BTN',
                'moneda' => 'ARS',
                'activo' => true,
            ],
            [
                'codigo_ssn' => 'TP002',
                'tipo_especie' => 'TP',
                'descripcion' => 'Letra del Tesoro Nacional',
                'emisor' => 'Tesoro Nacional',
                'serie' => 'LTN',
                'moneda' => 'ARS',
                'activo' => true,
            ],
            [
                'codigo_ssn' => 'TP003',
                'tipo_especie' => 'TP',
                'descripcion' => 'Bono del Tesoro en Dólares',
                'emisor' => 'Tesoro Nacional',
                'serie' => 'BODEN',
                'moneda' => 'USD',
                'activo' => true,
            ],

            // Obligaciones Negociables (ON)
            [
                'codigo_ssn' => 'ON001',
                'tipo_especie' => 'ON',
                'descripcion' => 'Obligación Negociable Empresa A',
                'emisor' => 'Empresa A S.A.',
                'serie' => 'ON-A-2025',
                'moneda' => 'ARS',
                'activo' => true,
            ],
            [
                'codigo_ssn' => 'ON002',
                'tipo_especie' => 'ON',
                'descripcion' => 'Obligación Negociable Empresa B',
                'emisor' => 'Empresa B S.A.',
                'serie' => 'ON-B-2025',
                'moneda' => 'USD',
                'activo' => true,
            ],

            // Acciones (AC)
            [
                'codigo_ssn' => 'AC001',
                'tipo_especie' => 'AC',
                'descripcion' => 'Acción Ordinaria Banco A',
                'emisor' => 'Banco A S.A.',
                'serie' => 'AO',
                'moneda' => 'ARS',
                'activo' => true,
            ],
            [
                'codigo_ssn' => 'AC002',
                'tipo_especie' => 'AC',
                'descripcion' => 'Acción Preferida Empresa C',
                'emisor' => 'Empresa C S.A.',
                'serie' => 'AP',
                'moneda' => 'ARS',
                'activo' => true,
            ],

            // Fondos Comunes de Inversión (FC)
            [
                'codigo_ssn' => 'FC001',
                'tipo_especie' => 'FC',
                'descripcion' => 'Fondo Común de Inversión Renta Fija',
                'emisor' => 'Sociedad Gerente A',
                'serie' => 'FCI-RF',
                'moneda' => 'ARS',
                'activo' => true,
            ],
            [
                'codigo_ssn' => 'FC002',
                'tipo_especie' => 'FC',
                'descripcion' => 'Fondo Común de Inversión Renta Variable',
                'emisor' => 'Sociedad Gerente B',
                'serie' => 'FCI-RV',
                'moneda' => 'ARS',
                'activo' => true,
            ],

            // Fideicomisos Financieros (FF)
            [
                'codigo_ssn' => 'FF001',
                'tipo_especie' => 'FF',
                'descripcion' => 'Fideicomiso Financiero Hipotecario',
                'emisor' => 'Fiduciaria A S.A.',
                'serie' => 'FFH',
                'moneda' => 'ARS',
                'activo' => true,
            ],

            // Otras Inversiones (OP)
            [
                'codigo_ssn' => 'OP001',
                'tipo_especie' => 'OP',
                'descripcion' => 'Certificado de Depósito Bancario',
                'emisor' => 'Banco B S.A.',
                'serie' => 'CDB',
                'moneda' => 'ARS',
                'activo' => true,
            ],
        ];

        foreach ($species as $specie) {
            SsnSpecie::updateOrCreate(
                ['codigo_ssn' => $specie['codigo_ssn']],
                $specie
            );
        }
    }
}
