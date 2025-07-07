<?php

namespace App\Helpers;

class ColorHelper
{
    /**
     * Obtiene un color de la paleta de Esencia Seguros
     *
     * @param string $category
     * @param string $variant
     * @return string
     */
    public static function getColor(string $category, string $variant = 'main'): string
    {
        $colors = config('colors');
        
        if (isset($colors[$category][$variant])) {
            return $colors[$category][$variant];
        }
        
        if (isset($colors[$category])) {
            return is_array($colors[$category]) ? $colors[$category]['main'] : $colors[$category];
        }
        
        return '#000000'; // Color por defecto
    }

    /**
     * Obtiene el color de un tipo de seguro
     *
     * @param string $type
     * @return string
     */
    public static function getInsuranceTypeColor(string $type): string
    {
        return self::getColor('insurance_types', $type);
    }

    /**
     * Obtiene el color de un estado
     *
     * @param string $status
     * @return string
     */
    public static function getStatusColor(string $status): string
    {
        return self::getColor('status', $status);
    }

    /**
     * Obtiene el color primario
     *
     * @param string $variant
     * @return string
     */
    public static function getPrimaryColor(string $variant = 'main'): string
    {
        return self::getColor('primary', $variant);
    }

    /**
     * Obtiene el color secundario
     *
     * @param string $variant
     * @return string
     */
    public static function getSecondaryColor(string $variant = 'main'): string
    {
        return self::getColor('secondary', $variant);
    }

    /**
     * Obtiene un gradiente
     *
     * @param string $type
     * @return string
     */
    public static function getGradient(string $type): string
    {
        return self::getColor('gradients', $type);
    }

    /**
     * Obtiene una sombra
     *
     * @param string $type
     * @return string
     */
    public static function getShadow(string $type): string
    {
        return self::getColor('shadows', $type);
    }

    /**
     * Obtiene el radio de borde
     *
     * @param string $size
     * @return string
     */
    public static function getBorderRadius(string $size = 'radius'): string
    {
        $borders = config('colors.borders');
        
        if ($size === 'small') {
            return $borders['radius_small'];
        }
        
        return $borders['radius'];
    }

    /**
     * Obtiene el espaciado
     *
     * @param string $size
     * @return string
     */
    public static function getSpacing(string $size): string
    {
        return config("colors.spacing.{$size}", '1rem');
    }

    /**
     * Obtiene la fuente
     *
     * @param string $type
     * @return string
     */
    public static function getFont(string $type = 'primary'): string
    {
        return config("colors.fonts.{$type}", "'Montserrat', sans-serif");
    }

    /**
     * Obtiene el peso de la fuente
     *
     * @param string $weight
     * @return int
     */
    public static function getFontWeight(string $weight): int
    {
        return config("colors.fonts.weights.{$weight}", 400);
    }

    /**
     * Genera una clase CSS para un tipo de seguro
     *
     * @param string $type
     * @return string
     */
    public static function getInsuranceTypeClass(string $type): string
    {
        return "insurance-type {$type}";
    }

    /**
     * Genera una clase CSS para un estado
     *
     * @param string $status
     * @return string
     */
    public static function getStatusClass(string $status): string
    {
        return "status-badge status-{$status}";
    }

    /**
     * Genera estilos inline para un color de fondo
     *
     * @param string $category
     * @param string $variant
     * @return string
     */
    public static function getBackgroundStyle(string $category, string $variant = 'main'): string
    {
        $color = self::getColor($category, $variant);
        return "background-color: {$color};";
    }

    /**
     * Genera estilos inline para un color de texto
     *
     * @param string $category
     * @param string $variant
     * @return string
     */
    public static function getTextColorStyle(string $category, string $variant = 'main'): string
    {
        $color = self::getColor($category, $variant);
        return "color: {$color};";
    }

    /**
     * Genera estilos inline para un borde
     *
     * @param string $color
     * @param string $width
     * @return string
     */
    public static function getBorderStyle(string $color = 'light', string $width = '1px'): string
    {
        $borderColor = self::getColor('borders', $color);
        return "border: {$width} solid {$borderColor};";
    }
} 