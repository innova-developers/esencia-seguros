<?php
/**
 * Configuración para el script de verificación de rectificaciones
 * 
 * IMPORTANTE: Actualizar estos valores según el entorno de producción
 */

return [
    // Configuración de la base de datos
    'database' => [
        'host' => 'localhost',
        'dbname' => 'esencia_seguros',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ],
    
    // Configuración SSN
    'ssn' => [
        'base_url' => 'https://ri.ssn.gob.ar/api', // URL de producción SSN
        'token' => '', // Token de autenticación SSN - OBLIGATORIO
        'timeout' => 30, // Timeout en segundos para las consultas
        'retry_attempts' => 3, // Número de intentos en caso de error
        'retry_delay' => 5 // Delay entre intentos en segundos
    ],
    
    // Configuración de horario hábil
    'business_hours' => [
        'enabled' => true, // Si se debe respetar horario hábil
        'days' => [1, 2, 3, 4, 5], // Lunes a viernes (1=lunes, 7=domingo)
        'start_hour' => 8, // Hora de inicio (formato 24h)
        'end_hour' => 19, // Hora de fin (formato 24h)
        'timezone' => 'America/Argentina/Buenos_Aires'
    ],
    
    // Configuración de logs
    'logging' => [
        'enabled' => true,
        'log_file' => __DIR__ . '/logs/rectification_check.log',
        'max_file_size' => 10 * 1024 * 1024, // 10MB
        'max_files' => 30, // Mantener 30 archivos de log
        'level' => 'INFO' // DEBUG, INFO, WARNING, ERROR
    ],
    
    // Configuración de notificaciones (opcional)
    'notifications' => [
        'enabled' => false,
        'email' => [
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_username' => '',
            'smtp_password' => '',
            'from_email' => '',
            'from_name' => 'Sistema Esencia Seguros',
            'to_emails' => [] // Array de emails para notificar
        ]
    ],
    
    // Estados que indican que la rectificación fue aprobada
    'approved_statuses' => ['RECTIFICAR', 'A_RECTIFICAR'],
    
    // Estados que indican que la rectificación fue rechazada
    'rejected_statuses' => ['RECHAZADA', 'DENEGADA'],
    
    // Delay entre consultas para no sobrecargar SSN (en microsegundos)
    'request_delay' => 500000, // 0.5 segundos
]; 