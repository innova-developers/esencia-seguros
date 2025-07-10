<?php
/**
 * Script para verificar el estado de las solicitudes de rectificación pendientes
 * Se ejecuta como cronjob cada 30 minutos en horario hábil
 * 
 * Uso: php check_rectification_status.php
 */

// Configuración
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Función para cargar variables de entorno desde .env
function loadEnvVariables() {
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) {
        throw new Exception("Archivo .env no encontrado en: {$envFile}");
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remover comillas si existen
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }
            
            $env[$key] = $value;
        }
    }
    
    return $env;
}

// Función para obtener variable de entorno con valor por defecto
function getEnv($key, $default = null) {
    global $envVars;
    return $envVars[$key] ?? $default;
}

// Cargar variables de entorno
try {
    $envVars = loadEnvVariables();
} catch (Exception $e) {
    die("Error cargando variables de entorno: " . $e->getMessage() . "\n");
}

// Configuración de la base de datos desde variables de entorno
$dbConfig = [
    'host' => getEnv('DB_HOST', 'localhost'),
    'dbname' => getEnv('DB_DATABASE', 'esencia_seguros'),
    'username' => getEnv('DB_USERNAME', 'root'),
    'password' => getEnv('DB_PASSWORD', ''),
    'charset' => getEnv('DB_CHARSET', 'utf8mb4')
];

// Configuración SSN desde variables de entorno
$ssnConfig = [
    'base_url' => getEnv('SSN_BASE_URL', 'https://ri.ssn.gob.ar/api'),
    'token' => getEnv('SSN_TOKEN', ''),
    'timeout' => (int)getEnv('SSN_TIMEOUT', '30'),
    'retry_attempts' => (int)getEnv('SSN_RETRY_ATTEMPTS', '3'),
    'retry_delay' => (int)getEnv('SSN_RETRY_DELAY', '5')
];

// Configuración de horario hábil desde variables de entorno
$businessHoursConfig = [
    'enabled' => getEnv('BUSINESS_HOURS_ENABLED', 'true') === 'true',
    'start_hour' => (int)getEnv('BUSINESS_HOURS_START', '8'),
    'end_hour' => (int)getEnv('BUSINESS_HOURS_END', '19'),
    'days' => explode(',', getEnv('BUSINESS_HOURS_DAYS', '1,2,3,4,5')) // Lunes a viernes
];

// Configuración de logs desde variables de entorno
$logConfig = [
    'enabled' => getEnv('LOG_ENABLED', 'true') === 'true',
    'log_file' => getEnv('LOG_FILE', __DIR__ . '/logs/rectification_check.log'),
    'max_file_size' => (int)getEnv('LOG_MAX_FILE_SIZE', '10485760'), // 10MB
    'max_files' => (int)getEnv('LOG_MAX_FILES', '30'),
    'level' => getEnv('LOG_LEVEL', 'INFO')
];

// Estados de rectificación desde variables de entorno
$approvedStatuses = explode(',', getEnv('RECTIFICATION_APPROVED_STATUSES', 'RECTIFICAR,A_RECTIFICAR'));
$rejectedStatuses = explode(',', getEnv('RECTIFICATION_REJECTED_STATUSES', 'RECHAZADA,DENEGADA'));

// Delay entre consultas
$requestDelay = (int)getEnv('REQUEST_DELAY_MICROSECONDS', '500000'); // 0.5 segundos

// Función para log
function logMessage($message, $level = 'INFO') {
    global $logConfig;
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    // Log a archivo
    $logFile = $logConfig['log_file'];
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    
    // También mostrar en consola si se ejecuta manualmente
    if (php_sapi_name() === 'cli') {
        echo $logMessage;
    }
}

// Función para verificar si es horario hábil
function isBusinessHours() {
    global $businessHoursConfig;
    
    if (!$businessHoursConfig['enabled']) {
        return true; // Si está deshabilitado, siempre permitir
    }
    
    $now = new DateTime();
    $dayOfWeek = $now->format('N'); // 1 (lunes) a 7 (domingo)
    $hour = (int)$now->format('H');
    
    return in_array($dayOfWeek, $businessHoursConfig['days']) && 
           $hour >= $businessHoursConfig['start_hour'] && 
           $hour < $businessHoursConfig['end_hour'];
}

// Función para conectar a la base de datos
function connectDatabase($config) {
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        logMessage("Error conectando a la base de datos: " . $e->getMessage(), 'ERROR');
        return null;
    }
}

// Función para obtener presentaciones con rectificación pendiente
function getPendingRectifications($pdo) {
    try {
        $sql = "SELECT id, codigo_compania, cronograma, tipo_entrega, ssn_response_id, 
                       ssn_response_data, rectification_requested_at, created_at, updated_at
                FROM presentations 
                WHERE estado = 'RECTIFICACION_PENDIENTE'
                ORDER BY rectification_requested_at ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        logMessage("Error obteniendo rectificaciones pendientes: " . $e->getMessage(), 'ERROR');
        return [];
    }
}

// Función para consultar estado en SSN
function checkSSNStatus($presentation, $ssnConfig, $pdo) {
    try {
        // Obtener token válido o renovarlo si es necesario
        $token = renewTokenIfNeeded($pdo, $ssnConfig);
        if (!$token) {
            logMessage("No se pudo obtener token válido para presentación {$presentation['id']}", 'ERROR');
            return null;
        }
        
        // Determinar endpoint según tipo de entrega
        $endpoint = $presentation['tipo_entrega'] === 'Semanal' ? '/inv/entregaSemanal' : '/inv/entregaMensual';
        
        // Parámetros de consulta
        $params = [
            'codigoCompania' => $presentation['codigo_compania'],
            'cronograma' => $presentation['cronograma'],
            'tipoEntrega' => $presentation['tipo_entrega']
        ];
        
        $url = $ssnConfig['base_url'] . $endpoint . '?' . http_build_query($params);
        
        // Configurar cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $ssnConfig['timeout'],
            CURLOPT_HTTPHEADER => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'token: ' . $token
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            logMessage("Error cURL para presentación {$presentation['id']}: {$error}", 'ERROR');
            return null;
        }
        
        if ($httpCode !== 200) {
            logMessage("HTTP Error {$httpCode} para presentación {$presentation['id']}: {$response}", 'ERROR');
            return null;
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logMessage("Error decodificando JSON para presentación {$presentation['id']}: " . json_last_error_msg(), 'ERROR');
            return null;
        }
        
        return $data;
        
    } catch (Exception $e) {
        logMessage("Error consultando SSN para presentación {$presentation['id']}: " . $e->getMessage(), 'ERROR');
        return null;
    }
}

// Función para actualizar estado de la presentación
function updatePresentationStatus($pdo, $presentationId, $newStatus, $ssnData) {
    try {
        $sql = "UPDATE presentations 
                SET estado = :estado, 
                    rectification_approved_at = :approved_at,
                    ssn_response_data = :ssn_data,
                    updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $approvedAt = $newStatus === 'A_RECTIFICAR' ? date('Y-m-d H:i:s') : null;
        
        // Actualizar datos de SSN
        $currentSsnData = json_decode($ssnData['ssn_response_data'] ?? '{}', true) ?: [];
        $currentSsnData['rectification_status_check'] = [
            'fecha_consulta' => date('Y-m-d H:i:s'),
            'estado_anterior' => 'RECTIFICACION_PENDIENTE',
            'estado_nuevo' => $newStatus,
            'respuesta_ssn' => $ssnData
        ];
        
        $stmt->execute([
            ':estado' => $newStatus,
            ':approved_at' => $approvedAt,
            ':ssn_data' => json_encode($currentSsnData),
            ':id' => $presentationId
        ]);
        
        return true;
    } catch (PDOException $e) {
        logMessage("Error actualizando estado de presentación {$presentationId}: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

// Función para registrar actividad
function logActivity($pdo, $presentationId, $action, $description, $metadata = [], $module = 'Cron') {
    try {
        $sql = "INSERT INTO activity_logs (user_id, action, description, metadata, ip_address, user_agent, created_at, module) 
                VALUES (:user_id, :action, :description, :metadata, :ip_address, :user_agent, NOW(), :module)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => null, // Sistema
            ':action' => $action,
            ':description' => $description,
            ':metadata' => json_encode($metadata),
            ':ip_address' => '127.0.0.1',
            ':user_agent' => 'CronJob/RectificationChecker',
            ':module' => $module
        ]);
        return true;
    } catch (PDOException $e) {
        logMessage("Error registrando actividad: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

// Función para obtener token válido desde la base de datos
function getValidToken($pdo) {
    try {
        $sql = "SELECT token, expiration, is_mock FROM ssn_tokens 
                WHERE (expiration > NOW() OR is_mock = 1) 
                ORDER BY created_at DESC LIMIT 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $tokenRecord = $stmt->fetch();
        
        if ($tokenRecord) {
            return $tokenRecord['token'];
        }
        
        return null;
    } catch (PDOException $e) {
        logMessage("Error obteniendo token de la base de datos: " . $e->getMessage(), 'ERROR');
        return null;
    }
}

// Función para renovar token si es necesario
function renewTokenIfNeeded($pdo, $ssnConfig) {
    $token = getValidToken($pdo);
    
    if ($token) {
        logMessage("Token válido encontrado en base de datos");
        return $token;
    }
    
    logMessage("Token no válido o expirado. Intentando renovar...");
    
    // Obtener credenciales desde variables de entorno
    $username = getEnv('SSN_USERNAME', '');
    $cia = getEnv('SSN_CIA', '');
    $password = getEnv('SSN_PASSWORD', '');
    
    if (empty($username) || empty($cia) || empty($password)) {
        logMessage("Credenciales SSN no configuradas en .env", 'ERROR');
        return null;
    }
    
    try {
        // Endpoint de autenticación
        $authUrl = $ssnConfig['base_url'] . '/login';
        
        // Configurar cURL para autenticación
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $authUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => $ssnConfig['timeout'],
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'USER' => $username,
                'CIA' => $cia,
                'PASSWORD' => $password,
            ]),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            logMessage("Error cURL en autenticación: {$error}", 'ERROR');
            return null;
        }
        
        if ($httpCode !== 200) {
            logMessage("HTTP Error {$httpCode} en autenticación: {$response}", 'ERROR');
            return null;
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            logMessage("Error decodificando JSON de autenticación: " . json_last_error_msg(), 'ERROR');
            return null;
        }
        
        $newToken = $data['TOKEN'] ?? null;
        $expiration = $data['FECHA_EXPIRACION'] ?? null;
        
        if ($newToken) {
            // Guardar nuevo token en base de datos
            saveTokenToDatabase($pdo, $newToken, $expiration, false, $username, $cia);
            logMessage("Token renovado exitosamente");
            return $newToken;
        } else {
            logMessage("No se pudo obtener token de la respuesta de autenticación", 'ERROR');
            return null;
        }
        
    } catch (Exception $e) {
        logMessage("Error renovando token: " . $e->getMessage(), 'ERROR');
        return null;
    }
}

// Función para guardar token en base de datos
function saveTokenToDatabase($pdo, $token, $expiration, $isMock = false, $username = null, $cia = null) {
    try {
        // Limpiar tokens expirados
        $sql = "DELETE FROM ssn_tokens WHERE expiration < NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        // Insertar nuevo token
        $sql = "INSERT INTO ssn_tokens (token, expiration, is_mock, username, cia, created_at, updated_at) 
                VALUES (:token, :expiration, :is_mock, :username, :cia, NOW(), NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':token' => $token,
            ':expiration' => $expiration ? date('Y-m-d H:i:s', strtotime($expiration)) : null,
            ':is_mock' => $isMock ? 1 : 0,
            ':username' => $username,
            ':cia' => $cia,
        ]);
        
        return true;
    } catch (PDOException $e) {
        logMessage("Error guardando token en base de datos: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

// Función principal
function main() {
    global $dbConfig, $ssnConfig, $approvedStatuses, $rejectedStatuses, $requestDelay;
    
    logMessage("Iniciando verificación de estado de rectificaciones");
    
    // Conectar a la base de datos
    $pdo = connectDatabase($dbConfig);
    if (!$pdo) {
        logMessage("No se pudo conectar a la base de datos. Abortando.", 'ERROR');
        return;
    }
    // Log de inicio de sync general
    logActivity($pdo, null, 'SYNC_STARTED', 'Comienza sincronización de rectificaciones por cron', [
        'fecha_inicio' => date('Y-m-d H:i:s')
    ], 'Cron');
    // Verificar horario hábil
    if (!isBusinessHours()) {
        logMessage("Fuera de horario hábil. No se realizarán consultas.");
        // Log de fin de sync general (sin procesar)
        logActivity($pdo, null, 'SYNC_FINISHED', 'Finaliza sincronización de rectificaciones por cron (fuera de horario hábil)', [
            'fecha_fin' => date('Y-m-d H:i:s'),
            'revisadas' => 0,
            'actualizadas' => 0,
            'errores' => 0
        ], 'Cron');
        return;
    }
    // Obtener presentaciones con rectificación pendiente
    $pendingRectifications = getPendingRectifications($pdo);
    logMessage("Encontradas " . count($pendingRectifications) . " rectificaciones pendientes");
    if (empty($pendingRectifications)) {
        logMessage("No hay rectificaciones pendientes para verificar");
        // Log de fin de sync general (sin procesar)
        logActivity($pdo, null, 'SYNC_FINISHED', 'Finaliza sincronización de rectificaciones por cron (sin pendientes)', [
            'fecha_fin' => date('Y-m-d H:i:s'),
            'revisadas' => 0,
            'actualizadas' => 0,
            'errores' => 0
        ], 'Cron');
        return;
    }
    $checked = 0;
    $updated = 0;
    $errors = 0;
    foreach ($pendingRectifications as $presentation) {
        $checked++;
        logMessage("Verificando presentación {$presentation['id']} ({$presentation['cronograma']})");
        // Consultar estado en SSN (pasar PDO para renovación de token)
        $ssnResponse = checkSSNStatus($presentation, $ssnConfig, $pdo);
        if ($ssnResponse === null) {
            $errors++;
            continue;
        }
        // Verificar si la rectificación fue aprobada
        $estado = $ssnResponse['estado'] ?? '';
        $needsUpdate = false;
        $newStatus = null;
        if (in_array($estado, $approvedStatuses)) {
            $newStatus = 'A_RECTIFICAR';
            $needsUpdate = true;
            logMessage("Rectificación aprobada para presentación {$presentation['id']}. Nuevo estado: {$newStatus}");
        } elseif (in_array($estado, $rejectedStatuses)) {
            $newStatus = 'RECHAZADA';
            $needsUpdate = true;
            logMessage("Rectificación rechazada para presentación {$presentation['id']}. Nuevo estado: {$newStatus}");
        } else {
            logMessage("Presentación {$presentation['id']} aún pendiente. Estado SSN: {$estado}");
        }
        // Actualizar si es necesario
        if ($needsUpdate && $newStatus) {
            if (updatePresentationStatus($pdo, $presentation['id'], $newStatus, $ssnResponse)) {
                $updated++;
                // Registrar actividad por presentación actualizada
                $action = $newStatus === 'A_RECTIFICAR' ? 'RECTIFICATION_APPROVED' : 'RECTIFICATION_REJECTED';
                $description = $newStatus === 'A_RECTIFICAR' 
                    ? "Rectificación aprobada por SSN para presentación {$presentation['id']}"
                    : "Rectificación rechazada por SSN para presentación {$presentation['id']}";
                logActivity($pdo, $presentation['id'], $action, $description, [
                    'presentation_id' => $presentation['id'],
                    'cronograma' => $presentation['cronograma'],
                    'tipo_entrega' => $presentation['tipo_entrega'],
                    'estado_anterior' => 'RECTIFICACION_PENDIENTE',
                    'estado_nuevo' => $newStatus,
                    'ssn_response' => $ssnResponse
                ], 'Cron');
                logMessage("Estado actualizado exitosamente para presentación {$presentation['id']}");
            } else {
                $errors++;
                logMessage("Error actualizando estado de presentación {$presentation['id']}", 'ERROR');
            }
        }
        // Pequeña pausa entre consultas para no sobrecargar SSN
        usleep($requestDelay);
    }
    logMessage("Verificación completada. Revisadas: {$checked}, Actualizadas: {$updated}, Errores: {$errors}");
    // Log de fin de sync general
    logActivity($pdo, null, 'SYNC_FINISHED', 'Finaliza sincronización de rectificaciones por cron', [
        'fecha_fin' => date('Y-m-d H:i:s'),
        'revisadas' => $checked,
        'actualizadas' => $updated,
        'errores' => $errors
    ], 'Cron');
}

// Ejecutar si se llama directamente
if (php_sapi_name() === 'cli' || isset($_GET['run'])) {
    main();
} else {
    // Si se accede por web, mostrar información básica
    header('Content-Type: text/plain; charset=utf-8');
    echo "Script de verificación de rectificaciones\n";
    echo "Este script debe ejecutarse como cronjob\n";
    echo "Para ejecutar manualmente: php check_rectification_status.php\n";
    echo "O agregar ?run=1 a la URL\n";
} 