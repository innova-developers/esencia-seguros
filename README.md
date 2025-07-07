<p align="center">
  <img src="./public/logo.png" width="50%" style="background-color:white;padding:50px;border-radius:15px;" alt="Esencia Seguros Logo" />
</p>


<p style="font-size:3em;" align="center">
  🚀 Esencia Seguros API <br>
   <img src="https://img.shields.io/badge/Laravel-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel" />
  <img src="https://img.shields.io/badge/Docker-2496ED?style=flat-square&logo=docker&logoColor=white" alt="Docker" />
  <img src="https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP" />
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL" />
</p>

<p align="center">
 
</p>


API para el sistema de gestión de seguros de Esencia Seguros desarrollada en Laravel con arquitectura hexagonal y Docker.

## 🌟 Características

- 🐳 Dockerizado con PHP, MySQL y phpMyAdmin  
- 🔐 Autenticación con Sanctum  
- 🏗️ Arquitectura hexagonal  
- 🔍 PHPStan para análisis estático  
- ✨ PHP-CS-Fixer para formateo de código  
- ⚙️ GitHub Actions para CI  
- 🔄 Pre-push hooks para verificación de código  
- 🧪 Tests automatizados incluidos  
- 🏢 Configuración específica para Esencia Seguros

## 📋 Requisitos

- Docker 🐳  
- Docker Compose 🐙  
- PHP 8.2 🐘  
- Composer 📦  

## 🛠️ Instalación

### 1. Clonar el Proyecto

```bash
git clone https://github.com/innova-developers/api-esencia-seguros.git
cd esencia-seguros
```

### 2. ⚙️ Configuración Inicial

- Copia el archivo `env.example` a `.env`:
```bash
cp env.example .env
```

- Inicia los contenedores de Docker:
```bash
docker-compose up -d --build
```

- Instala las dependencias de Composer:
```bash
docker-compose exec app composer install
```

- Genera la clave de aplicación:
```bash
docker-compose exec app php artisan key:generate
```

- Ejecuta las migraciones:
```bash
docker-compose exec app php artisan migrate
```

- Ejecuta los tests para verificar que todo funciona:
```bash
docker-compose exec app php artisan test
```

### 3. 🚀 Deploy a Producción

🐳 Docker 

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install docker.io docker-compose git -y
```

- Clona el repositorio:
```bash
git clone https://github.com/innova-developers/esencia-seguros.git
cd esencia-seguros
```

- 📝 Configura el .env para producción:
```bash
nano .env
```

- 📌 Ajusta los valores:
```
APP_ENV=production
APP_DEBUG=false
DB_PASSWORD=esencia_password_2024
```

- ▶️ Inicia los servicios:
```bash
docker-compose up -d --build
```

- 🛡️ Configura el proxy inverso (Nginx):
Crea un archivo de configuración para tu dominio:

```nginx
server {
    listen 80;
    server_name api.esenciaseguros.com;

    location / {
        proxy_pass http://localhost:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### 4.🧪 Ejecutando Tests
```bash
docker-compose exec app php artisan test
```

### 5.🔍 Análisis de Código
```bash
# PHPStan
docker-compose exec app composer analyse

# PHP-CS-Fixer
docker-compose exec app composer format
```

### 6.🛑 Deteniendo los Servicios
```bash
docker-compose down
```

## 📌 Estructura del Proyecto
```
📦 api-esencia-seguros
├── 🏗️ app
│   ├── 🏛️ Domain            # Capa de dominio
│   ├── 🚀 Application       # Casos de uso
│   └── ⚙️ Infrastructure   # Implementaciones
├── 📊 tests                # Pruebas automatizadas
├── 🐳 docker               # Configuración de Docker
└── 📝 .github              # GitHub Actions
```

## 🔧 Configuración de Contenedores

Los contenedores Docker han sido configurados específicamente para Esencia Seguros:

- **esencia-seguros-app**: Aplicación Laravel
- **esencia-seguros-mysql**: Base de datos MySQL
- **esencia-seguros-phpmyadmin**: Interfaz de administración de BD
- **esencia-seguros-nginx**: Servidor web Nginx

### Credenciales de Base de Datos:
- **Base de datos**: `esencia_seguros`
- **Usuario**: `esencia_user`
- **Contraseña**: `esencia_password_2024`
- **Root Password**: `esencia_root_2024`

## 📞 Contacto

Para soporte técnico o consultas sobre el proyecto:
- **Email**: info@esenciaseguros.com
- **Teléfono**: +57 300 123 4567
- **Sitio web**: https://esenciaseguros.com

