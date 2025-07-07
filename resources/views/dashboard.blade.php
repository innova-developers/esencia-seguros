@extends('layouts.app')

@section('title', 'Dashboard - Esencia Seguros')

@section('content')

    <div class="container">
        <!-- Session Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-times-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Welcome Section -->
        <div class="welcome-section" style="background: unset !important;color:var(--esencia-text-primary);border: unset !important;box-shadow: unset !important;">
            <h1>
                <i class="fas fa-hand-wave me-2"></i>
                ¡Bienvenido, {{ auth()->user()->name ?? 'Usuario' }}!
            </h1>
            <p>Has iniciado sesión correctamente en el sistema de Esencia Seguros</p>
            
            <!-- SSN Connection Status -->
            <div class="row justify-content-center mt-3">
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-shield-alt me-2"></i>
                                Estado de Conexión SSN
                            </h5>
                        </div>
                        <div class="card-body">
                            @if($ssnInfo['connected'])
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="{{ $ssnInfo['icon'] }} text-{{ $ssnInfo['color'] }} me-2"></i>
                                            <strong class="text-{{ $ssnInfo['color'] }}">{{ $ssnInfo['status'] }}</strong>
                                        </div>
                                        <p class="text-muted mb-2">{{ $ssnInfo['message'] }}</p>
                                        
                                        @if($ssnInfo['expiration'])
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    Expira: {{ $ssnInfo['expiration'] }}
                                                </small>
                                            </div>
                                        @endif
                                        
                                        @if($ssnInfo['mode'])
                                            <div class="mb-2">
                                                <span class="badge bg-{{ $ssnInfo['mode_color'] }}">
                                                    <i class="fas fa-{{ $ssnInfo['is_mock'] ? 'flask' : 'server' }} me-1"></i>
                                                    {{ $ssnInfo['mode'] }}
                                                </span>
                                            </div>
                                        @endif
                                        
                                        @if($ssnInfo['connection_time'])
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-tachometer-alt me-1"></i>
                                                    Tiempo de conexión: {{ $ssnInfo['connection_time'] }}ms
                                                </small>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="col-md-4 text-end">
                                        @if($ssnInfo['last_4_chars'])
                                            <div class="token-info">
                                                <small class="text-muted d-block">Token:</small>
                                                <code class="bg-light px-2 py-1 rounded">
                                                    ...{{ $ssnInfo['last_4_chars'] }}
                                                </code>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="text-center">
                                    <i class="{{ $ssnInfo['icon'] }} text-{{ $ssnInfo['color'] }} fa-2x mb-3"></i>
                                    <h6 class="text-{{ $ssnInfo['color'] }}">{{ $ssnInfo['status'] }}</h6>
                                    <p class="text-muted">{{ $ssnInfo['message'] }}</p>
                                    
                                    @if($ssnInfo['last_4_chars'])
                                        <div class="mt-2">
                                            <small class="text-muted">Token expirado:</small>
                                            <code class="bg-light px-2 py-1 rounded">
                                                ...{{ $ssnInfo['last_4_chars'] }}
                                            </code>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-section mt-5">
            <h2 class="section-title">
                <i class="fas fa-chart-bar me-2"></i>
                Estadísticas de Presentaciones
            </h2>
            <div class="row" style="justify-content: center;">
                <div class="col-md-2 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3>{{ $stats['total'] ?? 0 }}</h3>
                            <p>Total</p>
                        </div>
                    </div>
                </div>
                <div hidden class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>{{ $stats['vacio'] ?? 0 }}</h3>
                            <p>Vacías</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-upload"></i>
                        </div>
                        <div class="stat-content">
                            <h3>{{ $stats['cargado'] ?? 0 }}</h3>
                            <p>Borrador</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                        <div class="stat-content">
                            <h3>{{ $stats['presentado'] ?? 0 }}</h3>
                            <p>Presentadas</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-content">
                            <h3>{{ $stats['rectificacion_pendiente'] ?? 0 }}</h3>
                            <p>Rectificación Pendiente por SSN</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3>{{ $stats['a_rectificar'] ?? 0 }}</h3>
                            <p>Pendientes de Rectificar</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
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

    .quick-navigation {
        margin-bottom: 3rem;
    }

    .nav-card {
        display: block;
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        text-align: center;
        box-shadow: var(--esencia-shadow-card);
        transition: all 0.3s ease;
        margin-bottom: 1rem;
        height: 100%;
        border: 1px solid var(--esencia-border-light);
        text-decoration: none;
        color: inherit;
        position: relative;
        overflow: hidden;
    }

    .nav-card::before {
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

    .nav-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(55, 70, 97, 0.2);
        text-decoration: none;
        color: inherit;
    }

    .nav-card:hover::before {
        transform: scaleX(1);
    }

    .nav-icon {
        font-size: 2.5rem;
        color: var(--esencia-primary);
        margin-bottom: 1rem;
        transition: transform 0.3s ease;
    }

    .nav-card:hover .nav-icon {
        transform: scale(1.1);
    }

    .nav-content h4 {
        color: var(--esencia-text-primary);
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .nav-content p {
        color: var(--esencia-text-secondary);
        margin: 0;
        font-size: 0.9rem;
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

    .recent-activity {
        margin-bottom: 3rem;
    }

    .activity-list {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: var(--esencia-shadow-card);
        border: 1px solid var(--esencia-border-light);
    }

    .activity-item {
        display: flex;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid var(--esencia-border-light);
        transition: background-color 0.3s ease;
        border-radius: 8px;
        margin-bottom: 0.5rem;
    }

    .activity-item:hover {
        background-color: #f8f9fa;
    }

    .activity-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }

    .activity-icon {
        font-size: 1.5rem;
        color: var(--esencia-primary);
        margin-right: 1rem;
        width: 40px;
        text-align: center;
    }

    .activity-content h5 {
        color: var(--esencia-text-primary);
        margin-bottom: 0.25rem;
        font-weight: 600;
    }

    .activity-content p {
        color: var(--esencia-text-secondary);
        margin-bottom: 0.25rem;
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

        .nav-card {
            padding: 1rem;
        }

        .nav-icon {
            font-size: 2rem;
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

        .stat-card, .nav-card {
            margin-bottom: 0.5rem;
        }
    }
</style>
@endpush 