<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Paleta de Colores - Esencia Seguros
    |--------------------------------------------------------------------------
    |
    | Este archivo contiene la paleta de colores oficial de Esencia Seguros
    | extraída del logo y CSS de la empresa. Estos colores se utilizan
    | en todos los templates Blade y componentes de la aplicación.
    |
    */

    'primary' => [
        'main' => '#374661',      // Color principal del footer (proteccion background)
        'light' => '#4a5a7a',     // Versión más clara del principal
        'dark' => '#2a3547',      // Versión más oscura del principal
        'accent' => '#5c6b8a',    // Acento del color principal
    ],

    'secondary' => [
        'main' => '#dadada',      // Color de fondo del body
        'light' => '#f5f5f5',     // Versión más clara del secundario
        'dark' => '#b8b8b8',      // Versión más oscura del secundario
    ],

    'text' => [
        'primary' => '#374661',   // Color principal del texto
        'secondary' => '#878787', // Color secundario del texto (footer)
        'light' => '#ffffff',     // Texto claro sobre fondos oscuros
        'muted' => '#6c757d',     // Texto atenuado
    ],

    'background' => [
        'primary' => '#ffffff',   // Fondo principal
        'secondary' => '#dadada', // Fondo secundario
        'dark' => '#374661',      // Fondo oscuro
        'light' => '#f8f9fa',     // Fondo claro
    ],

    'status' => [
        'success' => '#28a745',   // Verde para estados exitosos
        'warning' => '#ffc107',   // Amarillo para advertencias
        'danger' => '#dc3545',    // Rojo para errores
        'info' => '#17a2b8',      // Azul para información
        'active' => '#28a745',    // Verde para pólizas activas
        'inactive' => '#6c757d',  // Gris para pólizas inactivas
        'pending' => '#ffc107',   // Amarillo para pólizas pendientes
        'cancelled' => '#dc3545', // Rojo para pólizas canceladas
        'expired' => '#fd7e14',   // Naranja para pólizas vencidas
    ],

    'insurance_types' => [
        'auto' => '#007bff',      // Azul para seguros de auto
        'home' => '#28a745',      // Verde para seguros de hogar
        'life' => '#dc3545',      // Rojo para seguros de vida
        'health' => '#17a2b8',    // Azul claro para seguros de salud
        'business' => '#6f42c1',  // Púrpura para seguros empresariales
        'travel' => '#fd7e14',    // Naranja para seguros de viajes
    ],

    'gradients' => [
        'primary' => 'linear-gradient(135deg, #374661 0%, #4a5a7a 100%)',
        'secondary' => 'linear-gradient(135deg, #dadada 0%, #f5f5f5 100%)',
        'success' => 'linear-gradient(135deg, #28a745 0%, #20c997 100%)',
        'warning' => 'linear-gradient(135deg, #ffc107 0%, #ffca2c 100%)',
        'danger' => 'linear-gradient(135deg, #dc3545 0%, #e74c3c 100%)',
    ],

    'shadows' => [
        'light' => '0 2px 4px rgba(55, 70, 97, 0.1)',
        'medium' => '0 4px 8px rgba(55, 70, 97, 0.15)',
        'heavy' => '0 8px 16px rgba(55, 70, 97, 0.2)',
        'card' => '0 2px 10px rgba(0, 0, 0, 0.1)',
    ],

    'borders' => [
        'light' => '#e9ecef',
        'medium' => '#dee2e6',
        'dark' => '#374661',
        'radius' => '8px',
        'radius_small' => '4px',
    ],

    'fonts' => [
        'primary' => "'Montserrat', sans-serif",
        'secondary' => "'Roboto', sans-serif",
        'weights' => [
            'light' => 300,
            'regular' => 400,
            'medium' => 500,
            'semibold' => 600,
            'bold' => 700,
        ],
    ],

    'spacing' => [
        'xs' => '0.25rem',
        'sm' => '0.5rem',
        'md' => '1rem',
        'lg' => '1.5rem',
        'xl' => '3rem',
        'xxl' => '5rem',
    ],

    'breakpoints' => [
        'mobile' => '767.98px',
        'tablet' => '991.98px',
        'desktop' => '1199.98px',
        'large' => '1400px',
    ],
]; 