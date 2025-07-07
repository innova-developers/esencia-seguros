<?php
/**
 * Script de instalación interactiva para el checker de rectificaciones
 * 
 * Uso: php install_rectification_checker.php
 */

// Configurar timezone
date_default_timezone_set('America/Argentina/Buenos_Aires');

echo "=== INSTALADOR DEL CHECKER DE RECTIFICACIONES ===\n\n";

// Función para leer input del usuario
function readInput($prompt, $default = '') {
    echo $prompt;
    if ($default) {
        echo " (default: {$default})";
    }
    echo ": ";
    
    $handle = fopen("php://stdin", "r");
    $input = trim(fgets($handle));
    fclose($handle);
    
    return $input ?: $default;
}

// Función para validar conexión a base de datos
function testDatabaseConnection($host, $dbname, $username, $password) {
    try {
        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        
        // Verificar tabla presentations
        $stmt = $pdo->query("SHOW TABLES LIKE 'presentations'");
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'La tabla "presentations" no existe'];
        }
        
        return ['success' => true, 'pdo' => $pdo];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Función para crear directorio de logs
function createLogDirectory($logPath) {
    $logDir = dirname($logPath);
    if (!is_dir($logDir)) {
        if (mkdir($logDir, 0755, true)) {
            return ['success' => true, 'message' => 'Directorio de logs creado'];
        } else {
            return ['success' => false, 'error' => 'No se pudo crear el directorio de logs'];
        }
    }
    return ['success' => true, 'message' => 'Directorio de logs ya existe'];
}

// Función para generar configuración
function generateConfig($config) {
    $configContent = "<?php\n";
    $configContent .= "/**\n";
    $configContent .= " * Configuración para el script de verificación de rectificaciones\n";
    $configContent .= " * Generado automáticamente el " . date('Y-m-d H:i:s') . "\n";
    $configContent .= " */\n\n";
    $configContent .= "return " . var_export($config, true) . ";\n";
    
    return $configContent;
}

echo "Este script te ayudará a configurar el checker de rectificaciones.\n";
echo "Necesitarás tener a mano:\n";
echo "- Credenciales de la base de datos\n";
echo "- Token de autenticación SSN\n";
echo "- Información del servidor\n\n";

// 1. Configuración de base de datos
echo "=== CONFIGURACIÓN DE BASE DE DATOS ===\n";
$dbHost = readInput("Host de la base de datos", "localhost");
$dbName = readInput("Nombre de la base de datos", "esencia_seguros");
$dbUser = readInput("Usuario de la base de datos", "root");
$dbPass = readInput("Contraseña de la base de datos", "");

echo "\nProbando conexión a la base de datos...\n";
$dbTest = testDatabaseConnection($dbHost, $dbName, $dbUser, $dbPass);

if (!$dbTest['success']) {
    echo "❌ Error: " . $dbTest['error'] . "\n";
    echo "Verifica las credenciales y vuelve a intentar.\n";
    exit(1);
}

echo "✅ Conexión exitosa a la base de datos\n";

// 2. Configuración SSN
echo "\n=== CONFIGURACIÓN SSN ===\n";
$ssnUrl = readInput("URL base de SSN", "https://ri.ssn.gob.ar/api");
$ssnToken = readInput("Token de autenticación SSN (OBLIGATORIO)", "");

if (empty($ssnToken)) {
    echo "❌ Error: El token SSN es obligatorio\n";
    exit(1);
}

$ssnTimeout = readInput("Timeout para consultas SSN (segundos)", "30");
$ssnRetries = readInput("Número de reintentos", "3");
$ssnDelay = readInput("Delay entre reintentos (segundos)", "5");

// 3. Configuración de logs
echo "\n=== CONFIGURACIÓN DE LOGS ===\n";
$logFile = readInput("Archivo de log", __DIR__ . "/logs/rectification_check.log");
$logLevel = readInput("Nivel de log (DEBUG/INFO/WARNING/ERROR)", "INFO");

echo "\nCreando directorio de logs...\n";
$logTest = createLogDirectory($logFile);
if (!$logTest['success']) {
    echo "❌ Error: " . $logTest['error'] . "\n";
    exit(1);
}
echo "✅ " . $logTest['message'] . "\n";

// 4. Configuración de horario hábil
echo "\n=== CONFIGURACIÓN DE HORARIO HÁBIL ===\n";
$businessHoursEnabled = readInput("¿Habilitar verificación de horario hábil? (y/n)", "y");
$businessHoursEnabled = strtolower($businessHoursEnabled) === 'y';

if ($businessHoursEnabled) {
    $startHour = readInput("Hora de inicio (formato 24h)", "8");
    $endHour = readInput("Hora de fin (formato 24h)", "19");
} else {
    $startHour = 0;
    $endHour = 23;
}

// 5. Configuración de notificaciones (opcional)
echo "\n=== CONFIGURACIÓN DE NOTIFICACIONES ===\n";
$notificationsEnabled = readInput("¿Habilitar notificaciones por email? (y/n)", "n");
$notificationsEnabled = strtolower($notificationsEnabled) === 'y';

$emailConfig = [];
if ($notificationsEnabled) {
    $emailConfig['smtp_host'] = readInput("Servidor SMTP", "");
    $emailConfig['smtp_port'] = readInput("Puerto SMTP", "587");
    $emailConfig['smtp_username'] = readInput("Usuario SMTP", "");
    $emailConfig['smtp_password'] = readInput("Contraseña SMTP", "");
    $emailConfig['from_email'] = readInput("Email de origen", "");
    $emailConfig['from_name'] = readInput("Nombre de origen", "Sistema Esencia Seguros");
    
    echo "Emails de destino (separar con comas): ";
    $handle = fopen("php://stdin", "r");
    $toEmails = trim(fgets($handle));
    fclose($handle);
    $emailConfig['to_emails'] = array_map('trim', explode(',', $toEmails));
}

// 6. Construir configuración
$config = [
    'database' => [
        'host' => $dbHost,
        'dbname' => $dbName,
        'username' => $dbUser,
        'password' => $dbPass,
        'charset' => 'utf8mb4'
    ],
    'ssn' => [
        'base_url' => $ssnUrl,
        'token' => $ssnToken,
        'timeout' => (int)$ssnTimeout,
        'retry_attempts' => (int)$ssnRetries,
        'retry_delay' => (int)$ssnDelay
    ],
    'business_hours' => [
        'enabled' => $businessHoursEnabled,
        'days' => [1, 2, 3, 4, 5], // Lunes a viernes
        'start_hour' => (int)$startHour,
        'end_hour' => (int)$endHour,
        'timezone' => 'America/Argentina/Buenos_Aires'
    ],
    'logging' => [
        'enabled' => true,
        'log_file' => $logFile,
        'max_file_size' => 10 * 1024 * 1024, // 10MB
        'max_files' => 30,
        'level' => strtoupper($logLevel)
    ],
    'notifications' => [
        'enabled' => $notificationsEnabled,
        'email' => $emailConfig
    ],
    'approved_statuses' => ['RECTIFICAR', 'A_RECTIFICAR'],
    'rejected_statuses' => ['RECHAZADA', 'DENEGADA'],
    'request_delay' => 500000 // 0.5 segundos
];

// 7. Generar archivo de configuración
echo "\n=== GENERANDO CONFIGURACIÓN ===\n";
$configContent = generateConfig($config);

if (file_put_contents('rectification_config.php', $configContent)) {
    echo "✅ Archivo de configuración generado: rectification_config.php\n";
} else {
    echo "❌ Error: No se pudo escribir el archivo de configuración\n";
    exit(1);
}

// 8. Probar configuración
echo "\n=== PROBANDO CONFIGURACIÓN ===\n";
if (file_exists('test_rectification_check.php')) {
    echo "Ejecutando script de prueba...\n";
    system('php test_rectification_check.php');
} else {
    echo "⚠️  Script de prueba no encontrado\n";
}

// 9. Instrucciones finales
echo "\n=== INSTALACIÓN COMPLETADA ===\n";
echo "✅ El checker de rectificaciones ha sido configurado correctamente.\n\n";

echo "PRÓXIMOS PASOS:\n";
echo "1. Verificar la configuración ejecutando: php test_rectification_check.php\n";
echo "2. Probar el script manualmente: php check_rectification_status.php\n";
echo "3. Configurar el cronjob en cPanel o servidor:\n";
echo "   Comando: /usr/bin/php " . realpath('check_rectification_status.php') . "\n";
echo "   Horario: */30 8-18 * * 1-5\n\n";

echo "ARCHIVOS CREADOS:\n";
echo "- rectification_config.php (configuración)\n";
echo "- logs/ (directorio de logs)\n\n";

echo "DOCUMENTACIÓN:\n";
echo "- Revisar README_RECTIFICATION_CHECKER.md para más detalles\n";
echo "- Los logs se guardan en: {$logFile}\n\n";

echo "¡Instalación completada exitosamente!\n"; 