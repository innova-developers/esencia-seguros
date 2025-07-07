<?php

namespace App\Infrastructure\Http\Controllers;

use App\Domain\Models\Presentation;
use App\Domain\Models\User;
use App\Domain\Models\ActivityLog;
use App\Services\SSNService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Domain\Models\MonthlyStock;
use App\Services\ExcelProcessorService;
use Illuminate\Routing\Controller;
use Carbon\Carbon;

class MonthlyPresentationController extends Controller
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

        // Si viene un parámetro month, validar que sea válido para presentar
        $selectedMonth = $request->query('month');
        if ($selectedMonth) {
            $validationResult = $this->validateMonthForPresentation($selectedMonth);
            if (!$validationResult['valid']) {
                return redirect()->route('monthly-presentations.index')
                    ->with('error', $validationResult['message']);
            }
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'VIEW_CREATE_FORM',
            'description' => 'Usuario accedió al formulario de nueva presentación mensual',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Generar meses disponibles según las reglas de negocio
        $availableMonths = $this->getAvailableMonths();

        return view('monthly-presentations.create', compact('availableMonths', 'selectedMonth'));
    }

    /**
     * Validar si un mes es válido para crear una presentación
     */
    private function validateMonthForPresentation($month)
    {
        // Verificar si existe una presentación con estados bloqueantes
        $existingPresentation = Presentation::where('cronograma', $month)
            ->where('tipo_entrega', 'Mensual')
            ->whereIn('estado', ['PRESENTADO', 'RECTIFICACION_PENDIENTE'])
            ->first();

        if ($existingPresentation) {
            return [
                'valid' => false,
                'message' => "No se puede crear una presentación para el mes {$month} porque ya existe una con estado '{$existingPresentation->estado}'."
            ];
        }

        return ['valid' => true, 'message' => ''];
    }

    private function getAvailableMonths()
    {
        $currentDate = Carbon::now();
        $oneMonthAgo = $currentDate->copy()->subMonth();
        $twoMonthsAgo = $currentDate->copy()->subMonths(2);
        
        $availableMonths = [];
        
        // Generar meses desde 1 mes atrás hasta el anteúltimo mes
        $startDate = $oneMonthAgo->startOfMonth();
        $endDate = $twoMonthsAgo->endOfMonth();
        
        $currentMonth = $startDate->copy();
        
        while ($currentMonth >= $endDate) {
            $monthKey = $currentMonth->format('Y-m');
            // Mostrar el mes en español
            $displayText = $currentMonth->locale('es')->translatedFormat('F Y');
            
            // Verificar si ya existe una presentación con estados bloqueantes
            $existingPresentation = Presentation::where('cronograma', $monthKey)
                ->where('tipo_entrega', 'Mensual')
                ->whereIn('estado', ['PRESENTADO', 'RECTIFICACION_PENDIENTE'])
                ->first();
            
            // Solo incluir si no existe presentación con estados bloqueantes
            if (!$existingPresentation) {
                // Marcar si ya existe una presentación en otros estados
                if (Presentation::where('cronograma', $monthKey)->where('tipo_entrega', 'Mensual')->exists()) {
                    $existing = Presentation::where('cronograma', $monthKey)->where('tipo_entrega', 'Mensual')->first();
                    $displayText .= " (Estado actual: {$existing->estado})";
                }
                
                $availableMonths[] = [
                    'value' => $monthKey,
                    'text' => $displayText,
                    'has_existing' => Presentation::where('cronograma', $monthKey)->where('tipo_entrega', 'Mensual')->exists()
                ];
            }
            
            $currentMonth->subMonth();
        }
        
        return $availableMonths;
    }

    public function processExcel(Request $request)
    {
        $request->validate([
            'month' => 'required|string',
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
        ]);

        $month = $request->input('month');
        $file = $request->file('excel_file');

        // Obtener información del archivo ANTES de moverlo
        $originalFilename = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();
        $extension = $file->getClientOriginalExtension();

        try {
            // Validar que el mes no tenga presentación con estados bloqueantes
            $existingPresentation = Presentation::where('cronograma', $month)
                ->where('tipo_entrega', 'Mensual')
                ->whereIn('estado', ['PRESENTADO', 'RECTIFICACION_PENDIENTE'])
                ->first();

            if ($existingPresentation) {
                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'VALIDATION_ERROR',
                    'description' => "Intento de procesar Excel para mes {$month} que ya tiene estado bloqueante: {$existingPresentation->estado}",
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'metadata' => json_encode([
                        'month' => $month,
                        'existing_status' => $existingPresentation->estado,
                    ]),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => "No se puede procesar el archivo para el mes {$month} porque ya existe una presentación con estado '{$existingPresentation->estado}'."
                ], 400);
            }

            // Log del inicio del procesamiento
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'EXCEL_PROCESS_START',
                'description' => "Iniciando procesamiento de Excel para el mes: {$month}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'month' => $month,
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
            $excelFilename = 'presentation_' . $month . '_' . now()->format('Y-m-d_H-i-s') . '.' . $extension;
            $excelFilePath = $excelDirectory . '/' . $excelFilename;
            
            // Guardar el archivo Excel
            $file->move($excelDirectory, $excelFilename);
            
            // Usar el archivo guardado para procesamiento
            if (!file_exists($excelFilePath)) {
                throw new \Exception("El archivo guardado no existe: {$excelFilePath}");
            }

            // Procesar el Excel desde el archivo guardado
            $result = $this->excelProcessor->processMonthlyExcel($excelFilePath, $month);

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
                'description' => "Excel procesado exitosamente para el mes: {$month}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'month' => $month,
                    'total_stocks' => $result['total_stocks'],
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
                'description' => "Error al procesar Excel para el mes: {$month}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'month' => $month,
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
            'stocks' => 'required|string',
            'month' => 'required|string',
        ]);

        try {
            $stocks = json_decode($request->input('stocks'), true);
            $month = $request->input('month');

            // Generar el JSON completo usando el servicio
            $codigoCompania = env('SSN_CIA', '1');
            
            $ssnJson = [
                'codigoCompania' => $codigoCompania,
                'cronograma' => $month,
                'tipoEntrega' => 'Mensual',
                'stocks' => []
            ];

            // Convertir los stocks al formato correcto
            foreach ($stocks as $stock) {
                $ssnStock = [
                    'tipo' => $stock['tipo'], // Siempre 'S' para mensuales
                    'tipoEspecie' => $stock['tipo_especie'],
                    'codigoEspecie' => $stock['codigo_especie'],
                    'cantidadDevengadoEspecies' => (float) $stock['cantidad_devengado_especies'],
                    'cantidadPercibidoEspecies' => (float) $stock['cantidad_percibido_especies'],
                    'codigoAfectacion' => $stock['codigo_afectacion'],
                    'tipoValuacion' => $stock['tipo_valuacion'],
                    'conCotizacion' => $stock['con_cotizacion'],
                    'libreDisponibilidad' => $stock['libre_disponibilidad'],
                    'emisorGrupoEconomico' => $stock['emisor_grupo_economico'],
                    'emisorArtRet' => $stock['emisor_art_ret'],
                    'previsionDesvalorizacion' => $stock['prevision_desvalorizacion'],
                    'valorContable' => (float) $stock['valor_contable'],
                    'fechaPaseVt' => $stock['fecha_pase_vt'],
                    'precioPaseVt' => $stock['precio_pase_vt'],
                    'enCustodia' => $stock['en_custodia'],
                    'financiera' => $stock['financiera'],
                    'valorFinanciero' => $stock['valor_financiero'],
                ];
                
                $ssnJson['stocks'][] = $ssnStock;
            }

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'JSON_GENERATED',
                'description' => "JSON generado para el mes: {$month}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'month' => $month,
                    'total_stocks' => count($stocks),
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
                'description' => "Error al generar JSON para el mes: {$month}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'month' => $month,
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
            'month' => 'required|string',
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
        ]);

        try {
            $month = $request->input('month');
            $file = $request->file('excel_file');

            // Validar que el mes no tenga presentación con estados bloqueantes
            $existingPresentation = Presentation::where('cronograma', $month)
                ->where('tipo_entrega', 'Mensual')
                ->whereIn('estado', ['PRESENTADO', 'RECTIFICACION_PENDIENTE'])
                ->first();

            if ($existingPresentation) {
                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'VALIDATION_ERROR',
                    'description' => "Intento de crear presentación para mes {$month} que ya tiene estado bloqueante: {$existingPresentation->estado}",
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'metadata' => json_encode([
                        'month' => $month,
                        'existing_status' => $existingPresentation->estado,
                    ]),
                ]);

                return redirect()->back()->with('error', "No se puede crear una nueva presentación para el mes {$month} porque ya existe una con estado '{$existingPresentation->estado}'.");
            }

            // Log del intento de importación
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'IMPORT_ATTEMPT',
                'description' => "Intento de importar Excel para el mes: {$month}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'month' => $month,
                    'filename' => $file->getClientOriginalName(),
                    'filesize' => $file->getSize(),
                ]),
            ]);

            // Procesar el archivo Excel
            $result = $this->excelProcessor->processMonthlyExcel($file, $month);

            // Crear la presentación
            $presentation = Presentation::create([
                'user_id' => auth()->id(),
                'codigo_compania' => env('SSN_CIA', '1'),
                'cronograma' => $month,
                'tipo_entrega' => 'Mensual',
                'estado' => 'CARGADO',
                'original_filename' => $file->getClientOriginalName(),
                'original_file_path' => $result['excel_file_info']['saved_path'] ?? null,
            ]);

            // Guardar los stocks mensuales
            foreach ($result['stocks'] as $stock) {
                $presentation->monthlyStocks()->create($stock);
            }

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'IMPORT_SUCCESS',
                'description' => "Presentación mensual importada exitosamente para el mes: {$month}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'presentation_id' => $presentation->id,
                    'month' => $month,
                    'total_stocks' => count($result['stocks']),
                    'excel_file_info' => $result['excel_file_info'],
                ]),
            ]);

            return redirect()->route('monthly-presentations.show', $presentation->id)
                ->with('success', 'Presentación mensual importada exitosamente.');

        } catch (\Exception $e) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'IMPORT_ERROR',
                'description' => "Error al importar presentación mensual para el mes: {$month}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'month' => $month,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]),
            ]);

            return back()->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'VIEW_LIST',
            'description' => 'Usuario accedió al listado de presentaciones mensuales',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $query = \App\Domain\Models\Presentation::where('tipo_entrega', 'Mensual')->with('user');

        // Filtros básicos (por estado, mes, usuario)
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

        return view('monthly-presentations.index', compact('presentations'));
    }

    public function saveDraft(Request $request)
    {
        try {
            $request->validate([
                'month' => 'required|string',
                'stocks' => 'required|string',
                'original_filename' => 'nullable|string',
                'original_file_path' => 'nullable|string',
            ]);

            $user = auth()->user();
            $month = $request->input('month');
            $stocks = json_decode($request->input('stocks'), true);
            $originalFilename = $request->input('original_filename');
            $originalFilePath = $request->input('original_file_path');

            $codigoCompania = env('SSN_CIA', '1');

            // Buscar presentación existente para el usuario y mes
            $existingPresentation = \App\Domain\Models\Presentation::where('user_id', $user->id)
                ->where('cronograma', $month)
                ->where('tipo_entrega', 'Mensual')
                ->first();

            if ($existingPresentation) {
                // Si existe con estado A_RECTIFICAR, crear una nueva presentación
                if ($existingPresentation->estado === 'A_RECTIFICAR') {
                    $presentation = \App\Domain\Models\Presentation::create([
                        'user_id' => $user->id,
                        'codigo_compania' => $codigoCompania,
                        'cronograma' => $month,
                        'tipo_entrega' => 'Mensual',
                        'estado' => 'CARGADO',
                        'original_filename' => $originalFilename,
                        'original_file_path' => $originalFilePath,
                    ]);

                    ActivityLog::create([
                        'user_id' => auth()->id(),
                        'action' => 'NEW_PRESENTATION_CREATED',
                        'description' => "Nueva presentación mensual creada para mes {$month} (existía A_RECTIFICAR)",
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'metadata' => json_encode([
                            'month' => $month,
                            'previous_presentation_id' => $existingPresentation->id,
                            'previous_status' => $existingPresentation->estado,
                        ]),
                    ]);
                } 
                // Si existe como borrador (CARGADO), actualizar la existente
                elseif ($existingPresentation->estado === 'CARGADO') {
                    $presentation = $existingPresentation;
                    
                    // Borrar stocks anteriores
                    $presentation->monthlyStocks()->delete();
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
                        'description' => "Borrador de presentación mensual actualizado para el mes {$month}",
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'metadata' => json_encode([
                            'presentation_id' => $presentation->id,
                            'month' => $month,
                            'total_stocks' => count($stocks),
                        ]),
                    ]);
                } 
                // Si existe con otro estado bloqueante, no permitir
                else {
                    return response()->json([
                        'success' => false, 
                        'message' => "Ya existe una presentación para el mes {$month} en estado '{$existingPresentation->estado}'. No se puede sobrescribir."
                    ], 400);
                }
            } else {
                // Crear nueva presentación si no existe
                $presentation = \App\Domain\Models\Presentation::create([
                    'user_id' => $user->id,
                    'codigo_compania' => $codigoCompania,
                    'cronograma' => $month,
                    'tipo_entrega' => 'Mensual',
                    'estado' => 'CARGADO',
                    'original_filename' => $originalFilename,
                    'original_file_path' => $originalFilePath,
                ]);

                ActivityLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'NEW_PRESENTATION_CREATED',
                    'description' => "Nueva presentación mensual creada para el mes {$month}",
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'metadata' => json_encode([
                        'presentation_id' => $presentation->id,
                        'month' => $month,
                        'total_stocks' => count($stocks),
                    ]),
                ]);
            }

            // Guardar stocks
            foreach ($stocks as $stock) {
                $presentation->monthlyStocks()->create($stock);
            }

            $presentation->estado = 'CARGADO';
            $presentation->save();

            // Redirigir a la vista de detalle
            return response()->json([
                'success' => true,
                'redirect_url' => route('monthly-presentations.show', $presentation->id)
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al guardar borrador mensual', [
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
        $presentation = \App\Domain\Models\Presentation::with(['monthlyStocks', 'user'])->findOrFail($id);
        if ($presentation->tipo_entrega !== 'Mensual') {
            return redirect()->route('dashboard')->with('error', 'La presentación no es mensual.');
        }
        return view('monthly-presentations.show', compact('presentation'));
    }

    public function confirm($id)
    {
        $presentation = \App\Domain\Models\Presentation::with(['monthlyStocks', 'user'])->findOrFail($id);
        
        if ($presentation->tipo_entrega !== 'Mensual') {
            return redirect()->route('dashboard')->with('error', 'La presentación no es mensual.');
        }

        if ($presentation->estado !== 'CARGADO') {
            return back()->with('error', 'Solo se pueden confirmar presentaciones en estado CARGADO.');
        }

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
            $response = $ssnService->sendMonthlyPresentation($presentation);
            
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
                'description' => "Presentación mensual {$id} confirmada y enviada exitosamente a SSN",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'presentation_id' => $id,
                    'month' => $presentation->cronograma,
                    'ssn_response_id' => $presentation->ssn_response_id,
                    'ssn_status' => $response['data']['status'] ?? 'unknown',
                    'total_stocks' => $presentation->monthlyStocks->count(),
                    'json_file_path' => $presentation->json_file_path,
                ]),
            ]);

            return redirect()->route('monthly-presentations.show', $presentation->id)->with('success', 'Presentación enviada a SSN correctamente.');

        } catch (\Exception $e) {
            // Log del error
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'CONFIRMATION_ERROR',
                'description' => "Error al confirmar presentación mensual {$id}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'presentation_id' => $id,
                    'month' => $presentation->cronograma,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]),
            ]);

            return back()->with('error', 'Error al enviar a SSN: ' . $e->getMessage());
        }
    }

    public function rectify($id)
    {
        $presentation = \App\Domain\Models\Presentation::with(['monthlyStocks', 'user'])->findOrFail($id);
        
        if ($presentation->tipo_entrega !== 'Mensual') {
            return redirect()->route('dashboard')->with('error', 'La presentación no es mensual.');
        }

        if ($presentation->estado !== 'PRESENTADO') {
            return back()->with('error', 'Solo se pueden rectificar presentaciones en estado PRESENTADO.');
        }

        try {
            // Generar el JSON para la rectificación
            $ssnJson = $presentation->getSsnJson();
            
            // Enviar solicitud de rectificación a SSN
            $ssnService = app(\App\Services\SSNService::class);
            $response = $ssnService->requestRectification($presentation);
            
            // Verificar si la respuesta fue exitosa
            if (!$response['success']) {
                throw new \Exception('Error en la respuesta de SSN: ' . ($response['error'] ?? 'Respuesta no exitosa'));
            }

            // Actualizar la presentación
            $presentation->estado = 'RECTIFICACION_PENDIENTE';
            $presentation->ssn_response_id = $response['data']['numeroSolicitud'] ?? null;
            $presentation->ssn_response_data = $response['data'] ?? $response;
            $presentation->rectification_requested_at = now();
            $presentation->save();

            // Log de rectificación exitosa
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'RECTIFICATION_SUCCESS',
                'description' => "Rectificación solicitada exitosamente para presentación mensual {$id}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'presentation_id' => $id,
                    'month' => $presentation->cronograma,
                    'ssn_rectification_id' => $presentation->ssn_response_id,
                    'ssn_status' => $response['data']['status'] ?? 'unknown',
                    'total_stocks' => $presentation->monthlyStocks->count(),
                ]),
            ]);

            return redirect()->route('monthly-presentations.show', $presentation->id)
                ->with('success', 'Rectificación solicitada correctamente. Número de solicitud: ' . ($response['data']['numeroSolicitud'] ?? 'N/A'));

        } catch (\Exception $e) {
            // Log del error
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'RECTIFICATION_ERROR',
                'description' => "Error al solicitar rectificación para presentación mensual {$id}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'presentation_id' => $id,
                    'month' => $presentation->cronograma,
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
            'description' => "Descarga de archivo JSON para presentación mensual {$id}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => json_encode([
                'presentation_id' => $id,
                'month' => $presentation->cronograma,
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
            return back()->with('error', 'No hay archivo Excel disponible para esta presentación.');
        }

        $filePath = storage_path('app/' . $presentation->original_file_path);
        
        if (!file_exists($filePath)) {
            return back()->with('error', 'El archivo Excel no se encuentra en el servidor.');
        }

        // Log de descarga
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'EXCEL_DOWNLOADED',
            'description' => "Descarga de archivo Excel para presentación mensual {$id}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => json_encode([
                'presentation_id' => $id,
                'month' => $presentation->cronograma,
                'excel_file_path' => $presentation->original_file_path,
                'file_size' => filesize($filePath),
            ]),
        ]);

        $filename = 'presentacion_' . $presentation->cronograma . '_' . $presentation->tipo_entrega . '.' . pathinfo($presentation->original_filename, PATHINFO_EXTENSION);
        
        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Enviar presentación a SSN
     */
    public function sendSsn($id)
    {
        $presentation = \App\Domain\Models\Presentation::with(['monthlyStocks', 'user'])->findOrFail($id);
        
        if ($presentation->tipo_entrega !== 'Mensual') {
            return redirect()->route('dashboard')->with('error', 'La presentación no es mensual.');
        }

        if ($presentation->estado !== 'CARGADO') {
            return back()->with('error', 'Solo se pueden enviar presentaciones en estado CARGADO.');
        }

        try {
            // Generar el JSON para SSN
            $ssnJson = $presentation->getSsnJson();
            
            // Enviar a SSN
            $ssnService = app(\App\Services\SSNService::class);
            $response = $ssnService->sendMonthlyPresentation($presentation);
            
            // Verificar si la respuesta fue exitosa
            if (!$response['success']) {
                throw new \Exception('Error en la respuesta de SSN: ' . ($response['error'] ?? 'Respuesta no exitosa'));
            }

            // Actualizar la presentación
            $presentation->estado = 'PRESENTADO';
            $presentation->ssn_response_id = $response['data']['id'] ?? null;
            $presentation->ssn_response_data = $response['data'] ?? $response;
            $presentation->presented_at = now();
            $presentation->save();

            // Log de envío exitoso
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'SEND_SSN_SUCCESS',
                'description' => "Presentación mensual {$id} enviada exitosamente a SSN",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'presentation_id' => $id,
                    'month' => $presentation->cronograma,
                    'ssn_response_id' => $presentation->ssn_response_id,
                    'ssn_status' => $response['data']['status'] ?? 'unknown',
                    'total_stocks' => $presentation->monthlyStocks->count(),
                ]),
            ]);

            return redirect()->route('monthly-presentations.show', $presentation->id)
                ->with('success', 'Presentación enviada a SSN correctamente.');

        } catch (\Exception $e) {
            // Log del error
            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'SEND_SSN_ERROR',
                'description' => "Error al enviar presentación mensual {$id} a SSN",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => json_encode([
                    'presentation_id' => $id,
                    'month' => $presentation->cronograma,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]),
            ]);

            return back()->with('error', 'Error al enviar a SSN: ' . $e->getMessage());
        }
    }
} 