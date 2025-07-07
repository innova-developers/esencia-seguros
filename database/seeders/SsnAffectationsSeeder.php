<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Domain\Models\SsnAffectation;

class SsnAffectationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $affectations = [
            [
                'codigo' => '001',
                'descripcion' => 'Reserva Técnica',
                'detalle' => 'Inversiones destinadas a la reserva técnica',
                'activo' => true,
            ],
            [
                'codigo' => '002',
                'descripcion' => 'Reserva de Riesgos en Curso',
                'detalle' => 'Inversiones destinadas a la reserva de riesgos en curso',
                'activo' => true,
            ],
            [
                'codigo' => '003',
                'descripcion' => 'Reserva de Riesgos Catastróficos',
                'detalle' => 'Inversiones destinadas a la reserva de riesgos catastróficos',
                'activo' => true,
            ],
            [
                'codigo' => '004',
                'descripcion' => 'Reserva de Riesgos de Desastres',
                'detalle' => 'Inversiones destinadas a la reserva de riesgos de desastres',
                'activo' => true,
            ],
            [
                'codigo' => '005',
                'descripcion' => 'Reserva de Riesgos de Desastres - Reaseguro',
                'detalle' => 'Inversiones destinadas a la reserva de riesgos de desastres - reaseguro',
                'activo' => true,
            ],
            [
                'codigo' => '006',
                'descripcion' => 'Reserva de Riesgos de Desastres - Retención',
                'detalle' => 'Inversiones destinadas a la reserva de riesgos de desastres - retención',
                'activo' => true,
            ],
            [
                'codigo' => '007',
                'descripcion' => 'Reserva de Riesgos de Desastres - Retención - Reaseguro',
                'detalle' => 'Inversiones destinadas a la reserva de riesgos de desastres - retención - reaseguro',
                'activo' => true,
            ],
            [
                'codigo' => '008',
                'descripcion' => 'Reserva de Riesgos de Desastres - Retención - Reaseguro - Retención',
                'detalle' => 'Inversiones destinadas a la reserva de riesgos de desastres - retención - reaseguro - retención',
                'activo' => true,
            ],
            [
                'codigo' => '009',
                'descripcion' => 'Reserva de Riesgos de Desastres - Retención - Reaseguro - Retención - Reaseguro',
                'detalle' => 'Inversiones destinadas a la reserva de riesgos de desastres - retención - reaseguro - retención - reaseguro',
                'activo' => true,
            ],
            [
                'codigo' => '010',
                'descripcion' => 'Reserva de Riesgos de Desastres - Retención - Reaseguro - Retención - Reaseguro - Retención',
                'detalle' => 'Inversiones destinadas a la reserva de riesgos de desastres - retención - reaseguro - retención - reaseguro - retención',
                'activo' => true,
            ],
            [
                'codigo' => '999',
                'descripcion' => 'Otras Afectaciones',
                'detalle' => 'Inversiones con otras afectaciones no especificadas',
                'activo' => true,
            ],
        ];

        foreach ($affectations as $affectation) {
            SsnAffectation::updateOrCreate(
                ['codigo' => $affectation['codigo']],
                $affectation
            );
        }
    }
}
