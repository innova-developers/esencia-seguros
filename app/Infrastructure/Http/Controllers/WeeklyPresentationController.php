<?php

namespace App\Infrastructure\Http\Controllers;

use App\Domain\Models\ActivityLog;
use App\Domain\Models\Presentation;
use App\Domain\Models\WeeklyOperation;
use App\Services\ExcelProcessorService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class WeeklyPresentationController extends Controller
{
    protected $excelProcessor;

    public function __construct(ExcelProcessorService $excelProcessor)
    {
        $this->excelProcessor = $excelProcessor;
    }

    public function create(Request $request)
    {
        // Verificar que el usuario esté autenticado
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Si viene un parámetro week, validar que sea válido para presentar
        $selectedWeek = $request->query('week');
        if ($selectedWeek) {
            $validationResult = $this->validateWeekForPresentation($selectedWeek);
            if (!$validationResult['valid']) {
                return redirect()->route('weekly-presentations.index')
                    ->with('error', $validationResult['message']);
            }
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'VIEW_CREATE_FORM',
            'description' => 'Usuario accedió al formulario de nueva presentación semanal',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Generar semanas disponibles según las reglas de negocio
        $availableWeeks = $this->getAvailableWeeks();

        return view('weekly-presentations.create', compact('availableWeeks', 'selectedWeek'));
    }

    /**
     * Validar si una semana es válida para crear una presentación
     */
    private function validateWeekForPresentation($week)
    {
        // Verificar si existe una presentación con estados bloqueantes
        $existingPresentation = Presentation::where('cronograma', $week)
            ->where('tipo_entrega', 'Semanal')
            ->whereIn('estado', ['PRESENTADO', 'RECTIFICACION_PENDIENTE'])
            ->first();

        if ($existingPresentation) {
            return [
                'valid' => false,
                'message' => "No se puede crear una presentación para la semana {$week} porque ya existe una con estado '{$existingPresentation->estado}'."
            ];
        }

        return ['valid' => true, 'message' => ''];
    }

    private function getAvailableWeeks()
    {
        $currentDate = Carbon::now();
        $oneMonthAgo = $currentDate->copy()->subMonth();
        $twoWeeksAgo = $currentDate->copy()->subWeeks(2);
        
        $availableWeeks = [];
        
        // Generar semanas desde 1 mes atrás hasta la anteúltima semana
        $startDate = $oneMonthAgo->startOfWeek();
        $endDate = $twoWeeksAgo->endOfWeek();
        
        $currentWeek = $startDate->copy();
        
        while ($currentWeek <= $endDate) {
            $weekNumber = $currentWeek->format('W');
            $year = $currentWeek->format('Y');
            $weekStart = $currentWeek->format('d/m');
            $weekEnd = $currentWeek->copy()->endOfWeek()->format('d/m');
            
            $weekKey = "{$year}-{$weekNumber}";
            
            // Verificar si ya existe una presentación con estados bloqueantes
            $existingPresentation = Presentation::where('cronograma', $weekKey)
                ->whereIn('estado', ['PRESENTADO', 'RECTIFICACION_PENDIENTE'])
                ->first();
            
            // Solo incluir si no existe presentación con estados bloqueantes
            if (!$existingPresentation) {
                $displayText = "Semana {$weekNumber} ({$year}) - {$weekStart} al {$weekEnd}";
                
                // Marcar si ya existe una presentación en otros estados
                if (Presentation::where('cronograma', $weekKey)->exists()) {
                    $existing = Presentation::where('cronograma', $weekKey)->first();
                    $displayText .= " (Estado actual: {$existing->estado})";
                }
                
                $availableWeeks[] = [
                    'value' => $weekKey,
                    'text' => $displayText,
                    'week_start' => $currentWeek->format('Y-m-d'), // Mantener formato original para la base de datos
                    'week_end' => $currentWeek->copy()->endOfWeek()->format('Y-m-d'), // Mantener formato original para la base de datos
                    'has_existing' => Presentation::where('cronograma', $weekKey)->exists()
                ];
            }
            
            $currentWeek->addWeek();
        }
        
        return $availableWeeks;
    }

    public function processExcel(Request $request)
    {
        $request->validate([
            'week' => 'required|string',
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
        ]);

        $week = $request->input('week');
        $file = $request->file('excel_file');

        // Obtener información del archivo ANTES de moverlo
        $originalFilename = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();
        $extension = $file->getClientOriginalExtension();

        try {
            // Validar que la semana no tenga presentación con estados bloqueantes
            $existingPresentation = Presentation::where('cronograma', $week)
                ->whereIn('estado', ['PRESENTADO', 'RECTIFICACION_PENDIENTE'])
                ->first();

            if ($existingPresentation) {
                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'VALIDATION_ERROR',
                    'description' => "Intento de procesar Excel para semana {$week} que ya tiene estado bloqueante: {$existingPresentation->estado}",
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'metadata' => json_encode([
                        'week' => $week,
                        'existing_status' => $existingPresentation->estado,
                    ]),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => "No se puede procesar el archivo para la semana {$week} porque ya existe una presentación con estado '{$existingPresentation->estado}'."
                ], 400);
            }

            // Log del inicio del procesamiento
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'EXCEL_PROCESS_START',
                'description' => "Iniciando procesamiento de Excel para la semana: {$week}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'week' => $week,
                    'filename' => $originalFilename,
                    'filesize' => $fileSize,
                    'mime_type' => $mimeType,
                ]),
            ]);

            // Guardar el archivo Excel permanentemente
            $excelDirectory = storage_path('app/presentations/excel');
            if (!file_exists($excelDirectory)) {
                mkdir($excelDirectory, 0755, true);
            }
            
            // Generar nombre único para el archivo Excel
            $excelFilename = 'presentation_' . $week . '_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
            $excelFilePath = $excelDirectory . '/' . $excelFilename;
            
            // Guardar el archivo Excel
            $file->move($excelDirectory, $excelFilename);
            
            // Usar el archivo guardado para procesamiento
            if (!file_exists($excelFilePath)) {
                throw new \Exception("El archivo guardado no existe: {$excelFilePath}");
            }

            // Procesar el Excel desde el archivo guardado
            $result = $this->excelProcessor->processWeeklyExcel($excelFilePath, $week);

            // Agregar información del archivo guardado al resultado
            $result['excel_file_info'] = [
                'original_filename' => $originalFilename,
                'saved_filename' => $excelFilename,
                'saved_path' => 'presentations/excel/' . $excelFilename,
                'file_size' => $fileSize,
            ];

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'EXCEL_PROCESSED',
                'description' => "Excel procesado exitosamente para la semana: {$week}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'week' => $week,
                    'total_operations' => $result['total_operations'],
                    'summary' => $result['summary'],
                    'excel_file_info' => $result['excel_file_info'],
                ]),
            ]);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'EXCEL_PROCESS_ERROR',
                'description' => "Error al procesar Excel para la semana: {$week}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'week' => $week,
                    'error' => $e->getMessage(),
                    'file' => $originalFilename ?? 'unknown',
                    'trace' => $e->getTraceAsString(),
                ]),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateJson(Request $request)
    {
        $request->validate([
            'operations' => 'required',
            'week' => 'required|string',
        ]);

        try {
            $operations = json_decode($request->input('operations'), true);
            $week = $request->input('week');

            // Generar el JSON completo como lo hace Presentation::getSsnJson()
            $codigoCompania = env('SSN_CIA', '1');
            
            $ssnJson = [
                'codigoCompania' => $codigoCompania,
                'cronograma' => $week,
                'tipoEntrega' => 'Semanal',
                'operaciones' => []
            ];

            // Convertir las operaciones al formato correcto
            foreach ($operations as $operation) {
                $ssnOperation = [
                    'tipoOperacion' => $operation['tipo_operacion'],
                    'tipoEspecie' => $operation['tipo_especie'],
                    'codigoEspecie' => $operation['codigo_especie'],
                    'cantEspecies' => (float) $operation['cant_especies'],
                    'codigoAfectacion' => $operation['codigo_afectacion'],
                    'tipoValuacion' => $operation['tipo_valuacion'],
                    'fechaMovimiento' => $operation['fecha_movimiento'],
                    'fechaLiquidacion' => $operation['fecha_liquidacion'],
                ];
                
                // Agregar campos específicos según el tipo de operación
                if ($operation['tipo_operacion'] === 'C' && !empty($operation['precio_compra'])) {
                    $ssnOperation['precioCompra'] = (float) $operation['precio_compra'];
                }
                
                $ssnJson['operaciones'][] = $ssnOperation;
            }

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'JSON_GENERATED',
                'description' => "JSON generado para la semana: {$week}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'week' => $week,
                    'total_operations' => count($operations),
                ]),
            ]);

            return response()->json([
                'success' => true,
                'data' => $ssnJson
            ]);

        } catch (\Exception $e) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'JSON_GENERATION_ERROR',
                'description' => "Error al generar JSON para la semana: {$week}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'week' => $week,
                    'error' => $e->getMessage(),
                ]),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar el JSON: ' . $e->getMessage()
            ], 500);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'week' => 'required|string',
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
        ]);

        try {
            $week = $request->input('week');
            $file = $request->file('excel_file');

            // Validar que la semana no tenga presentación con estados bloqueantes
            $existingPresentation = Presentation::where('cronograma', $week)
                ->whereIn('estado', ['PRESENTADO', 'RECTIFICACION_PENDIENTE'])
                ->first();

            if ($existingPresentation) {
                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'VALIDATION_ERROR',
                    'description' => "Intento de crear presentación para semana {$week} que ya tiene estado bloqueante: {$existingPresentation->estado}",
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'metadata' => json_encode([
                        'week' => $week,
                        'existing_status' => $existingPresentation->estado,
                    ]),
                ]);

                return redirect()->back()->with('error', "No se puede crear una nueva presentación para la semana {$week} porque ya existe una con estado '{$existingPresentation->estado}'.");
            }

            // Log del intento de importación
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'FILE_UPLOAD_ATTEMPT',
                'description' => "Intento de importación de archivo Excel para la semana: {$week}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'week' => $week,
                    'filename' => $file->getClientOriginalName(),
                    'filesize' => $file->getSize(),
                ]),
            ]);

            // Aquí iría la lógica de procesamiento del archivo
            // Por ahora solo simulamos el procesamiento

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'FILE_UPLOAD_SUCCESS',
                'description' => "Archivo Excel importado exitosamente para la semana: {$week}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'week' => $week,
                    'filename' => $file->getClientOriginalName(),
                ]),
            ]);

            return redirect()->back()->with('success', 'Archivo importado exitosamente.');

        } catch (\Exception $e) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'FILE_UPLOAD_ERROR',
                'description' => "Error al importar archivo Excel para la semana: {$week}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'week' => $week,
                    'error' => $e->getMessage(),
                ]),
            ]);

            return redirect()->back()->with('error', 'Error al importar el archivo: ' . $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'VIEW_LIST',
            'description' => 'Usuario accedió al listado de presentaciones semanales',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $query = \App\Domain\Models\Presentation::where('tipo_entrega', 'Semanal')->with('user');

        // Filtros básicos (por estado, semana, usuario)
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('cronograma')) {
            $query->where('cronograma', 'like', '%' . $request->cronograma . '%');
        }
        if ($request->filled('usuario')) {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->usuario . '%');
            });
        }

        $presentations = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('weekly-presentations.index', compact('presentations'));
    }

    public function saveDraft(Request $request)
    {
        try {
            $request->validate([
                'week' => 'required|string',
                'operations' => 'required|string',
                'original_filename' => 'nullable|string',
                'original_file_path' => 'nullable|string',
            ]);

            $user = auth()->user();
            $week = $request->input('week');
            $operations = json_decode($request->input('operations'), true);
            $originalFilename = $request->input('original_filename');
            $originalFilePath = $request->input('original_file_path');

            $codigoCompania = env('SSN_CIA', '1');

            // Buscar presentación existente para el usuario y semana
            $existingPresentation = \App\Domain\Models\Presentation::where('user_id', $user->id)
                ->where('cronograma', $week)
                ->where('tipo_entrega', 'Semanal')
                ->orderBy('version', 'desc')
                ->first();

            if ($existingPresentation) {
                // Si existe con estado A_RECTIFICAR, crear una nueva versión
                if ($existingPresentation->estado === 'A_RECTIFICAR') {
                    $newVersion = \App\Domain\Models\Presentation::getNextVersion($codigoCompania, $week, 'Semanal');
                    
                    $presentation = \App\Domain\Models\Presentation::create([
                        'user_id' => $user->id,
                        'codigo_compania' => $codigoCompania,
                        'cronograma' => $week,
                        'tipo_entrega' => 'Semanal',
                        'version' => $newVersion,
                        'estado' => 'CARGADO',
                        'original_filename' => $originalFilename,
                        'original_file_path' => $originalFilePath,
                        'notes' => "Rectificación de la presentación versión {$existingPresentation->version}",
                    ]);

                    ActivityLog::create([
                        'user_id' => auth()->id(),
                        'action' => 'NEW_PRESENTATION_VERSION_CREATED',
                        'description' => "Nueva versión de presentación semanal creada para semana {$week} (v{$newVersion}) - rectificación de v{$existingPresentation->version}",
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'metadata' => json_encode([
                            'week' => $week,
                            'new_version' => $newVersion,
                            'previous_presentation_id' => $existingPresentation->id,
                            'previous_version' => $existingPresentation->version,
                            'previous_status' => $existingPresentation->estado,
                        ]),
                    ]);
                } 
                // Si existe como borrador (CARGADO), actualizar la existente
                elseif ($existingPresentation->estado === 'CARGADO') {
                    $presentation = $existingPresentation;
                    
                    // Borrar operaciones anteriores
                    $presentation->weeklyOperations()->delete();
                    $presentation->codigo_compania = $codigoCompania;
                    
                    // Actualizar información del archivo original si se proporciona
                    if ($originalFilename) {
                        $presentation->original_filename = $originalFilename;
                    }
                    if ($originalFilePath) {
                        $presentation->original_file_path = $originalFilePath;
                    }
                    
                    $presentation->save();

                    ActivityLog::create([
                        'user_id' => auth()->id(),
                        'action' => 'DRAFT_UPDATED',
                        'description' => "Borrador de presentación semanal actualizado para la semana {$week} (v{$presentation->version})",
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'metadata' => json_encode([
                            'presentation_id' => $presentation->id,
                            'version' => $presentation->version,
                            'week' => $week,
                            'total_operations' => count($operations),
                        ]),
                    ]);
                } 
                // Si existe con otro estado bloqueante, no permitir
                else {
                    return response()->json([
                        'success' => false, 
                        'message' => "Ya existe una presentación para la semana {$week} en estado '{$existingPresentation->estado}' (v{$existingPresentation->version}). No se puede sobrescribir."
                    ], 400);
                }
            } else {
                // Crear nueva presentación si no existe
                $presentation = \App\Domain\Models\Presentation::create([
                    'user_id' => $user->id,
                    'codigo_compania' => $codigoCompania,
                    'cronograma' => $week,
                    'tipo_entrega' => 'Semanal',
                    'version' => 1, // Primera versión
                    'estado' => 'CARGADO',
                    'original_filename' => $originalFilename,
                    'original_file_path' => $originalFilePath,
                ]);

                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'NEW_PRESENTATION_CREATED',
                    'description' => "Nueva presentación semanal creada para la semana {$week} (v1)",
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'metadata' => json_encode([
                        'presentation_id' => $presentation->id,
                        'version' => $presentation->version,
                        'week' => $week,
                        'total_operations' => count($operations),
                    ]),
                ]);
            }

            // Guardar operaciones
            foreach ($operations as $op) {
                $presentation->weeklyOperations()->create([
                    'tipo_operacion' => $op['tipo_operacion'],
                    'tipo_especie' => $op['tipo_especie'],
                    'codigo_especie' => $op['codigo_especie'],
                    'cant_especies' => $op['cant_especies'],
                    'codigo_afectacion' => $op['codigo_afectacion'],
                    'tipo_valuacion' => $op['tipo_valuacion'],
                    'fecha_movimiento' => $op['fecha_movimiento'],
                    'fecha_liquidacion' => $op['fecha_liquidacion'],
                    'precio_compra' => $op['precio_compra'],
                ]);
            }

            $presentation->estado = 'CARGADO';
            $presentation->save();

            // Redirigir a la vista de detalle
            return response()->json([
                'success' => true,
                'redirect_url' => route('weekly-presentations.show', $presentation->id)
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al guardar borrador semanal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $presentation = \App\Domain\Models\Presentation::with(['weeklyOperations', 'user'])->findOrFail($id);
        if ($presentation->tipo_entrega !== 'Semanal') {
            return redirect()->route('dashboard')->with('error', 'La presentación no es semanal.');
        }
        return view('weekly-presentations.show', compact('presentation'));
    }

    public function confirm($id)
    {
        $presentation = \App\Domain\Models\Presentation::with('weeklyOperations')->findOrFail($id);
        
        if ($presentation->estado !== 'CARGADO') {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'CONFIRMATION_REJECTED',
                'description' => "Intento de confirmar presentación {$id} en estado incorrecto: {$presentation->estado}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'presentation_id' => $id,
                    'current_status' => $presentation->estado,
                    'required_status' => 'CARGADO',
                ]),
            ]);
            
            return back()->with('error', 'Solo se puede confirmar una presentación en estado CARGADO.');
        }

        // Log del inicio de la confirmación
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'CONFIRMATION_STARTED',
            'description' => "Iniciando confirmación de presentación semanal {$id}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => json_encode([
                'presentation_id' => $id,
                'week' => $presentation->cronograma,
                'total_operations' => $presentation->weeklyOperations->count(),
            ]),
        ]);

        try {
            // Generar el JSON que se enviará a SSN
            $ssnJson = $presentation->getSsnJson();
            
            // Crear directorio para archivos JSON si no existe
            $jsonDirectory = storage_path('app/presentations/json');
            if (!file_exists($jsonDirectory)) {
                mkdir($jsonDirectory, 0755, true);
            }
            
            // Generar nombre único para el archivo JSON
            $jsonFilename = 'presentation_' . $presentation->id . '_' . $presentation->cronograma . '_' . now()->format('Y-m-d_H-i-s') . '.json';
            $jsonFilePath = $jsonDirectory . '/' . $jsonFilename;
            
            // Guardar el JSON en archivo
            file_put_contents($jsonFilePath, json_encode($ssnJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            // Enviar a SSN (simulado o real)
            $ssnService = app(\App\Services\SSNService::class);
            $response = $ssnService->sendWeeklyPresentation($presentation);
            
            // Verificar si la respuesta fue exitosa
            if (!$response['success']) {
                throw new \Exception('Error en la respuesta de SSN: ' . ($response['error'] ?? 'Respuesta no exitosa'));
            }

            // Actualizar la presentación con los datos de respuesta
            $presentation->estado = 'PRESENTADO';
            $presentation->ssn_response_id = $response['data']['id'] ?? null;
            $presentation->ssn_response_data = $response['data'] ?? $response;
            $presentation->presented_at = now();
            $presentation->confirmed_at = now();
            
            // Guardar información del archivo JSON generado
            $presentation->json_file_path = 'presentations/json/' . $jsonFilename;
            
            $presentation->save();

            // Log de confirmación exitosa
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'CONFIRMATION_SUCCESS',
                'description' => "Presentación semanal {$id} confirmada y enviada exitosamente a SSN",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'presentation_id' => $id,
                    'week' => $presentation->cronograma,
                    'ssn_response_id' => $presentation->ssn_response_id,
                    'ssn_status' => $response['data']['status'] ?? 'unknown',
                    'total_operations' => $presentation->weeklyOperations->count(),
                    'json_file_path' => $presentation->json_file_path,
                ]),
            ]);

            return redirect()->route('weekly-presentations.show', $presentation->id)->with('success', 'Presentación enviada a SSN correctamente.');

        } catch (\Exception $e) {
            // Log del error
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'CONFIRMATION_ERROR',
                'description' => "Error al confirmar presentación semanal {$id}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'presentation_id' => $id,
                    'week' => $presentation->cronograma,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]),
            ]);

            return back()->with('error', 'Error al enviar a SSN: ' . $e->getMessage());
        }
    }

    public function rectify($id)
    {
        $presentation = \App\Domain\Models\Presentation::findOrFail($id);
        
        if ($presentation->estado !== 'PRESENTADO') {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'RECTIFICATION_REJECTED',
                'description' => "Intento de solicitar rectificación de presentación {$id} en estado incorrecto: {$presentation->estado}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'presentation_id' => $id,
                    'current_status' => $presentation->estado,
                    'required_status' => 'PRESENTADO',
                ]),
            ]);
            
            return back()->with('error', 'Solo se puede solicitar rectificación si el estado es PRESENTADO.');
        }

        // Log del inicio de la solicitud de rectificación
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'RECTIFICATION_STARTED',
            'description' => "Iniciando solicitud de rectificación para presentación semanal {$id}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => json_encode([
                'presentation_id' => $id,
                'week' => $presentation->cronograma,
                'ssn_response_id' => $presentation->ssn_response_id,
            ]),
        ]);

        try {
            // Llamar a la API de la SSN para solicitar rectificación
            $ssnService = app(\App\Services\SSNService::class);
            $response = $ssnService->requestRectification($presentation);
            
            // Verificar si la respuesta fue exitosa
            if (!$response['success']) {
                throw new \Exception('Error en la respuesta de SSN: ' . ($response['error'] ?? 'Respuesta no exitosa'));
            }

            // Actualizar la presentación
            $presentation->estado = 'RECTIFICACION_PENDIENTE';
            $presentation->rectification_requested_at = now();
            
            // Guardar la respuesta de rectificación en los datos de SSN
            $ssnData = $presentation->ssn_response_data ?? [];
            $ssnData['rectification_request'] = $response['data'] ?? $response;
            $presentation->ssn_response_data = $ssnData;
            
            $presentation->save();

            // Log de rectificación exitosa
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'RECTIFICATION_SUCCESS',
                'description' => "Rectificación solicitada exitosamente para presentación semanal {$id}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'presentation_id' => $id,
                    'week' => $presentation->cronograma,
                    'ssn_response_id' => $presentation->ssn_response_id,
                    'rectification_id' => $response['data']['id'] ?? null,
                    'numero_solicitud' => $response['data']['numeroSolicitud'] ?? null,
                ]),
            ]);

            return redirect()->route('weekly-presentations.show', $presentation->id)
                ->with('success', 'Rectificación solicitada correctamente. Número de solicitud: ' . ($response['data']['numeroSolicitud'] ?? 'N/A'));

        } catch (\Exception $e) {
            // Log del error
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'RECTIFICATION_ERROR',
                'description' => "Error al solicitar rectificación para presentación semanal {$id}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'presentation_id' => $id,
                    'week' => $presentation->cronograma,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]),
            ]);

            return back()->with('error', 'Error al solicitar rectificación: ' . $e->getMessage());
        }
    }

    /**
     * Descargar el archivo JSON de una presentación
     */
    public function downloadJson($id)
    {
        $presentation = \App\Domain\Models\Presentation::findOrFail($id);
        
        if (!$presentation->json_file_path) {
            return back()->with('error', 'No hay archivo JSON disponible para esta presentación.');
        }

        $filePath = storage_path('app/' . $presentation->json_file_path);
        
        if (!file_exists($filePath)) {
            return back()->with('error', 'El archivo JSON no se encuentra en el servidor.');
        }

        // Log de descarga
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'JSON_DOWNLOADED',
            'description' => "Descarga de archivo JSON para presentación semanal {$id}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => json_encode([
                'presentation_id' => $id,
                'week' => $presentation->cronograma,
                'json_file_path' => $presentation->json_file_path,
                'file_size' => filesize($filePath),
            ]),
        ]);

        $filename = 'presentacion_' . $presentation->cronograma . '_' . $presentation->tipo_entrega . '.json';
        
        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Descargar el archivo Excel original de una presentación
     */
    public function downloadExcel($id)
    {
        $presentation = \App\Domain\Models\Presentation::findOrFail($id);
        
        if (!$presentation->original_file_path) {
            return back()->with('error', 'No hay archivo Excel original disponible para esta presentación.');
        }

        $filePath = storage_path('app/' . $presentation->original_file_path);
        
        if (!file_exists($filePath)) {
            return back()->with('error', 'El archivo Excel original no se encuentra en el servidor.');
        }

        // Log de descarga
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'EXCEL_DOWNLOADED',
            'description' => "Descarga de archivo Excel original para presentación semanal {$id}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => json_encode([
                'presentation_id' => $id,
                'week' => $presentation->cronograma,
                'original_file_path' => $presentation->original_file_path,
                'original_filename' => $presentation->original_filename,
                'file_size' => filesize($filePath),
            ]),
        ]);

        // Usar el nombre original del archivo si está disponible
        $filename = $presentation->original_filename ?: 'presentacion_' . $presentation->cronograma . '_original.xlsx';
        
        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
} 