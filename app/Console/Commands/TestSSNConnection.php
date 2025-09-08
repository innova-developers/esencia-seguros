<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Services\SSNAuthService;
use App\Services\SSNService;
use Illuminate\Support\Facades\Http;

class TestSSNConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ssn:test-connection {--with-cert : Test with certificate verification}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SSN connection with certificate support';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing SSN Connection...');
        
        // Verificar configuración
        $this->checkConfiguration();
        
        // Verificar certificado
        $this->checkCertificate();
        
        // Probar conexión básica
        $this->testBasicConnection();
        
        // Probar con certificado si está disponible
        if ($this->option('with-cert')) {
            $this->testWithCertificate();
        }
        
        $this->info('✅ SSN connection test completed');
    }
    
    private function checkConfiguration()
    {
        $this->info('📋 Checking configuration...');
        
        $environment = config('services.ssn.environment');
        $baseUrl = config("services.ssn.base_url_{$environment}");
        $mockEnabled = config('services.ssn.mock_enabled');
        $certPath = config('services.ssn.cert_path');
        
        $this->line("Environment: {$environment}");
        $this->line("Base URL: {$baseUrl}");
        $this->line("Mock enabled: " . ($mockEnabled ? 'Yes' : 'No'));
        $this->line("Cert path: {$certPath}");
        
        if ($mockEnabled) {
            $this->warn('⚠️  Running in mock mode - no real SSN connection');
        }
    }
    
    private function checkCertificate()
    {
        $this->info('🔐 Checking certificate...');
        
        $certPath = config('services.ssn.cert_path');
        $fullPath = base_path($certPath);
        
        if (file_exists($fullPath)) {
            $this->info("✅ Certificate found: {$fullPath}");
            $this->line("File size: " . filesize($fullPath) . " bytes");
            $this->line("Last modified: " . date('Y-m-d H:i:s', filemtime($fullPath)));
        } else {
            $this->error("❌ Certificate not found: {$fullPath}");
            $this->warn("⚠️  SSL verification will be disabled");
        }
    }
    
    private function testBasicConnection()
    {
        $this->info('🌐 Testing basic connection...');
        
        try {
            $authService = app(SSNAuthService::class);
            $ssnService = app(SSNService::class);
            
            $this->line("Auth service available: " . ($authService ? 'Yes' : 'No'));
            $this->line("SSN service available: " . ($ssnService ? 'Yes' : 'No'));
            $this->line("Service info: " . json_encode($ssnService->getServiceInfo(), JSON_PRETTY_PRINT));
            
        } catch (\Exception $e) {
            $this->error("❌ Error testing services: " . $e->getMessage());
        }
    }
    
    private function testWithCertificate()
    {
        $this->info('🔒 Testing with certificate...');
        
        $certPath = config('services.ssn.cert_path');
        $fullPath = base_path($certPath);
        
        if (!file_exists($fullPath)) {
            $this->warn('⚠️  Certificate not found, skipping certificate test');
            return;
        }
        
        try {
            $environment = config('services.ssn.environment');
            $baseUrl = config("services.ssn.base_url_{$environment}");
            $testUrl = $baseUrl . '/test'; // Endpoint de prueba
            
            $this->line("Testing URL: {$testUrl}");
            
            $response = Http::withOptions([
                'verify' => $fullPath,
                'timeout' => 10,
                'connect_timeout' => 5,
            ])->get($testUrl);
            
            $this->line("Status: " . $response->status());
            $this->line("Response: " . $response->body());
            
        } catch (\Exception $e) {
            $this->error("❌ Certificate test failed: " . $e->getMessage());
        }
    }
}
