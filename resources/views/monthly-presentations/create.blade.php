@extends('layouts.app')

@section('title', 'Nueva Presentación Mensual - Esencia Seguros')

@section('content')
    <div class="container">
        <div class="main-content">
            <h1 class="page-title">
                <i class="fas fa-calendar-alt me-2"></i>
                @if($selectedMonth)
                    Rectificar Presentación Mensual
                @else
                    Nueva Presentación Mensual
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

            <form action="{{ route('monthly-presentations.import') }}" method="POST" enctype="multipart/form-data" id="importForm">
                @csrf
                
                <!-- Contenedor principal con dos columnas -->
                <div class="row equal-height-columns two-column-layout">
                    <!-- Columna izquierda: Selección de Mes -->
                    <div class="col-md-6">
                        <div class="form-section h-100">
                            <h4>
                                <i class="fas fa-calendar-alt"></i>
                                Seleccionar Mes
                            </h4>
                            <div>
                                <label for="month" class="form-label fw-bold">Mes a presentar:</label>
                                <select name="month" id="month" class="form-select month-selector" required {{ $selectedMonth ? 'disabled' : '' }}>
                                    <option value="">Selecciona un mes...</option>
                                    @foreach($availableMonths as $month)
                                        <option value="{{ $month['value'] }}" 
                                                {{ ($selectedMonth && $selectedMonth == $month['value']) ? 'selected' : '' }}
                                                {{ $month['has_existing'] ? 'style="background-color: #fff3cd;"' : '' }}>
                                            {{ $month['text'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($selectedMonth)
                                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                @endif
                                <div class="form-text mt-2">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Se muestran meses desde 1 mes atrás hasta el anteúltimo mes. 
                                    Los meses con presentaciones en estado "PRESENTADO" o "RECTIFICACION PENDIENTE" no están disponibles.
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
                            <div class="table-container">
                                <table class="table table-striped table-hover preview-table" id="previewTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Nombre</th>
                                            <th>Tipo Especie</th>
                                            <th>Código SSN</th>
                                            <th>Cant. Devengado</th>
                                            <th>Cant. Percibido</th>
                                            <th>Código Afect.</th>
                                            <th>Tipo Valuac.</th>
                                            <th>Valor Contable</th>
                                            <th>En Custodia</th>
                                            <th>Con Cotización</th>
                                            <th>Libre Disponibilidad</th>
                                            <th>Emisor Grupo Econ.</th>
                                            <th>Emisor Art. Ret.</th>
                                            <th>Previsión Desval.</th>
                                            <th>Financiera</th>
                                            <th>Valor Financiero</th>
                                        </tr>
                                    </thead>
                                    <tbody id="previewTableBody">
                                    </tbody>
                                </table>
                                <div class="scroll-indicator" id="scrollIndicator">
                                    <i class="fas fa-arrows-alt-h me-1"></i>
                                    Desliza horizontalmente para ver más columnas
                                </div>
                            </div>
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

        .equal-height-columns {
            display: flex;
            flex-wrap: wrap;
        }

        .equal-height-columns > [class*="col-"] {
            display: flex;
            flex-direction: column;
        }

        .equal-height-columns .form-section {
            flex: 1;
        }

        /* Estilos para el selector de mes */
        .month-selector {
            border: 2px solid var(--esencia-border-light);
            border-radius: 10px;
            padding: 0.75rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .month-selector:focus {
            border-color: var(--esencia-primary);
            box-shadow: 0 0 0 0.2rem rgba(55, 70, 97, 0.25);
            outline: none;
        }

        .month-selector:disabled {
            background-color: #f8f9fa;
            opacity: 0.7;
        }

        /* Estilos para el área de upload */
        .file-upload-area {
            border: 3px dashed var(--esencia-border-light);
            border-radius: 15px;
            padding: 3rem 2rem;
            text-align: center;
            transition: all 0.3s ease;
            background: #fafafa;
            cursor: pointer;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .file-upload-area:hover {
            border-color: var(--esencia-primary);
            background: #f8f9ff;
        }

        .file-upload-area.dragover {
            border-color: var(--esencia-primary);
            background: #f0f4ff;
            transform: scale(1.02);
        }

        .upload-icon {
            font-size: 3rem;
            color: var(--esencia-primary);
            margin-bottom: 1rem;
        }

        /* Estilos para botones */
        .btn-back {
            background: linear-gradient(135deg, #6c757d, #495057);
            border: none;
            color: white;
            font-weight: 600;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--esencia-success), #1e7e34);
            border: none;
            font-weight: 600;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        /* Estilos para la tabla de previsualización */
        .preview-table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            font-size: 0.85rem;
        }

        .preview-table th,
        .preview-table td {
            padding: 0.5rem 0.25rem;
            vertical-align: middle;
            white-space: nowrap;
        }

        .preview-table th {
            font-size: 0.8rem;
            font-weight: 600;
        }

        .preview-table .badge {
            font-size: 0.7rem;
        }

        .preview-table code {
            font-size: 0.75rem;
            background-color: #f8f9fa;
            padding: 0.1rem 0.3rem;
            border-radius: 0.25rem;
        }

        .preview-table thead th {
            background: var(--esencia-primary);
            color: white;
            border: none;
            padding: 1rem 0.75rem;
            font-weight: 600;
        }

        .preview-table tbody td {
            padding: 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--esencia-border-light);
        }

        .preview-table tbody tr:hover {
            background-color: #f8f9ff;
        }

        /* Scroll horizontal para la tabla */
        .table-responsive {
            overflow-x: auto;
            overflow-y: hidden;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .table-responsive::-webkit-scrollbar {
            height: 8px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: var(--esencia-primary);
            border-radius: 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: var(--esencia-primary-light);
        }

        /* Asegurar que la tabla mantenga su ancho mínimo */
        .preview-table {
            min-width: 1200px; /* Ancho mínimo para mostrar todas las columnas */
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
            background: var(--esencia-primary);
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

        /* Mejorar la experiencia de scroll */
        .table-responsive {
            scroll-behavior: smooth;
        }

        /* Estilos para dispositivos móviles */
        @media (max-width: 768px) {
            .scroll-indicator {
                font-size: 0.7rem;
                padding: 0.4rem 0.8rem;
                bottom: -12px;
            }
            
            .preview-table {
                min-width: 1000px;
            }
        }

        /* Estilos para badges */
        .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-weight: 600;
        }

        /* Estilos para el modal JSON */
        .json-modal .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .json-modal .modal-header {
            background: var(--esencia-primary);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .json-content {
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            line-height: 1.5;
            color: #333;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 2rem 1rem;
                margin: 1rem 0;
            }

            .form-section {
                padding: 1.5rem;
            }

            .file-upload-area {
                padding: 2rem 1rem;
            }

            .page-title {
                font-size: 1.5rem;
            }
        }

        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-section {
            animation: fadeIn 0.5s ease-out;
        }

        /* Loading spinner */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive para tabla con muchas columnas */
        @media (max-width: 1200px) {
            .preview-table {
                font-size: 0.75rem;
            }
            
            .preview-table th,
            .preview-table td {
                padding: 0.25rem 0.1rem;
            }
        }

        @media (max-width: 768px) {
            .preview-table {
                font-size: 0.7rem;
            }
            
            .preview-table th,
            .preview-table td {
                padding: 0.2rem 0.05rem;
            }
        }
    </style>

    <script>
        // Variables globales
        let processedData = null;
        let selectedMonth = '{{ $selectedMonth ?? "" }}';
        let excelFileInfo = null;

        // File upload handling
        const fileUploadArea = document.getElementById('fileUploadArea');
        const excelFile = document.getElementById('excelFile');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const monthSelect = document.getElementById('month');

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
            const month = monthSelect.value;

            if (!file) {
                alert('Por favor selecciona un archivo Excel.');
                return;
            }

            if (!month) {
                alert('Por favor selecciona un mes.');
                return;
            }

            selectedMonth = month;

            // Mostrar loading
            const importBtn = document.getElementById('importBtn');
            const originalText = importBtn.innerHTML;
            importBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
            importBtn.disabled = true;

            const formData = new FormData();
            formData.append('excel_file', file);
            formData.append('month', month);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route("monthly-presentations.process-excel") }}', {
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
            const scrollIndicator = document.getElementById('scrollIndicator');

            // Mostrar resumen
            summaryInfo.innerHTML = `
                <div>Total stocks: <strong>${data.total_stocks}</strong></div>
                <div>Inversiones: <strong>${data.summary.por_tipo.inversiones || 0}</strong></div>
                <div>Plazos fijos: <strong>${data.summary.por_tipo.plazos_fijos || 0}</strong></div>
                <div>Otros: <strong>${data.summary.por_tipo.otros || 0}</strong></div>
            `;

            // Limpiar tabla
            tableBody.innerHTML = '';

            // Llenar tabla con datos
            data.stocks.forEach(stock => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><span class="badge bg-${getStockBadgeColor(stock.tipo)}">${getStockLabel(stock.tipo)}</span></td>
                    <td>${stock.nombre || '-'}</td>
                    <td>${stock.tipo_especie || '-'}</td>
                    <td><code>${stock.codigo_especie || '-'}</code></td>
                    <td>${stock.cantidad_devengado_especies ? formatNumber(stock.cantidad_devengado_especies) : '-'}</td>
                    <td>${stock.cantidad_percibido_especies ? formatNumber(stock.cantidad_percibido_especies) : '-'}</td>
                    <td>${stock.codigo_afectacion || '-'}</td>
                    <td>${stock.tipo_valuacion || '-'}</td>
                    <td>${stock.valor_contable ? formatNumber(stock.valor_contable) : '-'}</td>
                    <td>${stock.en_custodia ? 'Sí' : 'No'}</td>
                    <td>${stock.con_cotizacion ? 'Sí' : 'No'}</td>
                    <td>${stock.libre_disponibilidad ? 'Sí' : 'No'}</td>
                    <td>${stock.emisor_grupo_economico ? 'Sí' : 'No'}</td>
                    <td>${stock.emisor_art_ret ? 'Sí' : 'No'}</td>
                    <td>${stock.prevision_desvalorizacion ? formatNumber(stock.prevision_desvalorizacion) : '-'}</td>
                    <td>${stock.financiera ? 'Sí' : 'No'}</td>
                    <td>${stock.valor_financiero ? formatNumber(stock.valor_financiero) : '-'}</td>
                `;
                tableBody.appendChild(row);
            });

            previewSection.style.display = 'block';
            previewSection.scrollIntoView({ behavior: 'smooth' });

            // Configurar scroll horizontal
            setupHorizontalScroll();

            // Mostrar el botón Guardar Borrador solo si hay datos cargados
            toggleSaveDraftBtn(true);
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

        // Generar JSON
        function generateJson() {
            if (!processedData) {
                alert('No hay datos procesados para generar JSON.');
                return;
            }
            const formData = new FormData();
            formData.append('stocks', JSON.stringify(processedData.stocks));
            formData.append('month', selectedMonth);
            formData.append('_token', '{{ csrf_token() }}');
            fetch('{{ route("monthly-presentations.generate-json") }}', {
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
        function getStockBadgeColor(tipo) {
            switch (tipo) {
                case 'I': return 'success';
                case 'P': return 'primary';
                case 'O': return 'warning';
                default: return 'secondary';
            }
        }

        function getStockLabel(tipo) {
            switch (tipo) {
                case 'I': return 'INVERSIÓN';
                case 'P': return 'PLAZO FIJO';
                case 'O': return 'OTRO';
                default: return 'DESCONOCIDO';
            }
        }

        // Función para formatear números correctamente
        function formatNumber(value) {
            if (!value || value === '-') return '-';
            
            // Convertir a string y limpiar
            let numStr = String(value).trim();
            
            // Si ya tiene punto como separador decimal, convertirlo a coma
            if (numStr.includes('.')) {
                const num = parseFloat(numStr);
                if (isNaN(num)) return '-';
                
                // Determinar cuántos decimales mostrar
                const decimalPart = numStr.split('.')[1];
                const decimalPlaces = decimalPart ? Math.min(decimalPart.length, 6) : 0;
                
                // Formatear sin separadores de miles y con coma decimal
                return num.toFixed(decimalPlaces).replace('.', ',');
            }
            
            // Si tiene coma como separador decimal, usarlo directamente
            if (numStr.includes(',')) {
                const num = parseFloat(numStr.replace(',', '.'));
                if (isNaN(num)) return '-';
                
                // Determinar cuántos decimales mostrar
                const decimalPart = numStr.split(',')[1];
                const decimalPlaces = decimalPart ? Math.min(decimalPart.length, 6) : 0;
                
                // Formatear sin separadores de miles y con coma decimal
                return num.toFixed(decimalPlaces).replace('.', ',');
            }
            
            // Si es un número entero
            const num = parseFloat(numStr);
            if (isNaN(num)) return '-';
            
            return num.toString();
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString('es-AR');
            } catch (e) {
                return dateString;
            }
        }

        function toggleSaveDraftBtn(show) {
            const saveDraftBtn = document.getElementById('saveDraftBtn');
            if (show) {
                saveDraftBtn.style.display = 'inline-block';
                saveDraftBtn.onclick = saveDraft;
            } else {
                saveDraftBtn.style.display = 'none';
            }
        }

        function saveDraft() {
            if (!processedData) {
                alert('No hay datos para guardar.');
                return;
            }

            const formData = new FormData();
            formData.append('stocks', JSON.stringify(processedData.stocks));
            formData.append('month', selectedMonth);
            formData.append('original_filename', excelFileInfo?.original_filename || '');
            formData.append('original_file_path', excelFileInfo?.saved_path || '');
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route("monthly-presentations.save-draft") }}', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect_url;
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar el borrador.');
            });
        }
    </script>
@endsection 