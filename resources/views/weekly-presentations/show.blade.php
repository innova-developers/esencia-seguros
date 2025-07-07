@extends('layouts.app')

@section('title', 'Detalle Presentación Semanal - Esencia Seguros')

@section('content')
<div class="row p-4">
    <div class="col-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-calendar-week me-2"></i>
                        Presentación Semanal {{ $presentation->cronograma }}
                    </h4>
                </div>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6><strong>Información General</strong></h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Semana:</strong></td>
                                <td>{{ $presentation->cronograma }}</td>
                            </tr>
                            <tr>
                                <td><strong>Estado:</strong></td>
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
                            </tr>
                            <tr>
                                <td><strong>Usuario:</strong></td>
                                <td>{{ $presentation->user->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Creado:</strong></td>
                                <td>{{ $presentation->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @if($presentation->presented_at)
                            <tr>
                                <td><strong>Presentado:</strong></td>
                                <td>{{ $presentation->presented_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6><strong>Resumen</strong></h6>
                        <div class="alert alert-info">
                            <div>Total operaciones: <strong>{{ $presentation->weeklyOperations->count() }}</strong></div>
                            <div>Compras: <strong>{{ $presentation->weeklyOperations->where('tipo_operacion', 'C')->count() }}</strong></div>
                            <div>Ventas: <strong>{{ $presentation->weeklyOperations->where('tipo_operacion', 'V')->count() }}</strong></div>
                            <div>Canjes: <strong>{{ $presentation->weeklyOperations->where('tipo_operacion', 'J')->count() }}</strong></div>
                        </div>
                    </div>
                </div>
                <h5 class="mt-4 mb-3"><i class="fas fa-table me-2"></i>Operaciones</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="tabla-operaciones">
                        <thead class="table-dark">
                            <tr>
                                <th>Tipo Oper.</th>
                                <th>Tipo Especie</th>
                                <th>Código SSN</th>
                                <th>Cant. Especies</th>
                                <th>Código Afect.</th>
                                <th>Tipo Valuac.</th>
                                <th>Fecha Movim.</th>
                                <th>Precio Compra</th>
                                <th>Fecha Liquidac.</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($presentation->weeklyOperations as $op)
                            @php
                                $badge = match($op->tipo_operacion) {
                                    'C' => 'success',
                                    'V' => 'danger',
                                    'J' => 'warning',
                                    default => 'secondary',
                                };
                                $label = match($op->tipo_operacion) {
                                    'C' => 'COMPRA',
                                    'V' => 'VENTA',
                                    'J' => 'CANJE',
                                    default => $op->tipo_operacion,
                                };
                            @endphp
                            <tr>
                                <td><span class="badge bg-{{ $badge }}">{{ $label }}</span></td>
                                <td>{{ $op->tipo_especie ?? '' }}</td>
                                <td><code>{{ $op->codigo_especie ?? '' }}</code></td>
                                <td>{{ $op->cant_especies ? number_format($op->cant_especies, 0, ',', '.') : '' }}</td>
                                <td>{{ $op->codigo_afectacion ?? '' }}</td>
                                <td>{{ $op->tipo_valuacion ?? '' }}</td>
                                <td>{{ $op->fecha_movimiento ? \Carbon\Carbon::parse($op->fecha_movimiento)->format('d/m/Y') : '' }}</td>
                                <td>{{ $op->precio_compra ? number_format($op->precio_compra, 4, ',', '.') : '' }}</td>
                                <td>{{ $op->fecha_liquidacion ? \Carbon\Carbon::parse($op->fecha_liquidacion)->format('d/m/Y') : '' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- Acciones según estado -->
                <div class="mt-4 text-center">
                    @if($presentation->estado === 'CARGADO')
                        <a href="{{ route('weekly-presentations.index') }}" class="btn btn-secondary btn-lg me-3">
                            <i class="fas fa-arrow-left me-2"></i>Volver al listado
                        </a>
                        <button class="btn btn-primary btn-lg" id="confirmarPresentacionBtn">
                            <i class="fas fa-check me-2"></i>Confirmar Presentación
                        </button>
                        <button type="button" class="btn btn-outline-info btn-lg ms-3" data-json='@json($presentation->getSsnJson())' onclick="showJsonModal(this)">
                            <i class="fas fa-code me-2"></i>Ver JSON
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-lg ms-3" onclick="showSsnParamsModal()">
                            <i class="fas fa-cogs me-2"></i>Ver Parámetros SSN
                        </button>
                        @if($presentation->original_file_path)
                            <a href="{{ route('weekly-presentations.download-excel', $presentation->id) }}" class="btn btn-outline-primary btn-lg ms-3">
                                <i class="fas fa-file-excel me-2"></i>Descargar Excel
                            </a>
                        @endif
                    @elseif($presentation->estado === 'PRESENTADO')
                        <a href="{{ route('weekly-presentations.index') }}" class="btn btn-secondary btn-lg me-3">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                        <button class="btn btn-warning btn-lg" id="solicitarRectificacionBtn">
                            <i class="fas fa-exclamation me-2"></i>Solicitar Rectificación
                        </button>
                        <button type="button" class="btn btn-outline-info btn-lg ms-3" data-json='@json($presentation->getSsnJson())' onclick="showJsonModal(this)">
                            <i class="fas fa-code me-2"></i>Ver JSON
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-lg ms-3" onclick="showSsnParamsModal()">
                            <i class="fas fa-cogs me-2"></i>Ver Parámetros SSN
                        </button>
                        @if($presentation->original_file_path)
                            <a href="{{ route('weekly-presentations.download-excel', $presentation->id) }}" class="btn btn-outline-primary btn-lg ms-3">
                                <i class="fas fa-file-excel me-2"></i>Descargar Excel
                            </a>
                        @endif
                    @elseif($presentation->estado === 'RECTIFICACION_PENDIENTE')
                        <a href="{{ route('weekly-presentations.index') }}" class="btn btn-secondary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                        <button type="button" class="btn btn-outline-info btn-lg ms-3" data-json='@json($presentation->getSsnJson())' onclick="showJsonModal(this)">
                            <i class="fas fa-code me-2"></i>Ver JSON
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-lg ms-3" onclick="showSsnParamsModal()">
                            <i class="fas fa-cogs me-2"></i>Ver Parámetros SSN
                        </button>
                        @if($presentation->original_file_path)
                            <a href="{{ route('weekly-presentations.download-excel', $presentation->id) }}" class="btn btn-outline-primary btn-lg ms-3">
                                <i class="fas fa-file-excel me-2"></i>Descargar Excel
                            </a>
                        @endif
                    @elseif($presentation->estado === 'A_RECTIFICAR')
                        <a href="{{ route('weekly-presentations.index') }}" class="btn btn-secondary btn-lg me-3">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                        <a href="{{ route('weekly-presentations.create', ['week' => $presentation->cronograma]) }}" class="btn btn-danger btn-lg">
                            <i class="fas fa-edit me-2"></i>Rectificar
                        </a>
                        <button type="button" class="btn btn-outline-info btn-lg ms-3" data-json='@json($presentation->getSsnJson())' onclick="showJsonModal(this)">
                            <i class="fas fa-code me-2"></i>Ver JSON
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-lg ms-3" onclick="showSsnParamsModal()">
                            <i class="fas fa-cogs me-2"></i>Ver Parámetros SSN
                        </button>
                        @if($presentation->original_file_path)
                            <a href="{{ route('weekly-presentations.download-excel', $presentation->id) }}" class="btn btn-outline-primary btn-lg ms-3">
                                <i class="fas fa-file-excel me-2"></i>Descargar Excel
                            </a>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modales y scripts -->
<!-- Modal Confirmar Presentación -->
<div class="modal fade" id="modalConfirmar" tabindex="-1" aria-labelledby="modalConfirmarLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalConfirmarLabel"><i class="fas fa-check me-2"></i>Confirmar Presentación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>¿Está seguro que desea confirmar y enviar la presentación a la SSN? Se enviarán los siguientes datos:</p>
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead class="table-dark">
              <tr>
                <th>Tipo Oper.</th>
                <th>Tipo Especie</th>
                <th>Código SSN</th>
                <th>Cant. Especies</th>
                <th>Código Afect.</th>
                <th>Tipo Valuac.</th>
                <th>Fecha Movim.</th>
                <th>Precio Compra</th>
                <th>Fecha Liquidac.</th>
              </tr>
            </thead>
            <tbody>
              @foreach($presentation->weeklyOperations as $op)
              @php
                  $badge = match($op->tipo_operacion) {
                      'C' => 'success',
                      'V' => 'danger',
                      'J' => 'warning',
                      default => 'secondary',
                  };
                  $label = match($op->tipo_operacion) {
                      'C' => 'COMPRA',
                      'V' => 'VENTA',
                      'J' => 'CANJE',
                      default => $op->tipo_operacion,
                  };
              @endphp
              <tr>
                <td><span class="badge bg-{{ $badge }}">{{ $label }}</span></td>
                <td>{{ $op->tipo_especie ?? '' }}</td>
                <td><code>{{ $op->codigo_especie ?? '' }}</code></td>
                <td>{{ $op->cant_especies ? number_format($op->cant_especies, 0, ',', '.') : '' }}</td>
                <td>{{ $op->codigo_afectacion ?? '' }}</td>
                <td>{{ $op->tipo_valuacion ?? '' }}</td>
                <td>{{ $op->fecha_movimiento ? \Carbon\Carbon::parse($op->fecha_movimiento)->format('d/m/Y') : '' }}</td>
                <td>{{ $op->precio_compra ? number_format($op->precio_compra, 4, ',', '.') : '' }}</td>
                <td>{{ $op->fecha_liquidacion ? \Carbon\Carbon::parse($op->fecha_liquidacion)->format('d/m/Y') : '' }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <form method="POST" action="{{ route('weekly-presentations.confirm', $presentation->id) }}" id="formConfirmarPresentacion">
          @csrf
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Confirmar y Enviar</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Solicitar Rectificación -->
<div class="modal fade" id="modalRectificar" tabindex="-1" aria-labelledby="modalRectificarLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalRectificarLabel"><i class="fas fa-exclamation me-2"></i>Solicitar Rectificación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>¿Está seguro que desea solicitar la rectificación de esta presentación? Se notificará a la SSN.</p>
      </div>
      <div class="modal-footer">
        <form method="POST" action="{{ route('weekly-presentations.rectify', $presentation->id) }}" id="formRectificarPresentacion">
          @csrf
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning">Solicitar Rectificación</button>
        </form>
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

<!-- Modal Parámetros SSN -->
<div class="modal fade" id="ssnParamsModal" tabindex="-1" aria-labelledby="ssnParamsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ssnParamsModalLabel">
                    <i class="fas fa-cogs me-2"></i>
                    Parámetros de Presentación SSN
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 30px;">
                @if($presentation->estado === 'CARGADO')
                    <div class="text-center py-5">
                        <i class="fas fa-info-circle fa-3x text-warning mb-3"></i>
                        <h5 class="text-warning mb-3">Presentación no enviada a SSN</h5>
                        <p class="text-muted">
                            Esta presentación aún no ha sido enviada a la SSN. 
                            Los parámetros de SSN estarán disponibles una vez que se confirme y envíe la presentación.
                        </p>
                    </div>
                @else
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3"><i class="fas fa-info-circle me-2"></i>Información General</h6>
                            <div class="mb-3">
                                <strong>ID SSN:</strong>
                                <span class="badge bg-primary ms-2">{{ $presentation->ssn_response_id ?? 'N/A' }}</span>
                            </div>
                            <div class="mb-3">
                                <strong>Estado:</strong>
                                <span class="badge bg-success ms-2">{{ $presentation->estado }}</span>
                            </div>
                            <div class="mb-3">
                                <strong>Cronograma:</strong>
                                <span class="ms-2">{{ $presentation->cronograma }}</span>
                            </div>
                            <div class="mb-3">
                                <strong>Tipo de Entrega:</strong>
                                <span class="ms-2">{{ $presentation->tipo_entrega }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3"><i class="fas fa-calendar me-2"></i>Fechas</h6>
                            <div class="mb-3">
                                <strong>Fecha Presentación:</strong>
                                <div class="ms-2 text-muted">
                                    {{ $presentation->presented_at ? $presentation->presented_at->format('d/m/Y H:i:s') : 'N/A' }}
                                </div>
                            </div>
                            <div class="mb-3">
                                <strong>Fecha Confirmación:</strong>
                                <div class="ms-2 text-muted">
                                    {{ $presentation->confirmed_at ? $presentation->confirmed_at->format('d/m/Y H:i:s') : 'N/A' }}
                                </div>
                            </div>
                            @if($presentation->rectification_requested_at)
                            <div class="mb-3">
                                <strong>Fecha Solicitud Rectificación:</strong>
                                <div class="ms-2 text-muted">
                                    {{ $presentation->rectification_requested_at->format('d/m/Y H:i:s') }}
                                </div>
                            </div>
                            @endif
                            @if($presentation->rectification_approved_at)
                            <div class="mb-3">
                                <strong>Fecha Aprobación Rectificación:</strong>
                                <div class="ms-2 text-muted">
                                    {{ $presentation->rectification_approved_at->format('d/m/Y H:i:s') }}
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    @if($presentation->ssn_response_data)
                    <hr>
                    <h6 class="text-primary mb-3"><i class="fas fa-database me-2"></i>Respuesta SSN</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <tbody>
                                @if(isset($presentation->ssn_response_data['id']))
                                <tr>
                                    <th style="width: 30%;">ID Respuesta</th>
                                    <td><code>{{ $presentation->ssn_response_data['id'] }}</code></td>
                                </tr>
                                @endif
                                @if(isset($presentation->ssn_response_data['mensaje']))
                                <tr>
                                    <th>Mensaje</th>
                                    <td>{{ $presentation->ssn_response_data['mensaje'] }}</td>
                                </tr>
                                @endif
                                @if(isset($presentation->ssn_response_data['estado']))
                                <tr>
                                    <th>Estado SSN</th>
                                    <td><span class="badge bg-info">{{ $presentation->ssn_response_data['estado'] }}</span></td>
                                </tr>
                                @endif
                                @if(isset($presentation->ssn_response_data['fechaEnvio']))
                                <tr>
                                    <th>Fecha Envío</th>
                                    <td>{{ $presentation->ssn_response_data['fechaEnvio'] }}</td>
                                </tr>
                                @endif
                                @if(isset($presentation->ssn_response_data['fechaRecepcion']))
                                <tr>
                                    <th>Fecha Recepción</th>
                                    <td>{{ $presentation->ssn_response_data['fechaRecepcion'] }}</td>
                                </tr>
                                @endif
                                @if(isset($presentation->ssn_response_data['totalOperaciones']))
                                <tr>
                                    <th>Total Operaciones</th>
                                    <td><span class="badge bg-secondary">{{ $presentation->ssn_response_data['totalOperaciones'] }}</span></td>
                                </tr>
                                @endif
                                @if(isset($presentation->ssn_response_data['numeroPresentacion']))
                                <tr>
                                    <th>Número Presentación</th>
                                    <td><code>{{ $presentation->ssn_response_data['numeroPresentacion'] }}</code></td>
                                </tr>
                                @endif
                                @if(isset($presentation->ssn_response_data['observaciones']))
                                <tr>
                                    <th>Observaciones</th>
                                    <td>{{ $presentation->ssn_response_data['observaciones'] }}</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    
                    @if(isset($presentation->ssn_response_data['validaciones']))
                    <h6 class="text-primary mb-3 mt-4"><i class="fas fa-check-circle me-2"></i>Validaciones SSN</h6>
                    <div class="row">
                        @foreach($presentation->ssn_response_data['validaciones'] as $tipo => $estado)
                        <div class="col-md-3 mb-2">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-{{ $estado === 'OK' ? 'success' : 'danger' }} me-2">
                                    {{ $estado }}
                                </span>
                                <small class="text-muted">{{ ucfirst($tipo) }}</small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                    @endif
                    
                    @if($presentation->original_filename || $presentation->json_file_path)
                    <hr>
                    <h6 class="text-primary mb-3"><i class="fas fa-file me-2"></i>Archivos</h6>
                    <div class="row">
                        @if($presentation->original_filename)
                        <div class="col-md-6 mb-3">
                            <strong>Archivo Excel Original:</strong>
                            <div class="ms-2 text-muted">{{ $presentation->original_filename }}</div>
                        </div>
                        @endif
                        @if($presentation->json_file_path)
                        <div class="col-md-6 mb-3">
                            <strong>Archivo JSON Generado:</strong>
                            <div class="ms-2 text-muted">{{ basename($presentation->json_file_path) }}</div>
                        </div>
                        @endif
                    </div>
                    @endif
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const confirmarBtn = document.getElementById('confirmarPresentacionBtn');
    if (confirmarBtn) {
        confirmarBtn.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('modalConfirmar'));
            modal.show();
        });
    }
    const rectificarBtn = document.getElementById('solicitarRectificacionBtn');
    if (rectificarBtn) {
        rectificarBtn.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('modalRectificar'));
            modal.show();
        });
    }
});

function showJsonModal(button) {
    const jsonData = JSON.parse(button.getAttribute('data-json'));
    document.getElementById('jsonContent').textContent = JSON.stringify(jsonData, null, 2);
    new bootstrap.Modal(document.getElementById('jsonModal')).show();
}

function showSsnParamsModal() {
    new bootstrap.Modal(document.getElementById('ssnParamsModal')).show();
}
</script>


@endsection 