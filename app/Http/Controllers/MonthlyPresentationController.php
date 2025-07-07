<?php

namespace App\Http\Controllers;

use App\Domain\Models\Presentation;
use App\Domain\Models\MonthlyStock;
use App\Domain\Models\SsnSpecie;
use App\Domain\Models\SsnAffectation;
use App\Domain\Models\SsnBank;
use App\Domain\Models\SsnDepositType;
use App\Domain\Models\SsnSgrCode;
use App\Domain\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MonthlyPresentationController extends \App\Infrastructure\Http\Controllers\Controller
{
    /**
     * Mostrar formulario para crear nueva presentación mensual
     */
    public function create()
    {
        // Verificar si el usuario está autenticado
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Obtener catálogos para el formulario
        $species = SsnSpecie::activo()->get();
        $affectations = SsnAffectation::activo()->get();
        $banks = SsnBank::activo()->get();
        $depositTypes = SsnDepositType::activo()->get();
        $sgrCodes = SsnSgrCode::activo()->get();

        // Generar meses disponibles según las reglas de negocio
        $availableMonths = $this->getAvailableMonths();

        // Log de actividad
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'VIEW_MONTHLY_PRESENTATION_FORM',
            'description' => 'Usuario accedió al formulario de nueva presentación mensual',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return view('monthly-presentations.create', compact(
            'species',
            'affectations',
            'banks',
            'depositTypes',
            'sgrCodes',
            'availableMonths'
        ));
    }

    /**
     * Obtener meses disponibles según las reglas de negocio
     */
    private function getAvailableMonths()
    {
        $currentDate = \Carbon\Carbon::now();
        $oneMonthAgo = $currentDate->copy()->subMonth();
        $twoMonthsAgo = $currentDate->copy()->subMonths(2);
        
        $availableMonths = [];
        
        // Generar meses desde 1 mes atrás hasta el anteúltimo mes
        $startDate = $oneMonthAgo->copy()->startOfMonth();
        $endDate = $twoMonthsAgo->copy()->endOfMonth();
        
        $currentMonth = $startDate->copy();
        
        while ($currentMonth >= $endDate) {
            $monthKey = $currentMonth->format('Y-m');
            $displayText = $currentMonth->format('F Y');
            
            // Verificar si ya existe una presentación con estados bloqueantes
            $existingPresentation = Presentation::where('cronograma', $monthKey)
                ->where('tipo_entrega', 'Mensual')
                ->whereIn('estado', ['PRESENTADO', 'RECTIFICACION PENDIENTE'])
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

    /**
     * Almacenar nueva presentación mensual
     */
    public function store(Request $request)
    {
        // Verificar si el usuario está autenticado
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Validar datos del formulario
        $validator = Validator::make($request->all(), [
            'codigo_compania' => 'required|string|max:4',
            'cronograma' => 'required|string|max:7|regex:/^\d{4}-\d{2}$/',
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB máximo
        ], [
            'cronograma.regex' => 'El cronograma debe tener el formato YYYY-MM (ej: 2025-01)',
            'file.mimes' => 'El archivo debe ser un Excel (.xlsx o .xls)',
            'file.max' => 'El archivo no puede superar los 10MB',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Verificar si ya existe una presentación para este período con estados bloqueantes
            $existingPresentation = Presentation::where([
                'codigo_compania' => $request->codigo_compania,
                'cronograma' => $request->cronograma,
                'tipo_entrega' => 'Mensual'
            ])->whereIn('estado', ['PRESENTADO', 'RECTIFICACION PENDIENTE'])->first();

            if ($existingPresentation) {
                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'VALIDATION_ERROR_MONTHLY_PRESENTATION',
                    'description' => "Intento de crear presentación para período {$request->cronograma} que ya tiene estado bloqueante: {$existingPresentation->estado}",
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'metadata' => [
                        'cronograma' => $request->cronograma,
                        'existing_status' => $existingPresentation->estado,
                    ],
                ]);

                return back()->withErrors(['cronograma' => "No se puede crear una nueva presentación para el período {$request->cronograma} porque ya existe una con estado '{$existingPresentation->estado}'."])->withInput();
            }

            // Verificar si ya existe una presentación para este período (cualquier estado)
            $existingPresentation = Presentation::where([
                'codigo_compania' => $request->codigo_compania,
                'cronograma' => $request->cronograma,
                'tipo_entrega' => 'Mensual'
            ])->first();

            if ($existingPresentation) {
                return back()->withErrors(['cronograma' => 'Ya existe una presentación mensual para este período.'])->withInput();
            }

            // Guardar archivo
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('monthly-presentations', $filename, 'public');

            // Crear presentación
            $presentation = Presentation::create([
                'user_id' => Auth::id(),
                'codigo_compania' => $request->codigo_compania,
                'cronograma' => $request->cronograma,
                'tipo_entrega' => 'Mensual',
                'estado' => 'VACIO',
                'original_file_path' => $filePath,
                'original_filename' => $file->getClientOriginalName(),
            ]);

            // Log de actividad
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'CREATE_MONTHLY_PRESENTATION',
                'description' => "Creó nueva presentación mensual para período {$request->cronograma}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'presentation_id' => $presentation->id,
                    'cronograma' => $request->cronograma,
                    'filename' => $file->getClientOriginalName(),
                ],
            ]);

            return redirect()->route('monthly-presentations.show', $presentation)
                ->with('success', 'Presentación mensual creada exitosamente. Ahora puedes procesar el archivo Excel.');

        } catch (\Exception $e) {
            // Log de error
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'ERROR_CREATE_MONTHLY_PRESENTATION',
                'description' => 'Error al crear presentación mensual: ' . $e->getMessage(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
            ]);

            return back()->withErrors(['error' => 'Error al crear la presentación: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Mostrar presentación mensual
     */
    public function show(Presentation $presentation)
    {
        // Verificar si el usuario está autenticado
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Verificar que sea una presentación mensual
        if (!$presentation->isMensual()) {
            return redirect()->route('dashboard')->withErrors(['error' => 'La presentación no es mensual.']);
        }

        // Cargar relaciones
        $presentation->load(['monthlyStocks', 'user']);

        // Log de actividad
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'VIEW_MONTHLY_PRESENTATION',
            'description' => "Visualizó presentación mensual ID: {$presentation->id}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => [
                'presentation_id' => $presentation->id,
                'cronograma' => $presentation->cronograma,
            ],
        ]);

        return view('monthly-presentations.show', compact('presentation'));
    }

    /**
     * Listar presentaciones mensuales
     */
    public function index()
    {
        // Verificar si el usuario está autenticado
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $presentations = Presentation::mensual()
            ->with(['user', 'monthlyStocks'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Log de actividad
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'LIST_MONTHLY_PRESENTATIONS',
            'description' => 'Listó presentaciones mensuales',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return view('monthly-presentations.index', compact('presentations'));
    }

    /**
     * Procesar archivo Excel de presentación mensual
     */
    public function processExcel(Presentation $presentation)
    {
        // Verificar si el usuario está autenticado
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Verificar que sea una presentación mensual
        if (!$presentation->isMensual()) {
            return redirect()->route('dashboard')->withErrors(['error' => 'La presentación no es mensual.']);
        }

        try {
            // Aquí iría la lógica para procesar el Excel
            // Por ahora, creamos algunos stocks de ejemplo
            
            // Crear stocks de ejemplo
            $this->createSampleStocks($presentation);

            // Actualizar estado de la presentación
            $presentation->update([
                'estado' => 'CARGADO',
                'notes' => 'Archivo procesado exitosamente',
            ]);

            // Log de actividad
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'PROCESS_MONTHLY_EXCEL',
                'description' => "Procesó archivo Excel de presentación mensual ID: {$presentation->id}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'presentation_id' => $presentation->id,
                    'cronograma' => $presentation->cronograma,
                ],
            ]);

            return redirect()->route('monthly-presentations.show', $presentation)
                ->with('success', 'Archivo Excel procesado exitosamente. La presentación está lista para ser enviada a SSN.');

        } catch (\Exception $e) {
            // Log de error
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'ERROR_PROCESS_MONTHLY_EXCEL',
                'description' => 'Error al procesar Excel mensual: ' . $e->getMessage(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'presentation_id' => $presentation->id,
                    'error' => $e->getMessage(),
                ],
            ]);

            return back()->withErrors(['error' => 'Error al procesar el archivo: ' . $e->getMessage()]);
        }
    }

    /**
     * Enviar presentación a SSN
     */
    public function sendToSsn(Presentation $presentation)
    {
        // Verificar si el usuario está autenticado
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Verificar que sea una presentación mensual
        if (!$presentation->isMensual()) {
            return redirect()->route('dashboard')->withErrors(['error' => 'La presentación no es mensual.']);
        }

        // Verificar que esté cargada
        if (!$presentation->isCargado()) {
            return back()->withErrors(['error' => 'La presentación debe estar cargada antes de enviarla a SSN.']);
        }

        try {
            // Aquí iría la lógica para enviar a SSN
            // Por ahora, simulamos el envío
            
            // Simular respuesta de SSN
            $ssnResponse = [
                'status' => 'success',
                'message' => 'Presentación enviada exitosamente',
                'id' => 'SSN-' . time(),
                'timestamp' => now()->toISOString(),
            ];

            // Actualizar presentación
            $presentation->update([
                'estado' => 'PRESENTADO',
                'ssn_response_id' => $ssnResponse['id'],
                'ssn_response_data' => $ssnResponse,
                'presented_at' => now(),
                'notes' => 'Enviada a SSN exitosamente',
            ]);

            // Log de actividad
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'SEND_MONTHLY_TO_SSN',
                'description' => "Envió presentación mensual a SSN ID: {$presentation->id}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'presentation_id' => $presentation->id,
                    'ssn_response' => $ssnResponse,
                ],
            ]);

            return redirect()->route('monthly-presentations.show', $presentation)
                ->with('success', 'Presentación enviada a SSN exitosamente.');

        } catch (\Exception $e) {
            // Log de error
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'ERROR_SEND_MONTHLY_TO_SSN',
                'description' => 'Error al enviar presentación mensual a SSN: ' . $e->getMessage(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'presentation_id' => $presentation->id,
                    'error' => $e->getMessage(),
                ],
            ]);

            return back()->withErrors(['error' => 'Error al enviar a SSN: ' . $e->getMessage()]);
        }
    }

    /**
     * Crear stocks de ejemplo para testing
     */
    private function createSampleStocks(Presentation $presentation)
    {
        // Stock de inversiones
        MonthlyStock::create([
            'presentation_id' => $presentation->id,
            'tipo' => 'I',
            'tipo_especie' => 'TP',
            'codigo_especie' => 'TP001',
            'cantidad_devengado_especies' => 1000000.000000,
            'cantidad_percibido_especies' => 1000000.000000,
            'codigo_afectacion' => '999',
            'tipo_valuacion' => 'V',
            'con_cotizacion' => true,
            'libre_disponibilidad' => true,
            'emisor_grupo_economico' => false,
            'emisor_art_ret' => false,
            'prevision_desvalorizacion' => 0,
            'valor_contable' => 1050000,
            'en_custodia' => true,
            'financiera' => true,
            'valor_financiero' => 1050000,
        ]);

        // Stock de plazo fijo
        MonthlyStock::create([
            'presentation_id' => $presentation->id,
            'tipo' => 'P',
            'tipo_pf' => '001',
            'bic' => 'NACNARBAXXX',
            'cdf' => '123456789',
            'fecha_constitucion' => '01012025',
            'fecha_vencimiento_pf' => '01022025',
            'moneda' => 'ARS',
            'valor_nominal_origen' => 500000,
            'valor_nominal_nacional' => 500000,
            'emisor_grupo_economico' => false,
            'libre_disponibilidad' => true,
            'en_custodia' => true,
            'codigo_afectacion' => '999',
            'tipo_tasa' => 'F',
            'tasa' => 30.000,
            'titulo_deuda' => false,
            'valor_contable' => 512500,
            'financiera' => true,
        ]);

        // Stock de cheque pago diferido
        MonthlyStock::create([
            'presentation_id' => $presentation->id,
            'tipo' => 'C',
            'codigo_sgr' => '001',
            'codigo_cheque' => '987654321',
            'fecha_emision' => '15012025',
            'fecha_vencimiento_cheque' => '15022025',
            'moneda' => 'ARS',
            'valor_nominal' => 200000,
            'valor_adquisicion' => 195000,
            'emisor_grupo_economico' => false,
            'libre_disponibilidad' => true,
            'en_custodia' => true,
            'codigo_afectacion' => '999',
            'tipo_tasa' => 'F',
            'tasa' => 25.000,
            'valor_contable' => 197500,
            'financiera' => true,
            'fecha_adquisicion' => '15012025',
        ]);
    }
}
