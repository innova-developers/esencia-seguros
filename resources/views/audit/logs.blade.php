@php
                            $moduleLabels = [
                                'auth' => 'Autenticación',
                                'ssn' => 'Conexión SSN',
                                'weekly_presentations' => 'Presentaciones Semanales',
                                'monthly_presentations' => 'Presentaciones Mensuales',
                                'cron' => 'Proceso Automático',
                                'Cron' => 'Proceso Automático',
                                'users' => 'Usuarios',
                                'dashboard' => 'Dashboard',
                                'files' => 'Archivos',
                                '' => 'General',
                                null => 'General',
                            ];
                            $actionLabels = [
                                'LOGIN' => 'Inicio de sesión',
                                'LOGOUT' => 'Cierre de sesión',
                                'VIEW_LIST' => 'Visualización de listado',
                                'CREATE' => 'Creación',
                                'UPDATE' => 'Actualización',
                                'DELETE' => 'Eliminación',
                                'EXCEL_PROCESSED' => 'Excel procesado',
                                'EXCEL_PROCESS_ERROR' => 'Error al procesar Excel',
                                'FILE_UPLOAD_ATTEMPT' => 'Intento de subir archivo',
                                'FILE_UPLOAD_SUCCESS' => 'Archivo subido',
                                'FILE_UPLOAD_ERROR' => 'Error al subir archivo',
                                'NEW_PRESENTATION_CREATED' => 'Nueva presentación creada',
                                'CONFIRMATION_STARTED' => 'Inicio de confirmación',
                                'CONFIRMATION_REJECTED' => 'Confirmación rechazada',
                                'RECTIFICATION_STARTED' => 'Inicio de rectificación',
                                'RECTIFICATION_REJECTED' => 'Rectificación rechazada',
                                'RECTIFICATION_SUCCESS' => 'Rectificación exitosa',
                                'RECTIFICATION_APPROVED' => 'Rectificación aprobada por SSN',
                                'RECTIFICATION_REJECTED' => 'Rectificación rechazada por SSN',
                                'VALIDATION_ERROR' => 'Error de validación',
                                'SYNC_STARTED' => 'Inicio de sincronización automática',
                                'SYNC_FINISHED' => 'Fin de sincronización automática',
                            ];
@endphp
                        
@extends('layouts.app')

@section('title', 'Registros de Auditoría - Esencia Seguros')

@push('styles')
<style>
    .page-header {
        background: linear-gradient(135deg, var(--esencia-primary) 0%, var(--esencia-primary-light) 100%);
        color: white;
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        text-align: center;
    }

    .filters-card {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: var(--esencia-shadow-card);
        margin-bottom: 2rem;
        border: 1px solid var(--esencia-border-light);
    }

    .btn-filter {
        background: linear-gradient(135deg, var(--esencia-primary) 0%, var(--esencia-primary-light) 100%);
        border: none;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        color: white;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-filter:hover {
        background: linear-gradient(135deg, var(--esencia-primary-dark) 0%, var(--esencia-primary) 100%);
        color: white;
        transform: translateY(-1px);
    }

    .btn-clear {
        background: linear-gradient(135deg, #6c757d, #495057);
        border: none;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        color: white;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-clear:hover {
        background: linear-gradient(135deg, #495057, #343a40);
        color: white;
        transform: translateY(-1px);
    }

    .table-container {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: var(--esencia-shadow-card);
        border: 1px solid var(--esencia-border-light);
        margin-bottom: 0.5rem;
    }

    .table {
        margin-bottom: 0;
    }

    .table th {
        background: linear-gradient(135deg, var(--esencia-primary) 0%, var(--esencia-primary-light) 100%);
        color: white;
        border: none;
        font-weight: 600;
        padding: 1rem;
    }

    .table td {
        padding: 1rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--esencia-border-light);
    }

    .table tbody tr:hover {
        background-color: rgba(55, 70, 97, 0.05);
    }

    .badge-module {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .badge-action {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        color: white;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .badge-action.create { background: linear-gradient(135deg, #28a745, #1e7e34); }
    .badge-action.update { background: linear-gradient(135deg, #ffc107, #ff9800); color: #374661; }
    .badge-action.delete { background: linear-gradient(135deg, #dc3545, #c82333); }
    .badge-action.login { background: linear-gradient(135deg, #17a2b8, #138496); }
    .badge-action.logout { background: linear-gradient(135deg, #6c757d, #495057); }

    .pagination {
        justify-content: center;
        margin-top: 2rem;
        margin-bottom: 2rem;
        gap: 0.25rem;
    }

    .pagination .page-item .page-link {
        color: var(--esencia-primary) !important;
        border: 1px solid var(--esencia-border-light) !important;
        padding: 0.5rem 0.9rem !important;
        border-radius: 8px !important;
        transition: all 0.3s ease;
        text-decoration: none;
        background-color: white !important;
        font-size: 1rem;
        font-weight: 500;
        min-width: 40px;
        text-align: center;
        box-shadow: none !important;
    }

    .pagination .page-item .page-link:hover {
        background: var(--esencia-primary) !important;
        color: white !important;
        border-color: var(--esencia-primary) !important;
        text-decoration: none;
        transform: translateY(-1px);
    }

    .pagination .page-item.active .page-link {
        background: var(--esencia-primary) !important;
        border-color: var(--esencia-primary) !important;
        color: white !important;
        font-weight: 600;
    }

    .pagination .page-item.disabled .page-link {
        color: #6c757d !important;
        background-color: #f8f9fa !important;
        border-color: var(--esencia-border-light) !important;
        cursor: not-allowed;
        opacity: 0.6;
    }

    .pagination .page-item.disabled .page-link:hover {
        background-color: #f8f9fa !important;
        color: #6c757d !important;
        border-color: var(--esencia-border-light) !important;
        transform: none;
    }

    .pagination .page-item .page-link i {
        font-size: 0.9rem;
    }

    .pagination .page-item .page-link:focus {
        box-shadow: 0 0 0 0.2rem rgba(55, 70, 97, 0.25) !important;
        outline: none;
    }

    .results-summary {
        background: white;
        border-radius: 10px;
        padding: 1rem 1.5rem;
        box-shadow: var(--esencia-shadow-card);
        margin-bottom: 1.5rem;
        border: 1px solid var(--esencia-border-light);
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
    }

    .empty-state i {
        font-size: 4rem;
        color: var(--esencia-text-secondary);
        margin-bottom: 1rem;
    }

    @media (max-width: 768px) {
        .page-header {
            padding: 1.5rem 1rem;
        }
        
        .filters-card {
            padding: 1rem;
        }
        
        .table-container {
            padding: 1rem;
            overflow-x: auto;
        }
        
        .results-summary {
            padding: 1rem;
        }
    }
</style>
@endpush

@section('content')
<div class="container">
    <!-- Page Header -->
    <div class="page-header mt-4">
        <h1>
            <i class="fas fa-history me-2"></i>
            Registros de Auditoría
        </h1>
        <p class="mb-0">Sistema de trazabilidad y auditoría de actividades</p>
    </div>

    <!-- Filters -->
    <div class="filters-card">
        <h5 class="mb-3">
            <i class="fas fa-filter me-2"></i>
            Filtros de Búsqueda
        </h5>
        <form method="GET" action="{{ route('audit.logs') }}" id="filtersForm">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="module" class="form-label">Módulo</label>
                    <select class="form-select" id="module" name="module">
                        <option value="">Todos los módulos</option>
                        @php
                            // Unir los módulos posibles del mapeo y los de la base
                            $allModules = collect(array_keys($moduleLabels))
                                ->merge($modules)
                                ->unique()
                                ->filter(fn($m) => $m !== null && $m !== '')
                                ->values();
                        @endphp
                        @foreach($allModules as $module)
                            <option value="{{ $module }}" {{ request('module') == $module ? 'selected' : '' }}>
                                {{ $moduleLabels[$module ?? ''] ?? ucfirst($module) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="user_id" class="form-label">Usuario</label>
                    <select class="form-select" id="user_id" name="user_id">
                        <option value="">Todos los usuarios</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="action" class="form-label">Acción</label>
                    <select class="form-select" id="action" name="action">
                        <option value="">Todas las acciones</option>
                        @php
                            // Unir las acciones posibles del mapeo y las de la base
                            $allActions = collect(array_keys($actionLabels))
                                ->merge($actions)
                                ->unique()
                                ->filter(fn($a) => $a !== null && $a !== '')
                                ->values();
                        @endphp
                        @foreach($allActions as $action)
                            <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                {{ $actionLabels[$action] ?? ucfirst(strtolower(str_replace('_', ' ', $action))) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Fecha Desde</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="{{ request('date_from', now()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Fecha Hasta</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="{{ request('date_to' , now()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-6">
                    <label for="description" class="form-label">Descripción</label>
                    <input type="text" class="form-control" id="description" name="description" 
                           placeholder="Buscar en descripción..." value="{{ request('description') }}">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-filter">
                            <i class="fas fa-search me-1"></i>
                            Filtrar
                        </button>
                        <a href="{{ route('audit.logs') }}" class="btn btn-clear">
                            <i class="fas fa-times me-1"></i>
                            Limpiar
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Results Summary -->
    <div class="results-summary">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-primary fs-6">
                    <i class="fas fa-list me-1"></i>
                    {{ $logs->total() }} registros encontrados
                </span>
            </div>
            <div>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-1"></i>
                    Volver al Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-container">
        @if($logs->count() > 0)
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Usuario</th>
                            <th>Módulo</th>
                            <th>Acción</th>
                            <th>Descripción</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                       
                        @foreach($logs as $log)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $log->created_at->format('d/m/Y') }}</div>
                                    <small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $log->user->name ?? 'Sistema' }}</div>
                                    <small class="text-muted">{{ $log->user->email ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    <span class="badge-module">
                                        {{ $moduleLabels[$log->module ?? ''] ?? ucfirst($log->module) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-action {{ strtolower($log->action) }}">
                                        {{ $actionLabels[$log->action] ?? ucfirst(strtolower(str_replace('_', ' ', $log->action))) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="text-wrap" style="max-width: 300px;">
                                        {{ $log->description }}
                                    </div>
                                </td>
                                <td>
                                    <code class="text-muted">{{ $log->ip_address ?? 'N/A' }}</code>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4 mb-2">
                {{ $logs->appends(request()->query())->links('vendor.pagination.bootstrap-5') }}
            </div>
        @else
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h4 class="text-muted">No se encontraron registros</h4>
                <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Auto-submit form when filters change
    document.querySelectorAll('#filtersForm select, #filtersForm input[type="date"]').forEach(element => {
        element.addEventListener('change', function() {
            document.getElementById('filtersForm').submit();
        });
    });

    // Debounced search for description
    let searchTimeout;
    document.getElementById('description').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            document.getElementById('filtersForm').submit();
        }, 500);
    });
</script>
@endpush 