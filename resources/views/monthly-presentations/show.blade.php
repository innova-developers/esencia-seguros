@extends('layouts.app')

@section('title', 'Detalle Presentación Mensual - Esencia Seguros')

@section('content')
<div class="row p-4">
    <div class="col-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Presentación Mensual {{ $presentation->cronograma }}
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
                                <td><strong>Mes:</strong></td>
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
                            <div>Total stocks: <strong>{{ $presentation->monthlyStocks->count() }}</strong></div>
                            <div>Inversiones: <strong>{{ $presentation->monthlyStocks->where('tipo', 'I')->count() }}</strong></div>
                            <div>Plazos Fijos: <strong>{{ $presentation->monthlyStocks->where('tipo', 'P')->count() }}</strong></div>
                            <div>Cheques Pago Diferido: <strong>{{ $presentation->monthlyStocks->where('tipo', 'C')->count() }}</strong></div>
                        </div>
                    </div>
                </div>
                <h5 class="mt-4 mb-3"><i class="fas fa-table me-2"></i>Stocks Mensuales</h5>
                <div class="table-responsive" style="overflow-x: auto;">
                    <div class="table-container">
                        <table class="table table-striped table-hover" id="tabla-stocks">
                        <thead class="table-dark">
                            <tr>
                                <th>Tipo</th>
                                <th>Nombre</th>
                                <th>Tipo Especie</th>
                                <th>Código Especie</th>
                                <th>Cant. Devengado</th>
                                <th>Cant. Percibido</th>
                                <th>Código Afect.</th>
                                <th>Tipo Valuac.</th>
                                <th>Con Cotización</th>
                                <th>Libre Dispon.</th>
                                <th>Emisor Grupo Econ.</th>
                                <th>Emisor Art. Ret.</th>
                                <th>Previsión Desval.</th>
                                <th>Valor Contable</th>
                                <th>Fecha Pase VT</th>
                                <th>Precio Pase VT</th>
                                <th>En Custodia</th>
                                <th>Financiera</th>
                                <th>Valor Financiero</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($presentation->monthlyStocks as $stock)
                            @php
                                $badge = match($stock->tipo) {
                                    'I' => 'primary',
                                    'P' => 'success',
                                    'C' => 'warning',
                                    default => 'secondary',
                                };
                                $label = match($stock->tipo) {
                                    'I' => 'INVERSIONES',
                                    'P' => 'PLAZO FIJO',
                                    'C' => 'CHEQUE PAGO DIF.',
                                    default => $stock->tipo,
                                };
                            @endphp
                            <tr>
                                <td><span class="badge bg-{{ $badge }}">{{ $label }}</span></td>
                                <td>{{ $stock->nombre ?? '' }}</td>
                                <td>{{ $stock->tipo_especie ?? '' }}</td>
                                <td><code>{{ $stock->codigo_especie ?? '' }}</code></td>
                                <td>{{ $stock->cantidad_devengado_especies ? number_format($stock->cantidad_devengado_especies, 6, ',', '') : '' }}</td>
                                <td>{{ $stock->cantidad_percibido_especies ? number_format($stock->cantidad_percibido_especies, 6, ',', '') : '' }}</td>
                                <td>{{ $stock->codigo_afectacion ?? '' }}</td>
                                <td>{{ $stock->tipo_valuacion ?? '' }}</td>
                                <td>
                                    @if($stock->con_cotizacion !== null)
                                        <span class="badge bg-{{ $stock->con_cotizacion ? 'success' : 'danger' }}">
                                            {{ $stock->con_cotizacion ? 'Sí' : 'No' }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($stock->libre_disponibilidad !== null)
                                        <span class="badge bg-{{ $stock->libre_disponibilidad ? 'success' : 'danger' }}">
                                            {{ $stock->libre_disponibilidad ? 'Sí' : 'No' }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($stock->emisor_grupo_economico !== null)
                                        <span class="badge bg-{{ $stock->emisor_grupo_economico ? 'success' : 'danger' }}">
                                            {{ $stock->emisor_grupo_economico ? 'Sí' : 'No' }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($stock->emisor_art_ret !== null)
                                        <span class="badge bg-{{ $stock->emisor_art_ret ? 'success' : 'danger' }}">
                                            {{ $stock->emisor_art_ret ? 'Sí' : 'No' }}
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $stock->prevision_desvalorizacion ? number_format($stock->prevision_desvalorizacion, 0, ',', '') : '' }}</td>
                                <td>{{ $stock->valor_contable ? number_format($stock->valor_contable, 0, ',', '') : '' }}</td>
                                <td>{{ $stock->fecha_pase_vt ? \Carbon\Carbon::parse($stock->fecha_pase_vt)->format('d/m/Y') : '' }}</td>
                                <td>{{ $stock->precio_pase_vt ? number_format($stock->precio_pase_vt, 2, ',', '') : '' }}</td>
                                <td>
                                    @if($stock->en_custodia !== null)
                                        <span class="badge bg-{{ $stock->en_custodia ? 'success' : 'danger' }}">
                                            {{ $stock->en_custodia ? 'Sí' : 'No' }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($stock->financiera !== null)
                                        <span class="badge bg-{{ $stock->financiera ? 'success' : 'danger' }}">
                                            {{ $stock->financiera ? 'Sí' : 'No' }}
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $stock->valor_financiero ? number_format($stock->valor_financiero, 0, ',', '') : '' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="scroll-indicator" id="scrollIndicator">
                        <i class="fas fa-arrows-alt-h me-1"></i>
                        Desliza horizontalmente para ver más columnas
                    </div>
                </div>
                <!-- Acciones según estado -->
                <div class="mt-4 text-center">
                    @if($presentation->estado === 'CARGADO')
                        <a href="{{ route('monthly-presentations.index') }}" class="btn btn-secondary btn-lg me-3">
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
                            <a href="{{ route('monthly-presentations.download-excel', $presentation->id) }}" class="btn btn-outline-primary btn-lg ms-3">
                                <i class="fas fa-file-excel me-2"></i>Descargar Excel
                            </a>
                        @endif
                    @elseif($presentation->estado === 'PRESENTADO')
                        <a href="{{ route('monthly-presentations.index') }}" class="btn btn-secondary btn-lg me-3">
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
                            <a href="{{ route('monthly-presentations.download-excel', $presentation->id) }}" class="btn btn-outline-primary btn-lg ms-3">
                                <i class="fas fa-file-excel me-2"></i>Descargar Excel
                            </a>
                        @endif
                    @elseif($presentation->estado === 'RECTIFICACION_PENDIENTE')
                        <a href="{{ route('monthly-presentations.index') }}" class="btn btn-secondary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                        <button type="button" class="btn btn-outline-info btn-lg ms-3" data-json='@json($presentation->getSsnJson())' onclick="showJsonModal(this)">
                            <i class="fas fa-code me-2"></i>Ver JSON
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-lg ms-3" onclick="showSsnParamsModal()">
                            <i class="fas fa-cogs me-2"></i>Ver Parámetros SSN
                        </button>
                        @if($presentation->original_file_path)
                            <a href="{{ route('monthly-presentations.download-excel', $presentation->id) }}" class="btn btn-outline-primary btn-lg ms-3">
                                <i class="fas fa-file-excel me-2"></i>Descargar Excel
                            </a>
                        @endif
                    @elseif($presentation->estado === 'A_RECTIFICAR')
                        <a href="{{ route('monthly-presentations.index') }}" class="btn btn-secondary btn-lg me-3">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                        <a href="{{ route('monthly-presentations.create', ['month' => $presentation->cronograma]) }}" class="btn btn-danger btn-lg">
                            <i class="fas fa-edit me-2"></i>Rectificar
                        </a>
                        <button type="button" class="btn btn-outline-info btn-lg ms-3" data-json='@json($presentation->getSsnJson())' onclick="showJsonModal(this)">
                            <i class="fas fa-code me-2"></i>Ver JSON
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-lg ms-3" onclick="showSsnParamsModal()">
                            <i class="fas fa-cogs me-2"></i>Ver Parámetros SSN
                        </button>
                        @if($presentation->original_file_path)
                            <a href="{{ route('monthly-presentations.download-excel', $presentation->id) }}" class="btn btn-outline-primary btn-lg ms-3">
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
                <th>Tipo</th>
                <th>Nombre</th>
                <th>Tipo Especie</th>
                <th>Código Especie</th>
                <th>Valor Contable</th>
                <th>Detalles</th>
              </tr>
            </thead>
            <tbody>
              @foreach($presentation->monthlyStocks as $stock)
              @php
                  $badge = match($stock->tipo) {
                      'I' => 'primary',
                      'P' => 'success',
                      'C' => 'warning',
                      default => 'secondary',
                  };
                  $label = match($stock->tipo) {
                      'I' => 'INVERSIONES',
                      'P' => 'PLAZO FIJO',
                      'C' => 'CHEQUE PAGO DIF.',
                      default => $stock->tipo,
                  };
              @endphp
              <tr>
                <td><span class="badge bg-{{ $badge }}">{{ $label }}</span></td>
                <td>{{ $stock->nombre ?? '' }}</td>
                <td>{{ $stock->tipo_especie ?? '' }}</td>
                <td><code>{{ $stock->codigo_especie ?? '' }}</code></td>
                <td>{{ $stock->valor_contable ? number_format($stock->valor_contable, 0, ',', '') : '' }}</td>
                <td>
                    @if($stock->isInversiones())
                        {{ $stock->codigo_afectacion ?? '' }} - {{ $stock->tipo_valuacion ?? '' }}
                    @elseif($stock->isPlazoFijo())
                        {{ $stock->cdf ?? '' }} - {{ $stock->moneda ?? '' }}
                    @elseif($stock->isChequePagoDiferido())
                        {{ $stock->codigo_cheque ?? '' }}
                    @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <form method="POST" action="{{ route('monthly-presentations.confirm', $presentation->id) }}" id="formConfirmarPresentacion">
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
        <form method="POST" action="{{ route('monthly-presentations.rectify', $presentation->id) }}" id="formRectificarPresentacion">
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
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <pre id="jsonContent" style="max-height: 500px; overflow-y: auto;"></pre>
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
// Función para mostrar el modal de confirmación
document.getElementById('confirmarPresentacionBtn')?.addEventListener('click', function() {
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmar'));
    modal.show();
});

// Función para mostrar el modal de rectificación
document.getElementById('solicitarRectificacionBtn')?.addEventListener('click', function() {
    const modal = new bootstrap.Modal(document.getElementById('modalRectificar'));
    modal.show();
});

// Función para mostrar el modal JSON
function showJsonModal(button) {
    const jsonData = JSON.parse(button.getAttribute('data-json'));
    document.getElementById('jsonContent').textContent = JSON.stringify(jsonData, null, 2);
    const modal = new bootstrap.Modal(document.getElementById('jsonModal'));
    modal.show();
}

// Función para mostrar el modal de parámetros SSN
function showSsnParamsModal() {
    const modal = new bootstrap.Modal(document.getElementById('ssnParamsModal'));
    modal.show();
}

// Función para descargar JSON
function downloadJson() {
    const jsonContent = document.getElementById('jsonContent').textContent;
    const blob = new Blob([jsonContent], { type: 'application/json' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'presentacion_{{ $presentation->cronograma }}_mensual.json';
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
}

// Configurar scroll horizontal
function setupHorizontalScroll() {
    const tableContainer = document.querySelector('.table-responsive');
    const scrollIndicator = document.getElementById('scrollIndicator');

    if (!tableContainer || !scrollIndicator) return;

    // Ocultar indicador si no hay scroll horizontal
    function checkScroll() {
        const hasHorizontalScroll = tableContainer.scrollWidth > tableContainer.clientWidth;
        scrollIndicator.style.display = hasHorizontalScroll ? 'block' : 'none';
    }

    // Verificar al cargar
    checkScroll();

    // Verificar al cambiar tamaño de ventana
    window.addEventListener('resize', checkScroll);

    // Ocultar indicador cuando se hace scroll
    tableContainer.addEventListener('scroll', function() {
        scrollIndicator.style.opacity = '0.3';
        clearTimeout(scrollIndicator.timeout);
        scrollIndicator.timeout = setTimeout(() => {
            scrollIndicator.style.opacity = '0.8';
        }, 1000);
    });

    // Mostrar indicador cuando se detiene el scroll
    tableContainer.addEventListener('scrollend', function() {
        scrollIndicator.style.opacity = '0.8';
    });
}

// Inicializar scroll horizontal cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    setupHorizontalScroll();
});
</script>

<style>
/* Estilos para el scroll horizontal */
.table-responsive {
    overflow-x: auto;
    overflow-y: hidden;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    scroll-behavior: smooth;
}

.table-responsive::-webkit-scrollbar {
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #374661;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #4a5a7a;
}

/* Estilos para la tabla */
#tabla-stocks {
    min-width: 2000px; /* Ancho mínimo para asegurar scroll */
}

#tabla-stocks th,
#tabla-stocks td {
    white-space: nowrap;
    min-width: 100px;
}

/* Estilos para campos específicos */
#tabla-stocks th:nth-child(1),
#tabla-stocks td:nth-child(1) {
    min-width: 120px; /* Tipo */
}

#tabla-stocks th:nth-child(2),
#tabla-stocks td:nth-child(2) {
    min-width: 150px; /* Nombre */
}

#tabla-stocks th:nth-child(4),
#tabla-stocks td:nth-child(4) {
    min-width: 120px; /* Código Especie */
}

#tabla-stocks th:nth-child(14),
#tabla-stocks td:nth-child(14) {
    min-width: 120px; /* Valor Contable */
}

/* Estilos para indicar scroll horizontal */
.table-container {
    position: relative;
    margin-bottom: 2rem;
}

.scroll-indicator {
    position: absolute;
    bottom: -15px;
    left: 50%;
    transform: translateX(-50%);
    background: #374661;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    opacity: 0.9;
    pointer-events: none;
    z-index: 10;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    transition: opacity 0.3s ease;
    display: none;
}

.scroll-indicator i {
    animation: slideLeftRight 2s ease-in-out infinite;
}

@keyframes slideLeftRight {
    0%, 100% { transform: translateX(0); }
    50% { transform: translateX(5px); }
}

/* Estilos para dispositivos móviles */
@media (max-width: 768px) {
    .scroll-indicator {
        font-size: 0.7rem;
        padding: 0.4rem 0.8rem;
        bottom: -12px;
    }
}
</style>
@endsection 