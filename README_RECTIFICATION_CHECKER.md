# Checker de Rectificaciones SSN

Este sistema verifica automáticamente el estado de las solicitudes de rectificación pendientes en la SSN (Superintendencia de Seguros de la Nación).

## Archivos del Sistema

- `check_rectification_status.php` - Script principal que se ejecuta como cronjob
- `rectification_config.php` - Archivo de configuración
- `test_rectification_check.php` - Script de prueba para verificar la configuración
- `README_RECTIFICATION_CHECKER.md` - Este archivo de documentación

## Características

- ✅ Verifica automáticamente el estado de rectificaciones pendientes
- ✅ Respeta horario hábil (Lunes a Viernes, 8:00 a 19:00 hs)
- ✅ Logs detallados de todas las operaciones
- ✅ Manejo de errores robusto
- ✅ Reintentos automáticos en caso de fallo
- ✅ Actualización automática del estado en la base de datos
- ✅ Registro de actividades en el log de auditoría

## Instalación

### 1. Configuración de Base de Datos

Editar `rectification_config.php` y actualizar la configuración de la base de datos:

```php
'database' => [
    'host' => 'localhost',           // Host de la base de datos
    'dbname' => 'esencia_seguros',   // Nombre de la base de datos
    'username' => 'root',            // Usuario de la base de datos
    'password' => '',                // Contraseña de la base de datos
    'charset' => 'utf8mb4'
],
```

### 2. Configuración SSN

Configurar el token de autenticación SSN (OBLIGATORIO):

```php
'ssn' => [
    'base_url' => 'https://ri.ssn.gob.ar/api', // URL de producción SSN
    'token' => 'TU_TOKEN_SSN_AQUI',            // Token de autenticación SSN
    'timeout' => 30,                           // Timeout en segundos
    'retry_attempts' => 3,                     // Número de intentos
    'retry_delay' => 5                         // Delay entre intentos
],
```

### 3. Configuración de Horario Hábil

El sistema respeta automáticamente el horario hábil de la SSN:
- **Días**: Lunes a Viernes
- **Horario**: 8:00 a 19:00 hs (hora de Argentina)
- **Timezone**: America/Argentina/Buenos_Aires

### 4. Configuración de Logs

Los logs se guardan automáticamente en:
- **Archivo**: `logs/rectification_check.log`
- **Rotación**: Máximo 30 archivos de 10MB cada uno
- **Nivel**: INFO (puede cambiarse a DEBUG, WARNING, ERROR)

## Configuración del Cronjob

### En cPanel

1. Ir a **Cron Jobs** en el panel de control
2. Agregar nuevo cronjob con la siguiente configuración:

```
Comando: /usr/bin/php /ruta/completa/a/tu/proyecto/check_rectification_status.php
Frecuencia: Cada 30 minutos
Horario: */30 8-18 * * 1-5
```

### En Linux/Unix

Agregar al crontab del usuario:

```bash
# Editar crontab
crontab -e

# Agregar la línea:
*/30 8-18 * * 1-5 /usr/bin/php /ruta/completa/a/tu/proyecto/check_rectification_status.php
```

### Explicación del Horario

- `*/30` - Cada 30 minutos
- `8-18` - De 8:00 a 18:59 (hasta las 19:00)
- `* * 1-5` - Todos los meses, todos los días, lunes a viernes

## Pruebas

### 1. Verificar Configuración

Ejecutar el script de prueba:

```bash
php test_rectification_check.php
```

Este script verificará:
- ✅ Configuración de base de datos
- ✅ Configuración SSN
- ✅ Horario hábil
- ✅ Directorio de logs
- ✅ Estados de rectificación

### 2. Ejecución Manual

Para probar el script manualmente:

```bash
php check_rectification_status.php
```

### 3. Ejecución por Web

También se puede ejecutar accediendo a:
```
https://tu-dominio.com/check_rectification_status.php?run=1
```

## Estados de Rectificación

### Estados que indican Aprobación
- `RECTIFICAR`
- `A_RECTIFICAR`

### Estados que indican Rechazo
- `RECHAZADA`
- `DENEGADA`

### Flujo de Estados
1. `PRESENTADO` → Usuario solicita rectificación
2. `RECTIFICACION_PENDIENTE` → Solicitud enviada a SSN
3. `A_RECTIFICAR` → SSN aprueba la rectificación
4. `VACIO` → Usuario crea nueva presentación corregida

## Logs y Monitoreo

### Archivo de Logs
- **Ubicación**: `logs/rectification_check.log`
- **Formato**: `[YYYY-MM-DD HH:MM:SS] [LEVEL] Mensaje`
- **Niveles**: INFO, WARNING, ERROR

### Ejemplo de Log
```
[2025-07-06 10:30:00] [INFO] Iniciando verificación de estado de rectificaciones
[2025-07-06 10:30:01] [INFO] Encontradas 2 rectificaciones pendientes
[2025-07-06 10:30:02] [INFO] Verificando presentación 1 (2025-01)
[2025-07-06 10:30:03] [INFO] Rectificación aprobada para presentación 1. Nuevo estado: A_RECTIFICAR
[2025-07-06 10:30:04] [INFO] Estado actualizado exitosamente para presentación 1
[2025-07-06 10:30:05] [INFO] Verificación completada. Revisadas: 2, Actualizadas: 1, Errores: 0
```

### Actividad en Base de Datos
Todas las actualizaciones se registran en la tabla `activity_logs`:
- **Usuario**: Sistema (ID: 1)
- **Acción**: `RECTIFICATION_APPROVED`
- **IP**: 127.0.0.1
- **User Agent**: CronJob/RectificationChecker

## Troubleshooting

### Problemas Comunes

#### 1. Token SSN no configurado
```
[ERROR] HTTP Error 401 para presentación 1: Unauthorized
```
**Solución**: Configurar el token SSN en `rectification_config.php`

#### 2. Error de conexión a base de datos
```
[ERROR] Error conectando a la base de datos: SQLSTATE[HY000] [2002] No such file or directory
```
**Solución**: Verificar configuración de base de datos en `rectification_config.php`

#### 3. Script no ejecuta consultas
```
[INFO] Fuera de horario hábil. No se realizarán consultas.
```
**Solución**: El script respeta el horario hábil. Es normal fuera de 8:00-19:00 hs de lunes a viernes.

#### 4. Error de permisos en logs
```
[ERROR] Error escribiendo log: Permission denied
```
**Solución**: Verificar permisos del directorio `logs/` (debe ser 755)

### Verificación de Estado

Para verificar que el cronjob esté funcionando:

1. **Revisar logs**:
```bash
tail -f logs/rectification_check.log
```

2. **Verificar en base de datos**:
```sql
SELECT * FROM presentations WHERE estado = 'RECTIFICACION_PENDIENTE';
SELECT * FROM activity_logs WHERE action = 'RECTIFICATION_APPROVED' ORDER BY created_at DESC LIMIT 10;
```

3. **Verificar crontab**:
```bash
crontab -l
```

## Seguridad

### Consideraciones de Seguridad

1. **Token SSN**: Mantener el token SSN seguro y no compartirlo
2. **Permisos de archivos**: Configurar permisos adecuados (644 para archivos, 755 para directorios)
3. **Logs**: Los logs pueden contener información sensible, proteger el directorio
4. **Base de datos**: Usar usuario de base de datos con permisos mínimos necesarios

### Recomendaciones

1. **Backup**: Hacer backup regular de la configuración y logs
2. **Monitoreo**: Configurar alertas si el script falla
3. **Rotación de logs**: El sistema rota automáticamente los logs
4. **Testing**: Probar en ambiente de desarrollo antes de producción

## Soporte

Para problemas o consultas:
1. Revisar los logs en `logs/rectification_check.log`
2. Ejecutar `php test_rectification_check.php` para diagnóstico
3. Verificar la configuración en `rectification_config.php`
4. Revisar el estado de la base de datos

## Changelog

### v1.0.0 (2025-07-06)
- ✅ Implementación inicial del checker de rectificaciones
- ✅ Soporte para presentaciones semanales y mensuales
- ✅ Verificación de horario hábil
- ✅ Sistema de logs robusto
- ✅ Manejo de errores y reintentos
- ✅ Integración con sistema de auditoría 