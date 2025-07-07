<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Esencia Seguros')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-color);
        }

        main.container {
            flex: 1 0 auto;
        }

        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #3b82f6;
            --accent-color: #60a5fa;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-color: #f8fafc;
            --dark-color: #1e293b;
            --esencia-primary: #374661;
            --esencia-primary-light: #4a5a7a;
            --esencia-primary-dark: #2a3547;
            --esencia-secondary: #dadada;
            --esencia-text-primary: #374661;
            --esencia-text-secondary: #878787;
            --esencia-success: #28a745;
            --esencia-danger: #dc3545;
            --esencia-border-light: #e9ecef;
            --esencia-shadow-card: 0 2px 10px rgba(0, 0, 0, 0.1);

        }
        
        .btn-logout {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .btn-logout:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            transform: translateY(-1px);
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--esencia-primary) 0%, var(--esencia-primary-light) 100%);
            box-shadow: 0 2px 10px rgba(55, 70, 97, 0.2);
            height: 100px;
        }

        .navbar-brand img {
            max-height: 60px;
        }

        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            color: white !important;
            transform: translateY(-1px);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .card {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .footer {
            background-color: var(--dark-color);
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        
        .status-vacio { background-color: #e5e7eb; color: #374151; }
        .status-cargado { background-color: #dbeafe; color: #1e40af; }
        .status-presentado { background-color: #d1fae5; color: #065f46; }
        .status-pendiente { background-color: #fef3c7; color: #92400e; }
        .status-rectificacion { background-color: #fecaca; color: #991b1b; }
        .status-a-rectificar { background-color: #fde68a; color: #92400e; }

        .footer {
            background: var(--esencia-primary);
            color: white;
            text-align: center;
            padding: 2rem 1rem;
            margin-top: 3rem;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .footer-left {
            text-align: left;
        }

        .footer-center {
            text-align: center;
        }

        .footer-right {
            text-align: right;
        }

        .footer-logo {
            max-height: 40px;
            filter: brightness(0) invert(1);
            transition: all 0.3s ease;
        }

        .footer-logo:hover {
            transform: scale(1.05);
        }

        .footer-link {
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-link:hover {
            color: var(--esencia-secondary);
            text-decoration: underline;
        }

        

        @media (max-width: 768px) {
            .footer-content {
                flex-direction: column;
                text-align: center;
            }
            
            .footer-left,
            .footer-right {
                text-align: center;
            }
        }

        @media (max-width: 768px) {
            .welcome-section {
                padding: 2rem 1.5rem;
                margin: 1rem 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1.5rem;
            }
        }
    </style>
    
    @stack('styles')
    
    <!-- Dashboard Styles -->
    <style>
        :root {
            --esencia-primary: #374661;
            --esencia-primary-light: #4a5a7a;
            --esencia-primary-dark: #2a3547;
            --esencia-secondary: #dadada;
            --esencia-text-primary: #374661;
            --esencia-text-secondary: #878787;
            --esencia-success: #28a745;
            --esencia-danger: #dc3545;
            --esencia-warning: #ffc107;
            --esencia-info: #17a2b8;
            --esencia-border-light: #e9ecef;
            --esencia-shadow-card: 0 2px 10px rgba(0, 0, 0, 0.1);
            --esencia-gradient: linear-gradient(135deg, var(--esencia-primary) 0%, var(--esencia-primary-light) 100%);
        }

        .welcome-section {
            text-align: center;
            padding: 2rem 0;
            background: var(--esencia-gradient);
            color: white;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: var(--esencia-shadow-card);
        }

        .welcome-section h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .welcome-section p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--esencia-text-primary);
            font-weight: 600;
            font-size: 1.8rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--esencia-gradient);
            border-radius: 2px;
        }

        .stats-section {
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--esencia-shadow-card);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
            height: 100%;
            border: 1px solid var(--esencia-border-light);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--esencia-gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(55, 70, 97, 0.2);
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-icon {
            font-size: 2.5rem;
            color: var(--esencia-primary);
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1);
        }

        .stat-content h3 {
            font-size: 2rem;
            font-weight: bold;
            color: var(--esencia-text-primary);
            margin-bottom: 0.5rem;
        }

        .stat-content p {
            color: var(--esencia-text-secondary);
            font-size: 1rem;
            margin: 0;
            font-weight: 500;
        }

        .token-info {
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 5px;
            border: 1px solid var(--esencia-border-light);
        }

        .token-info code {
            font-size: 0.9rem;
            color: var(--esencia-text-primary);
        }

        /* Estados de conexión SSN */
        .card-header.bg-primary {
            background: var(--esencia-gradient) !important;
        }

        .text-success {
            color: var(--esencia-success) !important;
        }

        .text-warning {
            color: var(--esencia-warning) !important;
        }

        .text-danger {
            color: var(--esencia-danger) !important;
        }

        .text-info {
            color: var(--esencia-info) !important;
        }

        .bg-success {
            background-color: var(--esencia-success) !important;
        }

        .bg-warning {
            background-color: var(--esencia-warning) !important;
        }

        .bg-danger {
            background-color: var(--esencia-danger) !important;
        }

        .bg-info {
            background-color: var(--esencia-info) !important;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .welcome-section h1 {
                font-size: 2rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-icon {
                font-size: 2rem;
            }
            
            .stat-content h3 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .welcome-section {
                padding: 1.5rem 0;
            }

            .welcome-section h1 {
                font-size: 1.8rem;
            }

            .section-title {
                font-size: 1.5rem;
            }

            .stat-card {
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->

    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="{{ asset('logo.png') }}" alt="Esencia Seguros">
            </a>
            
            <div class="navbar-nav" style="display: flex; justify-content:space-between; width: 100%;">
                <div class="nav-options" style="display: flex; justify-content: left; align-items: left;">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('dashboard') }}">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('weekly-presentations.index') }}">
                            <i class="fas fa-calendar-week me-1"></i>Presentaciones Semanales
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('monthly-presentations.index') }}">
                            <i class="fas fa-calendar-alt me-1"></i>Presentaciones Mensuales
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('audit.logs') }}">
                            <i class="fas fa-clipboard-list me-1"></i>Auditoría
                        </a>
                    </li>
                </div>
                <div class="nav-options" style="display: flex; justify-content: right; align-items: right;">
                <span class="navbar-text me-3" style="color: #fff;">
                    <i class="fas fa-user me-1" style="color: #fff;"></i>
                    {{ auth()->user()->name ?? 'Usuario' }}
                </span>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-logout">
                        <i class="fas fa-sign-out-alt me-1"></i>
                        Cerrar sesión
                    </button>
                </form>
                </div>
            </div>
        </div>
    </nav>



    <!-- Main Content -->
    <main class="container ">
        @yield('content')
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">    
                <div class="footer-center" style="margin:auto !important">
                    <p class="mb-0">
                        &copy; {{ date('Y') }} -   Desarrollado por 
                        <a href="https://innovadevelopers.com" target="_blank" class="footer-link">
                            <img src="{{ asset('innova-logo.png') }}" alt="Innova Developers" class="footer-logo me-1">
                        </a>
                    </p>
                </div>
                
                
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @stack('scripts')
</body>
</html> 