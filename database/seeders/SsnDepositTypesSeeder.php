<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Domain\Models\SsnDepositType;

class SsnDepositTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $depositTypes = [
            [
                'codigo' => '001',
                'descripcion' => 'Plazo Fijo Tradicional',
                'detalle' => 'Depósito a plazo fijo tradicional con tasa fija',
                'activo' => true,
            ],
            [
                'codigo' => '002',
                'descripcion' => 'Plazo Fijo con Tasa Variable',
                'detalle' => 'Depósito a plazo fijo con tasa variable',
                'activo' => true,
            ],
            [
                'codigo' => '003',
                'descripcion' => 'Plazo Fijo UVA',
                'detalle' => 'Depósito a plazo fijo ajustado por UVA',
                'activo' => true,
            ],
            [
                'codigo' => '004',
                'descripcion' => 'Plazo Fijo en Dólares',
                'detalle' => 'Depósito a plazo fijo en moneda extranjera',
                'activo' => true,
            ],
            [
                'codigo' => '005',
                'descripcion' => 'Plazo Fijo con Título de Deuda',
                'detalle' => 'Depósito a plazo fijo concretado con título de deuda pública',
                'activo' => true,
            ],
            [
                'codigo' => '006',
                'descripcion' => 'Plazo Fijo Especial',
                'detalle' => 'Depósito a plazo fijo con condiciones especiales',
                'activo' => true,
            ],
        ];

        foreach ($depositTypes as $depositType) {
            SsnDepositType::updateOrCreate(
                ['codigo' => $depositType['codigo']],
                $depositType
            );
        }
    }
}
