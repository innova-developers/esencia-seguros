<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Infrastructure\Http\Controllers\DashboardController;
use App\Domain\Services\SSNAuthService;
use App\Services\SSNService;

class TestSSNStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ssn:test-status {--user-id=1 : User ID to test with}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SSN connection status detection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing SSN Status Detection...');
        
        $userId = $this->option('user-id');
        
        // Simular sesión con token SSN
        $this->simulateSSNSession();
        
        // Test auth service
        $this->testAuthService();
        
        // Test SSN service
        $this->testSSNService();
        
        // Test dashboard controller
        $this->testDashboardController($userId);
        
        return 0;
    }
    
    private function simulateSSNSession()
    {
        $this->info('🔧 Simulating SSN session...');
        
        // Simular datos de sesión SSN
        session([
            'ssn_token' => 'test_token_' . time(),
            'ssn_expiration' => '31/12/2025 23:59:59',
            'ssn_mock' => false,
            'ssn_connection_time' => 6457.23,
        ]);
        
        $this->line("Session data set:");
        $this->line("  Token: " . session('ssn_token'));
        $this->line("  Expiration: " . session('ssn_expiration'));
        $this->line("  Mock: " . (session('ssn_mock') ? 'true' : 'false'));
        $this->line("  Connection time: " . session('ssn_connection_time') . "ms");
    }
    
    private function testAuthService()
    {
        $this->info('🔐 Testing Auth Service...');
        
        try {
            $authService = app(SSNAuthService::class);
            
            $cachedToken = $authService->getCachedToken();
            $this->line("Cached token: " . ($cachedToken ? substr($cachedToken, -8) : 'null'));
            
            $sessionToken = session('ssn_token');
            $this->line("Session token: " . ($sessionToken ? substr($sessionToken, -8) : 'null'));
            
            $isValid = $authService->isTokenValid($sessionToken);
            $this->line("Token valid: " . ($isValid ? 'true' : 'false'));
            
            $tokenInfo = $authService->getTokenInfo($sessionToken);
            $this->line("Token info: " . json_encode($tokenInfo, JSON_PRETTY_PRINT));
            
        } catch (\Exception $e) {
            $this->error("❌ Auth Service error: " . $e->getMessage());
        }
    }
    
    private function testSSNService()
    {
        $this->info('🌐 Testing SSN Service...');
        
        try {
            $ssnService = app(SSNService::class);
            $serviceInfo = $ssnService->getServiceInfo();
            
            $this->line("Service info: " . json_encode($serviceInfo, JSON_PRETTY_PRINT));
            
        } catch (\Exception $e) {
            $this->error("❌ SSN Service error: " . $e->getMessage());
        }
    }
    
    private function testDashboardController($userId)
    {
        $this->info('📊 Testing Dashboard Controller...');
        
        try {
            $dashboardController = app(DashboardController::class);
            $ssnInfo = $dashboardController->getSSNConnectionInfo();
            
            $this->line("SSN Connection Info:");
            $this->line("  Connected: " . ($ssnInfo['connected'] ? 'true' : 'false'));
            $this->line("  Status: " . $ssnInfo['status']);
            $this->line("  Message: " . $ssnInfo['message']);
            $this->line("  Mode: " . ($ssnInfo['mode'] ?? 'null'));
            $this->line("  Token: " . ($ssnInfo['token'] ? substr($ssnInfo['token'], -8) : 'null'));
            $this->line("  Expiration: " . ($ssnInfo['expiration'] ?? 'null'));
            $this->line("  Connection time: " . ($ssnInfo['connection_time'] ?? 'null') . "ms");
            
        } catch (\Exception $e) {
            $this->error("❌ Dashboard Controller error: " . $e->getMessage());
        }
    }
}
