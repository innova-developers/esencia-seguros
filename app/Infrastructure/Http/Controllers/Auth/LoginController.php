<?php

namespace App\Infrastructure\Http\Controllers\Auth;

use App\Application\Auth\LoginUserUseCase;
use App\Application\Auth\SSNLoginUseCase;
use App\Domain\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;

class LoginController extends Controller
{
    private LoginUserUseCase $loginUserUseCase;
    private SSNLoginUseCase $ssnLoginUseCase;

    public function __construct(LoginUserUseCase $loginUserUseCase, SSNLoginUseCase $ssnLoginUseCase)
    {
        $this->loginUserUseCase = $loginUserUseCase;
        $this->ssnLoginUseCase = $ssnLoginUseCase;
    }

    public function showLoginForm()
    {
        ActivityLog::log(
            'view_login_form',
            'Usuario accedió al formulario de login',
            'auth'
        );

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = ($this->loginUserUseCase)($credentials['email'], $credentials['password']);

        if ($user) {
            // Autenticar con la SSN
            $ssnCredentials = config('services.ssn');
            $ssnResult = null;
            $ssnConnectionTime = null;
            
            try {
                // Medir tiempo de conexión SSN
                $startTime = microtime(true);
                
                $ssnResult = ($this->ssnLoginUseCase)(
                    $ssnCredentials['username'] ?? '',
                    $ssnCredentials['cia'] ?? '',
                    $ssnCredentials['password'] ?? ''
                );
                
                $ssnConnectionTime = round((microtime(true) - $startTime) * 1000, 2); // en milisegundos
                
            } catch (\Exception $e) {
                // Log del error de conexión SSN
                ActivityLog::log(
                    'ssn_connection_error',
                    'Error de conexión con SSN: ' . $e->getMessage(),
                    'ssn',
                    $user->id,
                    [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ],
                    'error'
                );
            }

            Auth::login($user);
            
            // Guardar información de la SSN en la sesión
            if ($ssnResult && $ssnResult['success']) {
                session([
                    'ssn_token' => $ssnResult['token'],
                    'ssn_expiration' => $ssnResult['expiration'],
                    'ssn_mock' => $ssnResult['mock'] ?? false,
                    'ssn_connection_time' => $ssnConnectionTime,
                ]);

                ActivityLog::log(
                    'ssn_connection_success',
                    'Conexión exitosa con SSN',
                    'ssn',
                    $user->id,
                    [
                        'mock' => $ssnResult['mock'] ?? false,
                        'expiration' => $ssnResult['expiration'] ?? null,
                        'connection_time_ms' => $ssnConnectionTime,
                    ],
                    'success'
                );
                
                // Mensaje de éxito con información de la conexión
                $message = 'Sesión iniciada exitosamente';
                if ($ssnConnectionTime) {
                    $message .= " (Conexión SSN: {$ssnConnectionTime}ms)";
                }
                
                return redirect()->intended('dashboard')->with('success', $message);
                
            } else {
                // Conexión SSN fallida pero login exitoso
                ActivityLog::log(
                    'ssn_connection_failed',
                    'Fallo en la conexión con SSN',
                    'ssn',
                    $user->id,
                    [
                        'connection_time_ms' => $ssnConnectionTime,
                    ],
                    'error'
                );
                
                return redirect()->intended('dashboard')->with('warning', 
                    'Sesión iniciada, pero no se pudo conectar con la SSN. Algunas funciones pueden estar limitadas.'
                );
            }

            ActivityLog::log(
                'login_success',
                'Usuario inició sesión exitosamente',
                'auth',
                $user->id,
                [
                    'email' => $credentials['email'],
                    'ip' => $request->ip(),
                    'ssn_connected' => $ssnResult && $ssnResult['success'],
                ],
                'success'
            );
        }

        ActivityLog::log(
            'login_failed',
            'Intento fallido de inicio de sesión',
            'auth',
            null,
            [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
            ],
            'error'
        );

        return back()->withErrors([
            'email' => 'Las credenciales no son válidas.',
        ])->withInput();
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        
        ActivityLog::log(
            'logout',
            'Usuario cerró sesión',
            'auth',
            $user ? $user->id : null,
            [
                'ip' => $request->ip(),
            ]
        );

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Limpiar token SSN del cache
        app(\App\Domain\Services\SSNAuthService::class)->clearCachedToken();
        
        return redirect('/login');
    }
} 