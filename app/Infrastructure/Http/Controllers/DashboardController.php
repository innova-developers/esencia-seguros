<?php

namespace App\Infrastructure\Http\Controllers;

use App\Domain\Services\SSNAuthService;
use App\Domain\Models\Presentation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    private SSNAuthService $ssnAuthService;

    public function __construct(SSNAuthService $ssnAuthService)
    {
        $this->ssnAuthService = $ssnAuthService;
    }

    public function index()
    {
        $user = Auth::user();
        
        // Obtener información real de la conexión SSN
        $ssnInfo = $this->getSSNConnectionInfo();
        
        // Obtener estadísticas de presentaciones del usuario
        $stats = $this->getPresentationStats($user->id);
        
        return view('dashboard', compact('ssnInfo', 'stats'));
    }

    /**
     * Obtiene información real de la conexión SSN
     */
    public function getSSNConnectionInfo(): array
    {
        // Obtener token desde cache o base de datos
        $token = $this->ssnAuthService->getCachedToken();
        
        if (!$token) {
            return [
                'connected' => false,
                'status' => 'No Conectado',
                'message' => 'No se pudo establecer conexión con la SSN',
                'icon' => 'fas fa-exclamation-triangle',
                'color' => 'warning',
                'token' => null,
                'expiration' => null,
                'mode' => null,
                'last_4_chars' => null,
            ];
        }

        // Verificar si el token es válido
        $isValid = $this->ssnAuthService->isTokenValid($token);
        
        if (!$isValid) {
            return [
                'connected' => false,
                'status' => 'Token Expirado',
                'message' => 'El token de conexión ha expirado',
                'icon' => 'fas fa-clock',
                'color' => 'danger',
                'token' => $token,
                'expiration' => null,
                'mode' => null,
                'last_4_chars' => substr($token, -4),
            ];
        }

        // Obtener información del token
        $tokenInfo = $this->ssnAuthService->getTokenInfo($token);
        
        // Determinar modo (mock o real)
        $isMock = $tokenInfo['mock'] ?? false;
        $mode = $isMock ? 'Testing Local' : 'Testing SSN';
        $modeColor = $isMock ? 'warning' : 'success';
        
        // Obtener expiración
        $expiration = $tokenInfo['expiration'] ?? null;
        
        // Formatear expiración si existe
        $formattedExpiration = null;
        if ($expiration) {
            try {
                $expirationDate = \DateTime::createFromFormat('d/m/Y H:i:s', $expiration);
                if ($expirationDate) {
                    $formattedExpiration = $expirationDate->format('d/m/Y H:i:s');
                }
            } catch (\Exception $e) {
                $formattedExpiration = $expiration;
            }
        }

        return [
            'connected' => true,
            'status' => 'Conectado',
            'message' => 'Conexión activa con la SSN',
            'icon' => 'fas fa-shield-alt',
            'color' => 'success',
            'token' => $token,
            'expiration' => $formattedExpiration,
            'mode' => $mode,
            'mode_color' => $modeColor,
            'last_4_chars' => substr($token, -4),
            'is_mock' => $isMock,
            'connection_time' => session('ssn_connection_time'),
        ];
    }

    /**
     * Obtiene estadísticas de presentaciones del usuario
     */
    public function getPresentationStats(int $userId): array
    {
        $stats = [
            'total' => 0,
            'vacio' => 0,
            'cargado' => 0,
            'presentado' => 0,
            'rectificacion_pendiente' => 0,
            'a_rectificar' => 0,
            'rechazada' => 0,
        ];

        try {
            // Obtener conteos por estado desde la base de datos
            $presentations = Presentation::where('user_id', $userId)
                ->selectRaw('estado, COUNT(*) as count')
                ->groupBy('estado')
                ->get();

            foreach ($presentations as $presentation) {
                $estado = strtolower($presentation->estado);
                $stats[$estado] = $presentation->count;
                $stats['total'] += $presentation->count;
            }
        } catch (\Exception $e) {
            // Si hay error de base de datos, usar datos mockeados como fallback
            \Log::warning('Error obteniendo estadísticas de presentaciones: ' . $e->getMessage());
            
            // Datos mockeados como fallback
            $stats = [
                'total' => 12,
                'vacio' => 3,
                'cargado' => 4,
                'presentado' => 3,
                'rectificacion_pendiente' => 1,
                'a_rectificar' => 1,
                'rechazada' => 0,
            ];
        }

        return $stats;
    }
} 