<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Infrastructure\Http\Controllers\DashboardController;
use App\Domain\Services\SSNAuthService;
use Illuminate\Support\Facades\Auth;

class TestDashboard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:test {--user=1 : ID del usuario para probar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba el dashboard y la información SSN';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Probando Dashboard y información SSN...');
        $this->newLine();

        // Simular autenticación de usuario
        $userId = $this->option('user');
        $user = \App\Domain\Models\User::find($userId);
        
        if (!$user) {
            $this->error("❌ Usuario con ID {$userId} no encontrado");
            return 1;
        }

        Auth::login($user);
        $this->info("✅ Usuario autenticado: {$user->name} ({$user->email})");

        // Crear instancia del controlador
        $ssnAuthService = app(SSNAuthService::class);
        $dashboardController = new DashboardController($ssnAuthService);

        // Obtener información SSN
        $this->newLine();
        $this->info('🔍 Información de conexión SSN:');
        
        $ssnInfo = $dashboardController->getSSNConnectionInfo();
        
        $this->line("   Estado: " . ($ssnInfo['connected'] ? '✅ Conectado' : '❌ No Conectado'));
        $this->line("   Status: {$ssnInfo['status']}");
        $this->line("   Mensaje: {$ssnInfo['message']}");
        
        if ($ssnInfo['expiration']) {
            $this->line("   Expiración: {$ssnInfo['expiration']}");
        }
        
        if ($ssnInfo['mode']) {
            $this->line("   Modo: {$ssnInfo['mode']}");
        }
        
        if ($ssnInfo['last_4_chars']) {
            $this->line("   Token: ...{$ssnInfo['last_4_chars']}");
        }

        // Obtener estadísticas
        $this->newLine();
        $this->info('📊 Estadísticas de presentaciones:');
        
        $stats = $dashboardController->getPresentationStats($user->id);
        
        $this->line("   Total: {$stats['total']}");
        $this->line("   Vacío: {$stats['vacio']}");
        $this->line("   Cargado: {$stats['cargado']}");
        $this->line("   Presentado: {$stats['presentado']}");
        $this->line("   Rectificación Pendiente: {$stats['rectificacion_pendiente']}");
        $this->line("   A Rectificar: {$stats['a_rectificar']}");
        $this->line("   Rechazada: {$stats['rechazada']}");

        $this->newLine();
        $this->info('✅ Prueba completada exitosamente');

        return 0;
    }
} 