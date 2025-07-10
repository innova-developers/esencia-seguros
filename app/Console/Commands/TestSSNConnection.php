<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Services\SSNAuthService;
use Illuminate\Support\Facades\Config;

class TestSSNConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ssn:test-connection {--mock : Forzar modo mock} {--no-cache : No guardar en cache/DB}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba la conexión con la API de la SSN';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Probando conexión con la API de la SSN...');
        $this->newLine();

        // Mostrar configuración actual
        $this->showConfiguration();

        // Crear instancia del servicio
        $authService = new SSNAuthService();

        // Verificar modo
        if ($this->option('mock')) {
            $this->warn('⚠️  Forzando modo MOCK para la prueba');
            Config::set('services.ssn.mock_enabled', true);
        }

        $isMockMode = $authService->isMockMode();
        $this->info($isMockMode ? '🎭 Modo: MOCK' : '🌐 Modo: API REAL');
        $this->newLine();

        // Obtener credenciales
        $username = Config::get('services.ssn.username');
        $cia = Config::get('services.ssn.cia');
        $password = Config::get('services.ssn.password');

        if (!$username || !$cia || !$password) {
            $this->error('❌ Credenciales SSN no configuradas en el .env');
            $this->error('   Asegúrate de tener configuradas: SSN_USERNAME, SSN_CIA, SSN_PASSWORD');
            return 1;
        }

        $this->info('✅ Credenciales configuradas');
        $this->line("   Usuario: {$username}");
        $this->line("   Compañía: {$cia}");
        $this->line("   Contraseña: " . str_repeat('*', strlen($password)));

        // Mostrar URL de autenticación
        $this->newLine();
        $this->info('🌐 URL de autenticación:');
        $this->line("   " . $authService->getAuthUrl());

        // Intentar autenticación
        $this->newLine();
        $this->info('🔐 Intentando autenticación...');

        try {
            $result = $authService->authenticate($username, $cia, $password);
            if ($result && $result['success']) {
                $this->info('✅ Autenticación exitosa!');
                $this->line("   Token: " . substr($result['token'], 0, 50) . '...');
                $this->line("   Expiración: " . ($result['expiration'] ?? 'No especificada'));
                
                if (isset($result['mock'])) {
                    $this->warn('   ⚠️  Este es un token MOCK');
                } else {
                    $this->info('   ✅ Este es un token REAL de la SSN');
                }

                // Cachear el token solo si no se especifica --no-cache
                if (!$this->option('no-cache') && isset($result['token']) && isset($result['expiration'])) {
                    try {
                        $authService->cacheToken($result['token'], $result['expiration'], $username, $cia);
                        $this->info('💾 Token guardado en cache y base de datos');
                    } catch (\Exception $e) {
                        $this->warn('⚠️  No se pudo guardar en cache/DB: ' . $e->getMessage());
                    }
                }

                // Verificar token desde cache
                if (!$this->option('no-cache')) {
                    try {
                        $cachedToken = $authService->getCachedToken();
                        if ($cachedToken) {
                            $this->info('✅ Token recuperado desde cache');
                        }
                    } catch (\Exception $e) {
                        $this->warn('⚠️  No se pudo verificar cache: ' . $e->getMessage());
                    }
                }

                return 0;
            } else {
                $this->error('❌ Autenticación fallida');
                $this->error('   No se pudo obtener un token válido');
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Error durante la autenticación');
            $this->error('   ' . $e->getMessage());
            
            // Mostrar más detalles si es un error de red
            if (str_contains($e->getMessage(), 'cURL error') || str_contains($e->getMessage(), 'Connection')) {
                $this->newLine();
                $this->warn('💡 Posibles soluciones:');
                $this->line('   1. Verificar que la URL de la API sea correcta');
                $this->line('   2. Verificar conectividad a internet');
                $this->line('   3. Verificar que las credenciales sean correctas');
                $this->line('   4. Verificar que el endpoint de autenticación sea correcto');
            }
            
            return 1;
        }
    }

    private function showConfiguration()
    {
        $this->info('📋 Configuración actual:');
        $this->line("   Environment: " . Config::get('services.ssn.environment', 'testing'));
        $this->line("   Base URL Testing: " . Config::get('services.ssn.base_url_testing'));
        $this->line("   Base URL Production: " . Config::get('services.ssn.base_url_production'));
        $this->line("   Mock Enabled: " . (Config::get('services.ssn.mock_enabled') ? 'Sí' : 'No'));
        $this->line("   Auth Endpoint: " . Config::get('services.ssn.auth_endpoint', '/login'));
        $this->newLine();
    }
} 