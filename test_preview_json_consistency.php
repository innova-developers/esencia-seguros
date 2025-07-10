<?php

require_once 'vendor/autoload.php';

// Inicializar Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ExcelProcessorService;
use App\Domain\Models\Presentation;
use App\Domain\Models\WeeklyOperation;

// Simular datos de prueba
$testOperations = [
    [
        'tipo_operacion' => 'C',
        'tipo_especie' => 'BON',
        'codigo_especie' => 'AL30',
        'cant_especies' => 1000,
        'codigo_afectacion' => '01',
        'tipo_valuacion' => 'M',
        'fecha_movimiento' => '2025-01-15',
        'fecha_liquidacion' => '2025-01-17',
        'precio_compra' => 95.50,
    ],
    [
        'tipo_operacion' => 'V',
        'tipo_especie' => 'BON',
        'codigo_especie' => 'AL30',
        'cant_especies' => 500,
        'codigo_afectacion' => '01',
        'tipo_valuacion' => 'M',
        'fecha_movimiento' => '2025-01-20',
        'fecha_liquidacion' => '2025-01-22',
        'precio_venta' => 98.75,
        'fecha_pase_vt' => '2025-01-21',
        'precio_pase_vt' => 97.25,
    ]
];

echo "=== PRUEBA DE CONSISTENCIA DEL JSON ===\n\n";

// 1. Generar JSON con ExcelProcessorService (como en la previsualización)
$excelService = new ExcelProcessorService();
$previewJson = $excelService->generateSsnJson($testOperations, '2025-01');

echo "1. JSON generado por ExcelProcessorService (previsualización):\n";
echo json_encode($previewJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

// 2. Simular JSON como lo generaría el modelo Presentation
$modelJson = [
    'codigoCompania' => config('services.ssn.cia', '0001'),
    'cronograma' => '2025-01',
    'tipoEntrega' => 'S',
    'operaciones' => []
];

// Simular operaciones como las generaría WeeklyOperation::getSsnJson()
foreach ($testOperations as $operation) {
    $ssnOperation = ['tipoOperacion' => $operation['tipo_operacion']];
    
    if ($operation['tipo_operacion'] === 'C') {
        $ssnOperation = array_merge($ssnOperation, [
            'tipoEspecie' => $operation['tipo_especie'],
            'codigoEspecie' => $operation['codigo_especie'],
            'cantEspecies' => (float) $operation['cant_especies'],
            'codigoAfectacion' => $operation['codigo_afectacion'],
            'tipoValuacion' => $operation['tipo_valuacion'],
            'fechaMovimiento' => $excelService->formatDateForSSN($operation['fecha_movimiento']),
            'fechaLiquidacion' => $excelService->formatDateForSSN($operation['fecha_liquidacion']),
            'precioCompra' => (float) $operation['precio_compra'],
        ]);
    } elseif ($operation['tipo_operacion'] === 'V') {
        $ssnOperation = array_merge($ssnOperation, [
            'tipoEspecie' => $operation['tipo_especie'],
            'codigoEspecie' => $operation['codigo_especie'],
            'cantEspecies' => (float) $operation['cant_especies'],
            'codigoAfectacion' => $operation['codigo_afectacion'],
            'tipoValuacion' => $operation['tipo_valuacion'],
            'fechaMovimiento' => $excelService->formatDateForSSN($operation['fecha_movimiento']),
            'fechaLiquidacion' => $excelService->formatDateForSSN($operation['fecha_liquidacion']),
            'precioVenta' => (float) $operation['precio_venta'],
            'fechaPaseVT' => $excelService->formatDateForSSN($operation['fecha_pase_vt']),
            'precioPaseVT' => (float) $operation['precio_pase_vt'],
        ]);
    }
    
    $modelJson['operaciones'][] = $ssnOperation;
}

echo "2. JSON simulado como lo generaría el modelo Presentation:\n";
echo json_encode($modelJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

// 3. Comparar estructuras
echo "3. COMPARACIÓN:\n";
echo "- Ambos incluyen codigoCompania: " . (isset($previewJson['codigoCompania']) && isset($modelJson['codigoCompania']) ? '✅' : '❌') . "\n";
echo "- Ambos incluyen cronograma: " . (isset($previewJson['cronograma']) && isset($modelJson['cronograma']) ? '✅' : '❌') . "\n";
echo "- Ambos incluyen tipoEntrega: " . (isset($previewJson['tipoEntrega']) && isset($modelJson['tipoEntrega']) ? '✅' : '❌') . "\n";
echo "- Ambos incluyen operaciones: " . (isset($previewJson['operaciones']) && isset($modelJson['operaciones']) ? '✅' : '❌') . "\n";

// Verificar campos específicos de venta
$ventaPreview = array_filter($previewJson['operaciones'], fn($op) => $op['tipoOperacion'] === 'V');
$ventaModel = array_filter($modelJson['operaciones'], fn($op) => $op['tipoOperacion'] === 'V');

if (!empty($ventaPreview) && !empty($ventaModel)) {
    $ventaPreview = reset($ventaPreview);
    $ventaModel = reset($ventaModel);
    
    echo "- Operación de venta incluye precioVenta: " . (isset($ventaPreview['precioVenta']) && isset($ventaModel['precioVenta']) ? '✅' : '❌') . "\n";
    echo "- Operación de venta incluye fechaPaseVT: " . (isset($ventaPreview['fechaPaseVT']) && isset($ventaModel['fechaPaseVT']) ? '✅' : '❌') . "\n";
    echo "- Operación de venta incluye precioPaseVT: " . (isset($ventaPreview['precioPaseVT']) && isset($ventaModel['precioPaseVT']) ? '✅' : '❌') . "\n";
}

echo "\n=== FIN DE PRUEBA ===\n"; 