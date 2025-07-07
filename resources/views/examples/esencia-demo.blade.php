<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esencia Seguros - Demo de Componentes</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/esencia-seguros.css') }}">
    <style>
        body {
            padding: 2rem;
            background: var(--esencia-secondary);
        }
        .demo-section {
            margin-bottom: 3rem;
        }
        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 1rem;
        }
        .color-palette {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .color-swatch {
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            color: white;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <header style="text-align: center; margin-bottom: 3rem;">
            <img src="{{ asset('logo.png') }}" alt="Esencia Seguros" style="max-width: 300px; margin-bottom: 1rem;">
            <h1 style="color: var(--esencia-text-primary); font-weight: 700;">Demo de Componentes - Esencia Seguros</h1>
            <p style="color: var(--esencia-text-secondary);">Paleta de colores y componentes del sistema</p>
        </header>

        <!-- Paleta de Colores -->
        <section class="demo-section">
            <h2 class="titulo">Paleta de Colores</h2>
            
            <h3>Colores Principales</h3>
            <div class="color-palette">
                <div class="color-swatch" style="background: var(--esencia-primary);">Primary</div>
                <div class="color-swatch" style="background: var(--esencia-primary-light);">Primary Light</div>
                <div class="color-swatch" style="background: var(--esencia-primary-dark);">Primary Dark</div>
                <div class="color-swatch" style="background: var(--esencia-secondary); color: var(--esencia-text-primary);">Secondary</div>
            </div>

            <h3>Estados</h3>
            <div class="color-palette">
                <div class="color-swatch" style="background: var(--esencia-success);">Success</div>
                <div class="color-swatch" style="background: var(--esencia-warning); color: var(--esencia-text-primary);">Warning</div>
                <div class="color-swatch" style="background: var(--esencia-danger);">Danger</div>
                <div class="color-swatch" style="background: var(--esencia-info);">Info</div>
            </div>

            <h3>Tipos de Seguro</h3>
            <div class="color-palette">
                <div class="color-swatch" style="background: var(--esencia-auto);">Auto</div>
                <div class="color-swatch" style="background: var(--esencia-home);">Home</div>
                <div class="color-swatch" style="background: var(--esencia-life);">Life</div>
                <div class="color-swatch" style="background: var(--esencia-health);">Health</div>
                <div class="color-swatch" style="background: var(--esencia-business);">Business</div>
                <div class="color-swatch" style="background: var(--esencia-travel);">Travel</div>
            </div>
        </section>

        <!-- Componentes de Seguro -->
        <section class="demo-section">
            <h2 class="titulo">Componentes de Seguro</h2>
            
            <div class="demo-grid">
                <!-- Tarjeta de Auto -->
                <x-esencia-card 
                    title="Seguro de Automóvil" 
                    subtitle="Protección completa para tu vehículo"
                    type="auto"
                    status="active"
                    :showLogo="true">
                    <p>Este seguro te brinda protección integral para tu automóvil, incluyendo cobertura por daños, robo y responsabilidad civil.</p>
                    <div style="margin-top: 1rem;">
                        <button class="btn-esencia">Ver Detalles</button>
                    </div>
                </x-esencia-card>

                <!-- Tarjeta de Hogar -->
                <x-esencia-card 
                    title="Seguro de Hogar" 
                    subtitle="Protege tu patrimonio familiar"
                    type="home"
                    status="pending">
                    <p>Cobertura completa para tu hogar, incluyendo daños estructurales, contenido y responsabilidad civil.</p>
                    <div style="margin-top: 1rem;">
                        <button class="btn-esencia-secondary">Solicitar Cotización</button>
                    </div>
                </x-esencia-card>

                <!-- Tarjeta de Vida -->
                <x-esencia-card 
                    title="Seguro de Vida" 
                    subtitle="Protección para tu familia"
                    type="life"
                    status="active">
                    <p>Garantiza el futuro económico de tu familia con nuestra cobertura de vida que se adapta a tus necesidades.</p>
                    <div style="margin-top: 1rem;">
                        <button class="btn-esencia">Consultar Planes</button>
                    </div>
                </x-esencia-card>

                <!-- Tarjeta de Salud -->
                <x-esencia-card 
                    title="Seguro de Salud" 
                    subtitle="Cuidado integral de tu salud"
                    type="health"
                    status="inactive">
                    <p>Acceso a la mejor atención médica con cobertura nacional e internacional para ti y tu familia.</p>
                    <div style="margin-top: 1rem;">
                        <button class="btn-esencia-secondary">Renovar Póliza</button>
                    </div>
                </x-esencia-card>
            </div>
        </section>

        <!-- Estados de Pólizas -->
        <section class="demo-section">
            <h2 class="titulo">Estados de Pólizas</h2>
            
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center;">
                <span class="status-badge status-active">Activo</span>
                <span class="status-badge status-inactive">Inactivo</span>
                <span class="status-badge status-pending">Pendiente</span>
                <span class="status-badge status-cancelled">Cancelado</span>
                <span class="status-badge status-expired">Vencido</span>
            </div>
        </section>

        <!-- Botones -->
        <section class="demo-section">
            <h2 class="titulo">Botones</h2>
            
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center;">
                <button class="btn-esencia">Botón Primario</button>
                <button class="btn-esencia-secondary">Botón Secundario</button>
            </div>
        </section>

        <!-- Footer de Ejemplo -->
        <footer>
            <div class="container">
                <div class="row">
                    <div class="col">
                        <div class="logo">
                            <img src="{{ asset('logo.png') }}" alt="Esencia Seguros">
                        </div>
                    </div>
                    <div class="col">
                        <div class="datos">
                            <div>
                                <p class="titulo">Contacto</p>
                                <p>info@esenciaseguros.com</p>
                                <p>+57 300 123 4567</p>
                                <p>Calle Principal #123, Bogotá</p>
                            </div>
                            <div>
                                <p class="titulo">Protección</p>
                                <span class="proteccion">Seguro de Automóviles</span>
                                <span class="proteccion">Seguro de Hogar</span>
                                <span class="proteccion">Seguro de Vida</span>
                                <span class="proteccion">Seguro de Salud</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</body>
</html> 