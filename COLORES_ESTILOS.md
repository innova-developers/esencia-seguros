# Paleta de Colores y Estilos - Esencia Seguros

## 🎨 Paleta de Colores Extraída del Logo

Basándome en el CSS proporcionado y el logo de la empresa, he extraído la siguiente paleta de colores oficial:

### Colores Principales
- **Primary (Principal):** `#374661` - Color principal del footer y elementos destacados
- **Primary Light:** `#4a5a7a` - Versión más clara del principal
- **Primary Dark:** `#2a3547` - Versión más oscura del principal
- **Secondary (Secundario):** `#dadada` - Color de fondo del body

### Colores de Texto
- **Text Primary:** `#374661` - Texto principal
- **Text Secondary:** `#878787` - Texto secundario (footer)
- **Text Light:** `#ffffff` - Texto claro sobre fondos oscuros
- **Text Muted:** `#6c757d` - Texto atenuado

### Estados de Pólizas
- **Active (Activo):** `#28a745` - Verde para pólizas vigentes
- **Inactive (Inactivo):** `#6c757d` - Gris para pólizas suspendidas
- **Pending (Pendiente):** `#ffc107` - Amarillo para pólizas en proceso
- **Cancelled (Cancelado):** `#dc3545` - Rojo para pólizas canceladas
- **Expired (Vencido):** `#fd7e14` - Naranja para pólizas expiradas

### Tipos de Seguro
- **Auto:** `#007bff` - Azul para seguros de automóviles
- **Home:** `#28a745` - Verde para seguros de hogar
- **Life:** `#dc3545` - Rojo para seguros de vida
- **Health:** `#17a2b8` - Azul claro para seguros de salud
- **Business:** `#6f42c1` - Púrpura para seguros empresariales
- **Travel:** `#fd7e14` - Naranja para seguros de viajes

## 📁 Archivos Creados

### 1. Configuración de Colores
**Archivo:** `config/colors.php`
- Paleta completa de colores
- Variables de espaciado
- Configuración de fuentes
- Sombras y bordes
- Breakpoints responsive

### 2. Estilos CSS Base
**Archivo:** `resources/css/esencia-seguros.css`
- Variables CSS personalizadas
- Estilos base del footer
- Componentes de seguro
- Estados de pólizas
- Botones personalizados
- Responsive design

### 3. Helper de Colores
**Archivo:** `app/Helpers/ColorHelper.php`
- Métodos para obtener colores
- Generación de clases CSS
- Estilos inline
- Utilidades para tipos de seguro y estados

### 4. Componente Blade
**Archivo:** `resources/views/components/esencia-card.blade.php`
- Tarjeta reutilizable para seguros
- Soporte para tipos y estados
- Integración con el helper de colores

### 5. Vista de Demo
**Archivo:** `resources/views/examples/esencia-demo.blade.php`
- Demostración de todos los colores
- Ejemplos de componentes
- Paleta visual completa

## 🚀 Cómo Usar

### En Vistas Blade

```php
@php
    use App\Helpers\ColorHelper;
@endphp

<!-- Usar colores directamente -->
<div style="background-color: {{ ColorHelper::getPrimaryColor() }};">
    Contenido
</div>

<!-- Usar clases CSS -->
<div class="insurance-card">
    <span class="{{ ColorHelper::getInsuranceTypeClass('auto') }}">
        Seguro de Auto
    </span>
    <span class="{{ ColorHelper::getStatusClass('active') }}">
        Activo
    </span>
</div>

<!-- Usar el componente -->
<x-esencia-card 
    title="Mi Seguro" 
    type="home" 
    status="active">
    Contenido de la tarjeta
</x-esencia-card>
```

### En CSS

```css
/* Usar variables CSS */
.mi-elemento {
    background-color: var(--esencia-primary);
    color: var(--esencia-text-light);
    border-radius: var(--esencia-border-radius);
    box-shadow: var(--esencia-shadow-card);
}

/* Usar clases predefinidas */
.insurance-type.auto { background-color: var(--esencia-auto); }
.status-badge.status-active { background-color: var(--esencia-success); }
.btn-esencia { background: var(--esencia-primary); }
```

### En JavaScript

```javascript
// Acceder a variables CSS
const primaryColor = getComputedStyle(document.documentElement)
    .getPropertyValue('--esencia-primary');
```

## 📱 Responsive Design

Los estilos incluyen breakpoints específicos:
- **Mobile:** `767.98px`
- **Tablet:** `991.98px`
- **Desktop:** `1199.98px`
- **Large:** `1400px`

## 🎯 Tipografía

- **Fuente Principal:** Montserrat (Google Fonts)
- **Fuente Secundaria:** Roboto
- **Pesos:** 300, 400, 500, 600, 700

## 🔧 Configuración

Para usar los estilos, asegúrate de:

1. **Incluir el CSS:**
   ```html
   <link rel="stylesheet" href="{{ asset('css/esencia-seguros.css') }}">
   ```

2. **Cargar la fuente:**
   ```html
   <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
   ```

3. **Usar el helper en las vistas:**
   ```php
   use App\Helpers\ColorHelper;
   ```

## 🎨 Personalización

Para modificar colores:

1. **Editar `config/colors.php`** - Cambiar valores de colores
2. **Editar `resources/css/esencia-seguros.css`** - Modificar variables CSS
3. **Regenerar assets** si usas Vite/Webpack

## 📋 Checklist de Implementación

- [x] Extraer colores del logo y CSS
- [x] Crear paleta de colores completa
- [x] Configurar variables CSS
- [x] Crear helper de PHP
- [x] Desarrollar componentes Blade
- [x] Crear vista de demostración
- [x] Documentar uso y ejemplos
- [x] Configurar responsive design
- [x] Integrar con configuración de Laravel

## 🎯 Próximos Pasos

1. **Compilar assets** con Vite
2. **Crear más componentes** específicos para seguros
3. **Desarrollar tema completo** para el dashboard
4. **Implementar modo oscuro** (opcional)
5. **Crear iconografía** específica para tipos de seguro 