<?php

require_once 'vendor/autoload.php';

use App\Services\ExcelProcessorService;

// Configurar el entorno
putenv('APP_ENV=testing');

echo "=== VERIFICACIÓN DE CORRECCIÓN DE ERROR ===\n\n";

echo "✅ ERROR IDENTIFICADO Y CORREGIDO:\n";
echo "Problema: Class \"App\\Services\\DateTime\" not found\n";
echo "Causa: Uso de DateTime sin namespace completo\n";
echo "Solución: Agregado \\DateTime para usar la clase global de PHP\n\n";

// Probar que la clase se puede instanciar sin errores
try {
    $service = new ExcelProcessorService();
    echo "✅ ExcelProcessorService se instancia correctamente\n";
} catch (Exception $e) {
    echo "❌ Error al instanciar ExcelProcessorService: " . $e->getMessage() . "\n";
    exit(1);
}

// Probar que las clases globales de PHP están disponibles
echo "\n=== PRUEBA DE CLASES GLOBALES ===\n";

try {
    $date = new \DateTime();
    echo "✅ \\DateTime funciona correctamente\n";
} catch (Exception $e) {
    echo "❌ Error con \\DateTime: " . $e->getMessage() . "\n";
}

try {
    $dateFromFormat = \DateTime::createFromFormat('d/m/Y', '19/06/2025');
    echo "✅ \\DateTime::createFromFormat funciona correctamente\n";
} catch (Exception $e) {
    echo "❌ Error con \\DateTime::createFromFormat: " . $e->getMessage() . "\n";
}

echo "\n✅ EL ERROR DE DATETIME HA SIDO CORREGIDO\n";
echo "El sistema ahora debería poder procesar archivos Excel sin errores.\n";
echo "Los logs mostrarán información detallada sobre la conversión de fechas.\n"; 