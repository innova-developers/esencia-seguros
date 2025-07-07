# Esencia Seguros - API de Gestión de Seguros

## 📋 Información del Proyecto

**Empresa:** Esencia Seguros  
**Proyecto:** API de Gestión de Seguros  
**Versión:** 1.0.0  
**Tecnología:** Laravel 12 + Docker  
**Arquitectura:** Hexagonal (Clean Architecture)  

## 🏢 Información de la Empresa

- **Nombre:** Esencia Seguros
- **Email:** info@esenciaseguros.com
- **Teléfono:** +57 300 123 4567
- **Dirección:** Calle Principal #123, Bogotá, Colombia
- **Sitio Web:** https://esenciaseguros.com
- **NIT:** 900.123.456-7
- **Régimen:** Responsable de IVA

## 🐳 Contenedores Docker

### Servicios Configurados:

1. **esencia-seguros-app** (Laravel Application)
   - Puerto: 8000
   - Imagen: esencia-seguros-api
   - Descripción: Aplicación principal de Laravel

2. **esencia-seguros-mysql** (Base de Datos)
   - Puerto: 3306
   - Base de datos: esencia_seguros
   - Usuario: esencia_user
   - Contraseña: esencia_password_2024
   - Root Password: esencia_root_2024

3. **esencia-seguros-phpmyadmin** (Administración BD)
   - Puerto: 8081
   - URL: http://localhost:8081
   - Usuario: root
   - Contraseña: esencia_root_2024

4. **esencia-seguros-nginx** (Servidor Web)
   - Puerto: 8000
   - Proxy inverso para la aplicación

## 🔧 Configuración del Entorno

### Variables de Entorno Principales:

```bash
# Aplicación
APP_NAME="Esencia Seguros"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Base de Datos
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=esencia_seguros
DB_USERNAME=esencia_user
DB_PASSWORD=esencia_password_2024

# Empresa
ESENCIA_COMPANY_NAME="Esencia Seguros"
ESENCIA_COMPANY_EMAIL="info@esenciaseguros.com"
ESENCIA_COMPANY_PHONE="+57 300 123 4567"
ESENCIA_COMPANY_ADDRESS="Calle Principal #123, Bogotá, Colombia"
ESENCIA_WEBSITE="https://esenciaseguros.com"
ESENCIA_NIT="900.123.456-7"
ESENCIA_REGIMEN="Responsable de IVA"
```

## 🚀 Comandos de Inicio Rápido

```bash
# 1. Clonar el proyecto
git clone https://github.com/esencia-seguros/api-esencia-seguros.git
cd api-esencia-seguros

# 2. Configurar entorno
cp env.example .env

# 3. Iniciar contenedores
docker-compose up -d --build

# 4. Instalar dependencias
docker-compose exec app composer install

# 5. Generar clave de aplicación
docker-compose exec app php artisan key:generate

# 6. Ejecutar migraciones
docker-compose exec app php artisan migrate

# 7. Ejecutar tests
docker-compose exec app php artisan test
```

## 📊 Tipos de Seguros Soportados

- **Auto:** Seguro de Automóviles
- **Home:** Seguro de Hogar
- **Life:** Seguro de Vida
- **Health:** Seguro de Salud
- **Business:** Seguro Empresarial
- **Travel:** Seguro de Viajes

## 🔐 Estados de Pólizas

- **Activo:** Póliza vigente
- **Inactivo:** Póliza suspendida
- **Pendiente:** Póliza en proceso
- **Cancelado:** Póliza cancelada
- **Vencido:** Póliza expirada

## 🌐 Acceso a Servicios

- **API:** http://localhost:8000
- **phpMyAdmin:** http://localhost:8081
- **Documentación API:** http://localhost:8000/api/documentation

## 📞 Soporte Técnico

Para soporte técnico o consultas sobre el proyecto:
- **Email:** info@esenciaseguros.com
- **Teléfono:** +57 300 123 4567
- **Sitio web:** https://esenciaseguros.com

## 🔄 Comandos Útiles

```bash
# Ver logs de la aplicación
docker-compose logs app

# Ejecutar tests
docker-compose exec app php artisan test

# Análisis de código
docker-compose exec app composer analyse

# Formateo de código
docker-compose exec app composer format

# Acceder a la base de datos
docker-compose exec mysql mysql -u esencia_user -p esencia_seguros

# Reiniciar servicios
docker-compose restart

# Detener todos los servicios
docker-compose down
```

## 📝 Notas de Desarrollo

- El proyecto utiliza arquitectura hexagonal para mejor mantenibilidad
- Todas las credenciales están configuradas específicamente para Esencia Seguros
- La configuración está optimizada para el entorno colombiano (COP, timezone, etc.)
- Se incluyen configuraciones de seguridad específicas para el sector de seguros 