@extends('layouts.app')

@section('title', 'Listado de Presentaciones Mensuales')

@section('content')
<div class="container-fluid p-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Presentaciones Mensuales
                    </h4>
                    <a href="{{ route('monthly-presentations.create') }}" class="btn btn-light">
                        <i class="fas fa-plus me-2"></i>Nueva Presentación
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                        </div>
                    @endif

                    <!-- Filtros -->
                    <form method="GET" class="row g-3 mb-3 align-items-end">
                        <div class="col-md-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select name="estado" id="estado" class="form-select">
                                <option value="">Todos</option>
                                <option value="CARGADO" @selected(request('estado')=='CARGADO')>CARGADO</option>
                                <option value="PRESENTADO" @selected(request('estado')=='PRESENTADO')>PRESENTADO</option>
                                <option value="RECTIFICACION_PENDIENTE" @selected(request('estado')=='RECTIFICACION_PENDIENTE')>RECTIFICACION PENDIENTE</option>
                                <option value="A_RECTIFICAR" @selected(request('estado')=='A_RECTIFICAR')>A RECTIFICAR</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="cronograma" class="form-label">Mes</label>
                            <input type="text" name="cronograma" id="cronograma" class="form-control" value="{{ request('cronograma') }}" placeholder="Ej: 2025-06">
                        </div>
                        <div class="col-md-3">
                            <label for="usuario" class="form-label">Usuario</label>
                            <input type="text" name="usuario" id="usuario" class="form-control" value="{{ request('usuario') }}" placeholder="Nombre usuario">
                        </div>
                        <div class="col-md-3 text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Filtrar
                            </button>
                            <a href="{{ route('monthly-presentations.index') }}" class="btn btn-outline-secondary ms-2">
                                Limpiar
                            </a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Mes</th>
                                    <th>Estado</th>
                                    <th>Usuario</th>
                                    <th>Creado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($presentations as $presentation)
                                <tr>
                                    <td><span class="badge bg-secondary">#{{ $presentation->id }}</span></td>
                                    <td><span class="badge bg-info">{{ $presentation->cronograma }}</span></td>
                                    <td>
                                        @php
                                            $estado = $presentation->estado;
                                            $color = match($estado) {
                                                'CARGADO' => 'warning',
                                                'PRESENTADO' => 'success',
                                                'RECTIFICACION_PENDIENTE' => 'info',
                                                'A_RECTIFICAR' => 'danger',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $color }}">{{ $estado }}</span>
                                    </td>
                                    <td>{{ $presentation->user->name ?? '-' }}</td>
                                    <td><small>{{ $presentation->created_at->format('d/m/Y H:i') }}</small></td>
                                    <td>
                                        <a href="{{ route('monthly-presentations.show', $presentation->id) }}" class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($presentation->estado === 'CARGADO')
                                            <button type="button" class="btn btn-sm btn-outline-info ms-1" data-json='@json($presentation->getSsnJson())' onclick="showJsonModal(this)" title="Ver JSON SSN">
                                                <i class="fas fa-code"></i> JSON
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No hay presentaciones mensuales registradas.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <!-- Paginación -->
                    <div class="d-flex justify-content-center mt-3">
                        {{ $presentations->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal JSON -->
<div class="modal fade" id="jsonModal" tabindex="-1" aria-labelledby="jsonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="jsonModalLabel">
                    <i class="fas fa-code me-2"></i>
                    JSON para SSN
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <pre id="jsonContent" class="json-content" style="background-color: #f8f9fa; padding: 1rem; border-radius: 0.375rem; max-height: 500px; overflow-y: auto;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<script>
function showJsonModal(btn) {
    const json = btn.getAttribute('data-json');
    let pretty = '';
    try {
        pretty = JSON.stringify(JSON.parse(json), null, 2);
    } catch (e) {
        pretty = json;
    }
    document.getElementById('jsonContent').textContent = pretty;
    const modal = new bootstrap.Modal(document.getElementById('jsonModal'));
    modal.show();
}
</script>
@endsection 