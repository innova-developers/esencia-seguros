<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Domain\Models\SsnSgrCode;

class SsnSgrCodesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sgrCodes = [
            [
                'codigo' => '001',
                'descripcion' => 'SGR Principal',
                'nombre_sgr' => 'Sociedad de Garantía Recíproca Principal',
                'activo' => true,
            ],
            [
                'codigo' => '002',
                'descripcion' => 'SGR Regional',
                'nombre_sgr' => 'Sociedad de Garantía Recíproca Regional',
                'activo' => true,
            ],
            [
                'codigo' => '003',
                'descripcion' => 'SGR Sectorial',
                'nombre_sgr' => 'Sociedad de Garantía Recíproca Sectorial',
                'activo' => true,
            ],
            [
                'codigo' => '004',
                'descripcion' => 'SGR Especializada',
                'nombre_sgr' => 'Sociedad de Garantía Recíproca Especializada',
                'activo' => true,
            ],
            [
                'codigo' => '005',
                'descripcion' => 'SGR Nacional',
                'nombre_sgr' => 'Sociedad de Garantía Recíproca Nacional',
                'activo' => true,
            ],
            [
                'codigo' => '099',
                'descripcion' => 'Otras SGR',
                'nombre_sgr' => 'Otras Sociedades de Garantía Recíproca',
                'activo' => true,
            ],
        ];

        foreach ($sgrCodes as $sgrCode) {
            SsnSgrCode::updateOrCreate(
                ['codigo' => $sgrCode['codigo']],
                $sgrCode
            );
        }
    }
}
