@extends('layouts.app')

@section('title', 'Nueva Presentación Semanal - Esencia Seguros')

@section('content')
    <div class="container">
        <div class="main-content">
            <h1 class="page-title">
                <i class="fas fa-calendar-week me-2"></i>
                @if($selectedWeek)
                    Rectificar Presentación Semanal
                @else
                    Nueva Presentación Semanal
                @endif
            </h1>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('weekly-presentations.import') }}" method="POST" enctype="multipart/form-data" id="importForm">
                @csrf
                
                <!-- Contenedor principal con dos columnas -->
                <div class="row equal-height-columns two-column-layout">
                    <!-- Columna izquierda: Selección de Semana -->
                    <div class="col-md-6">
                        <div class="form-section h-100">
                            <h4>
                                <i class="fas fa-calendar-alt"></i>
                                Seleccionar Semana
                            </h4>
                            <div>
                                <label for="week" class="form-label fw-bold">Semana a presentar:</label>
                                <select name="week" id="week" class="form-select week-selector" required {{ $selectedWeek ? 'disabled' : '' }}>
                                    <option value="">Selecciona una semana...</option>
                                    @foreach($availableWeeks as $week)
                                        <option value="{{ $week['value'] }}" 
                                                {{ ($selectedWeek && $selectedWeek == $week['value']) ? 'selected' : '' }}
                                                {{ $week['has_existing'] ? 'style="background-color: #fff3cd;"' : '' }}>
                                            {{ $week['text'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($selectedWeek)
                                    <input type="hidden" name="week" value="{{ $selectedWeek }}">
                                @endif
                                <div class="form-text mt-2">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Se muestran semanas desde 1 mes atrás hasta la anteúltima semana. 
                                    Las semanas con presentaciones en estado "PRESENTADO" o "RECTIFICACION PENDIENTE" no están disponibles.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna derecha: Upload de Archivo -->
                    <div class="col-md-6">
                        <div class="form-section h-100">
                            <h4>
                                <i class="fas fa-file-excel"></i>
                                Importar Archivo Excel
                            </h4>
                            <div class="file-upload-area" id="fileUploadArea">
                                <div class="upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <h5 class="mb-3">Arrastra tu archivo Excel aquí o haz clic para seleccionar</h5>
                                <p class="text-muted mb-3">Formatos soportados: .xlsx, .xls (Máximo 10MB)</p>
                                <input type="file" name="excel_file" id="excelFile" class="d-none" accept=".xlsx,.xls" required>
                                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('excelFile').click()">
                                    <i class="fas fa-folder-open me-2"></i>
                                    Seleccionar Archivo
                                </button>
                            </div>
                            <div id="fileInfo" class="mt-3" style="display: none;">
                                <div class="alert alert-info">
                                    <i class="fas fa-file-excel me-2"></i>
                                    <strong id="fileName"></strong>
                                    <span id="fileSize" class="ms-2"></span>
                                </div>
                                <button type="button" class="btn btn-success w-100" id="importBtn" onclick="processExcel()">
                                    <i class="fas fa-upload me-2"></i>
                                    Importar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Previsualización -->
                <div id="previewSection" class="mt-4" style="display: none;">
                    <div class="form-section">
                        <h4>
                            <i class="fas fa-table me-2"></i>
                            Previsualización de Datos
                        </h4>
                        <div class="row mb-3 align-items-center g-3">
                            <div class="col-md-8 col-12">
                                <div class="alert alert-info mb-0">
                                    <strong>Resumen:</strong>
                                    <div id="summaryInfo"></div>
                                </div>
                            </div>
                            <div class="col-md-4 col-12 text-md-end text-center mt-2 mt-md-0">
                                <button type="button" class="btn btn-primary" onclick="generateJson()">
                                    <i class="fas fa-code me-2"></i>
                                    Ver JSON
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover preview-table" id="previewTable">
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
                                        <th>Precio Venta</th>
                                        <th>Fecha Pase VT</th>
                                        <th>Precio Pase VT</th>
                                    </tr>
                                </thead>
                                <tbody id="previewTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="text-center mt-4">
                    <a href="{{ route('dashboard') }}" class="btn btn-back btn-lg me-3">
                        <i class="fas fa-arrow-left me-2"></i>
                        Volver al Dashboard
                    </a>
                    <button type="button" class="btn btn-success btn-lg" id="saveDraftBtn" style="display:none;">
                        <i class="fas fa-save me-2"></i>
                        Guardar Borrador
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para mostrar JSON -->
    <div class="modal fade json-modal" id="jsonModal" tabindex="-1" aria-labelledby="jsonModalLabel" aria-hidden="true">
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
            --esencia-border-light: #e9ecef;
            --esencia-shadow-card: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        body {
            background: linear-gradient(135deg, var(--esencia-secondary) 0%, #f5f5f5 100%);
            min-height: 100vh;
        }

        .container {
            padding: 1rem 0;
        }

        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: var(--esencia-shadow-card);
            padding: 3rem;
            margin: 2rem 0;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .page-title {
            color: var(--esencia-primary);
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: center;
        }

        .form-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: var(--esencia-shadow-card);
            margin-bottom: 2rem;
            height: 100%;
        }

        .form-section h4 {
            color: var(--esencia-primary);
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Estilos para el layout de dos columnas */
        .two-column-layout {
            margin-bottom: 2rem;
        }

        .two-column-layout .form-section {
            margin-bottom: 0;
        }

        /* Asegurar que ambas columnas tengan la misma altura */
        .equal-height-columns {
            display: flex;
            flex-wrap: wrap;
        }

        .equal-height-columns > .col-md-6 {
            display: flex;
            flex-direction: column;
        }

        .equal-height-columns .form-section {
            flex: 1;
        }

        .week-selector {
            color: black;
            border: 1px solid   black;
            border-radius: 10px;
            padding: 1rem;
            font-size: 1.1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .week-selector:focus {
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            transform: translateY(-2px);
        }

        .file-upload-area {
            border: 3px dashed var(--esencia-primary);
            border-radius: 15px;
            padding: 3rem 2rem;
            text-align: center;
            background: rgba(55, 70, 97, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-upload-area:hover {
            border-color: var(--esencia-primary-light);
            background: rgba(55, 70, 97, 0.1);
            transform: translateY(-2px);
        }

        .file-upload-area.dragover {
            border-color: var(--esencia-success);
            background: rgba(40, 167, 69, 0.1);
        }

        .upload-icon {
            font-size: 3rem;
            color: var(--esencia-primary);
            margin-bottom: 1rem;
        }

        .btn-back {
            background: linear-gradient(135deg, var(--esencia-text-secondary), #6c757d);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 2rem 1.5rem;
                margin: 1rem 0;
            }
            
            .form-section {
                padding: 1.5rem;
                margin-bottom: 1rem;
            }
            
            .file-upload-area {
                padding: 2rem 1rem;
            }

            /* En móviles, las columnas se apilan verticalmente */
            .equal-height-columns > .col-md-6 {
                margin-bottom: 1rem;
            }

            .two-column-layout {
                margin-bottom: 1rem;
            }
        }

        /* Estilos para la tabla de previsualización */
        .preview-table {
            font-size: 0.9rem;
        }

        .preview-table th {
            font-weight: 600;
            text-align: center;
            vertical-align: middle;
        }

        .preview-table td {
            vertical-align: middle;
        }

        .preview-table .badge {
            font-size: 0.75rem;
        }

        /* Estilos para el modal JSON */
        .json-modal .modal-dialog {
            max-width: 90%;
        }

        .json-content {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        /* Estilos para el resumen */
        .summary-info {
            font-size: 0.9rem;
        }

        .summary-info div {
            margin-bottom: 0.25rem;
        }

        /* Estilos para botones de acción */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        /* Estilos para loading */
        .btn-loading {
            pointer-events: none;
        }

        .btn-loading .fa-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <script>
        // Variables globales
        let processedData = null;
        let selectedWeek = '{{ $selectedWeek ?? "" }}';
        let excelFileInfo = null;

        // File upload handling
        const fileUploadArea = document.getElementById('fileUploadArea');
        const excelFile = document.getElementById('excelFile');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const weekSelect = document.getElementById('week');

        // Drag and drop functionality
        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });

        fileUploadArea.addEventListener('dragleave', () => {
            fileUploadArea.classList.remove('dragover');
        });

        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                excelFile.files = files;
                handleFileSelect();
            }
        });

        fileUploadArea.addEventListener('click', () => {
            excelFile.click();
        });

        excelFile.addEventListener('change', handleFileSelect);

        function handleFileSelect() {
            const file = excelFile.files[0];
            if (file) {
                fileName.textContent = file.name;
                fileSize.textContent = `(${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                fileInfo.style.display = 'block';
            }
        }

        // Procesar Excel
        function processExcel() {
            const file = excelFile.files[0];
            const week = weekSelect.value;

            if (!file) {
                alert('Por favor selecciona un archivo Excel.');
                return;
            }

            if (!week) {
                alert('Por favor selecciona una semana.');
                return;
            }

            selectedWeek = week;

            // Mostrar loading
            const importBtn = document.getElementById('importBtn');
            const originalText = importBtn.innerHTML;
            importBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
            importBtn.disabled = true;

            const formData = new FormData();
            formData.append('excel_file', file);
            formData.append('week', week);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route("weekly-presentations.process-excel") }}', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    processedData = data.data;
                    // Guardar información del archivo Excel procesado
                    excelFileInfo = data.data.excel_file_info;
                    showPreview(data.data);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar el archivo.');
            })
            .finally(() => {
                importBtn.innerHTML = originalText;
                importBtn.disabled = false;
            });
        }

        // Mostrar previsualización
        function showPreview(data) {
            const previewSection = document.getElementById('previewSection');
            const summaryInfo = document.getElementById('summaryInfo');
            const tableBody = document.getElementById('previewTableBody');

            // Mostrar resumen
            summaryInfo.innerHTML = `
                <div>Total operaciones: <strong>${data.total_operations}</strong></div>
                <div>Compras: <strong>${data.summary.por_tipo.compras}</strong></div>
                <div>Ventas: <strong>${data.summary.por_tipo.ventas}</strong></div>
                <div>Canjes: <strong>${data.summary.por_tipo.canjes}</strong></div>
            `;

            // Limpiar tabla
            tableBody.innerHTML = '';

            // Llenar tabla con datos
            data.operations.forEach(operation => {
                const row = document.createElement('tr');
                
                // Determinar qué campos mostrar según el tipo de operación
                let precioCompraField = '-';
                let precioVentaField = '-';
                let fechaPaseField = '-';
                let precioPaseField = '-';
                
                if (operation.tipo_operacion === 'C') {
                    precioCompraField = operation.precio_compra ? parseFloat(operation.precio_compra).toFixed(4) : '-';
                } else if (operation.tipo_operacion === 'V') {
                    precioVentaField = operation.precio_venta ? parseFloat(operation.precio_venta).toFixed(4) : '-';
                    fechaPaseField = formatDate(operation.fecha_pase_vt);
                    precioPaseField = operation.precio_pase_vt ? parseFloat(operation.precio_pase_vt).toFixed(4) : '-';
                }
                
                row.innerHTML = `
                    <td><span class="badge bg-${getOperationBadgeColor(operation.tipo_operacion)}">${getOperationLabel(operation.tipo_operacion)}</span></td>
                    <td>${operation.tipo_especie || '-'}</td>
                    <td><code>${operation.codigo_especie || '-'}</code></td>
                    <td>${operation.cant_especies ? parseFloat(operation.cant_especies).toLocaleString() : '-'}</td>
                    <td>${operation.codigo_afectacion || '-'}</td>
                    <td>${operation.tipo_valuacion || '-'}</td>
                    <td>${formatDate(operation.fecha_movimiento)}</td>
                    <td>${precioCompraField}</td>
                    <td>${formatDate(operation.fecha_liquidacion)}</td>
                    <td>${precioVentaField}</td>
                    <td>${fechaPaseField}</td>
                    <td>${precioPaseField}</td>
                `;
                tableBody.appendChild(row);
            });

            previewSection.style.display = 'block';
            previewSection.scrollIntoView({ behavior: 'smooth' });

            // Mostrar el botón Guardar Borrador solo si hay datos cargados
            toggleSaveDraftBtn(true);
        }

        // Generar JSON
        function generateJson() {
            if (!processedData) {
                alert('No hay datos procesados para generar JSON.');
                return;
            }
            const formData = new FormData();
            formData.append('operations', JSON.stringify(processedData.operations));
            formData.append('week', selectedWeek);
            formData.append('_token', '{{ csrf_token() }}');
            fetch('{{ route("weekly-presentations.generate-json") }}', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showJsonModal(data.data);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al generar el JSON.');
            });
        }

        // Mostrar modal con JSON
        function showJsonModal(jsonData) {
            let pretty = '';
            try {
                pretty = JSON.stringify(jsonData, null, 2);
            } catch (e) {
                pretty = jsonData;
            }
            document.getElementById('jsonContent').textContent = pretty;
            const modal = new bootstrap.Modal(document.getElementById('jsonModal'));
            modal.show();
        }

        // Funciones auxiliares
        function getOperationBadgeColor(tipo) {
            switch (tipo) {
                case 'C': return 'success';
                case 'V': return 'danger';
                case 'J': return 'warning';
                default: return 'secondary';
            }
        }

        function getOperationLabel(tipo) {
            switch (tipo) {
                case 'C': return 'COMPRA';
                case 'V': return 'VENTA';
                case 'J': return 'CANJE';
                default: return tipo;
            }
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            
            // Si la fecha está en formato YYYY-MM-DD, parsearla manualmente para evitar desfases de zona horaria
            if (dateString.match(/^\d{4}-\d{2}-\d{2}$/)) {
                const parts = dateString.split('-');
                const year = parts[0];
                const month = parts[1];
                const day = parts[2];
                return `${day}/${month}/${year}`;
            }
            
            // Para otros formatos, usar el método original
            const date = new Date(dateString);
            const day = date.getDate().toString().padStart(2, '0');
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        }

        // Mostrar el botón Guardar Borrador solo si hay datos cargados
        function toggleSaveDraftBtn(show) {
            const btn = document.getElementById('saveDraftBtn');
            btn.style.display = show ? 'inline-block' : 'none';
        }

        // Guardar borrador al hacer click
        const saveDraftBtn = document.getElementById('saveDraftBtn');
        saveDraftBtn.addEventListener('click', function() {
            if (!processedData) return;
            
            const formData = new FormData();
            formData.append('operations', JSON.stringify(processedData.operations));
            formData.append('week', selectedWeek);
            formData.append('_token', '{{ csrf_token() }}');
            
            // Agregar información del archivo Excel ya procesado y guardado
            if (excelFileInfo) {
                formData.append('original_filename', excelFileInfo.original_filename);
                formData.append('original_file_path', excelFileInfo.saved_path);
            }
            
            fetch('{{ route('weekly-presentations.save-draft') }}', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    alert('Error: ' + (data.message || 'No se pudo guardar el borrador.'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar el borrador.');
            });
        });
    </script>
@endsection 