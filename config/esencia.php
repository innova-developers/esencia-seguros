<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Esencia Seguros
    |--------------------------------------------------------------------------
    |
    | Este archivo contiene la configuración específica para la empresa
    | Esencia Seguros, incluyendo información de contacto, configuración
    | de la API y parámetros del negocio.
    |
    */

    'company' => [
        'name' => env('ESENCIA_COMPANY_NAME', 'Esencia Seguros'),
        'email' => env('ESENCIA_COMPANY_EMAIL', 'info@esenciaseguros.com'),
        'phone' => env('ESENCIA_COMPANY_PHONE', '+57 300 123 4567'),
        'address' => env('ESENCIA_COMPANY_ADDRESS', 'Calle Principal #123, Bogotá, Colombia'),
        'website' => env('ESENCIA_WEBSITE', 'https://esenciaseguros.com'),
        'nit' => env('ESENCIA_NIT', '900.123.456-7'),
        'regimen' => env('ESENCIA_REGIMEN', 'Responsable de IVA'),
    ],

    'api' => [
        'version' => env('API_VERSION', 'v1'),
        'prefix' => env('API_PREFIX', 'api'),
        'rate_limit' => env('API_RATE_LIMIT', '60,1'),
        'timeout' => env('API_TIMEOUT', 30),
    ],

    'business' => [
        'default_currency' => 'COP',
        'default_language' => 'es',
        'timezone' => 'America/Bogota',
        'date_format' => 'Y-m-d',
        'datetime_format' => 'Y-m-d H:i:s',
    ],

    'insurance' => [
        'types' => [
            'auto' => 'Seguro de Automóviles',
            'home' => 'Seguro de Hogar',
            'life' => 'Seguro de Vida',
            'health' => 'Seguro de Salud',
            'business' => 'Seguro Empresarial',
            'travel' => 'Seguro de Viajes',
        ],
        'status' => [
            'active' => 'Activo',
            'inactive' => 'Inactivo',
            'pending' => 'Pendiente',
            'cancelled' => 'Cancelado',
            'expired' => 'Vencido',
        ],
    ],

    'notifications' => [
        'email' => [
            'from_address' => env('MAIL_FROM_ADDRESS', 'hello@esenciaseguros.com'),
            'from_name' => env('MAIL_FROM_NAME', 'Esencia Seguros'),
        ],
        'sms' => [
            'provider' => env('SMS_PROVIDER', 'twilio'),
            'from_number' => env('SMS_FROM_NUMBER', '+573001234567'),
        ],
    ],

    'security' => [
        'password_min_length' => 8,
        'password_require_special' => true,
        'session_lifetime' => env('SESSION_LIFETIME', 120),
        'max_login_attempts' => 5,
        'lockout_duration' => 15, // minutos
    ],
]; 