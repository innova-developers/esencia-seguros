<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Domain\Services\SSNAuthService;
use App\Services\SSNService;

class TestSSNCertificate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ssn:test-certificate {--url= : Specific URL to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SSN certificate with real HTTPS requests';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔒 Testing SSN Certificate with real HTTPS requests...');
        
        $certPath = config('services.ssn.cert_path');
        $fullPath = base_path($certPath);
        
        if (!file_exists($fullPath)) {
            $this->error("❌ Certificate not found: {$fullPath}");
            return 1;
        }
        
        $this->info("✅ Certificate found: {$fullPath}");
        
        // Test URL
        $testUrl = $this->option('url') ?: config('services.ssn.base_url_production') . '/test';
        $this->line("Testing URL: {$testUrl}");
        
        try {
            // Test with certificate
            $this->info('🔐 Testing with certificate...');
            $response = Http::withOptions([
                'verify' => $fullPath,
                'timeout' => 30,
                'connect_timeout' => 10,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_CAINFO => $fullPath,
                ],
            ])->get($testUrl);
            
            $this->info("✅ Certificate test successful!");
            $this->line("Status: " . $response->status());
            $this->line("Response: " . substr($response->body(), 0, 200) . "...");
            
        } catch (\Exception $e) {
            $this->error("❌ Certificate test failed: " . $e->getMessage());
            
            // Try without certificate for comparison
            $this->info('🔄 Trying without certificate for comparison...');
            try {
                $response = Http::withOptions([
                    'verify' => false,
                    'timeout' => 30,
                    'connect_timeout' => 10,
                ])->get($testUrl);
                
                $this->warn("⚠️  Request without certificate succeeded (Status: {$response->status()})");
                $this->line("This suggests the certificate configuration needs adjustment.");
                
            } catch (\Exception $e2) {
                $this->error("❌ Request without certificate also failed: " . $e2->getMessage());
            }
        }
        
        // Test SSN service
        $this->info('🌐 Testing SSN Service...');
        try {
            $ssnService = app(SSNService::class);
            $serviceInfo = $ssnService->getServiceInfo();
            
            $this->line("Service status: " . $serviceInfo['status']);
            $this->line("Service mode: " . $serviceInfo['mode']);
            $this->line("Has token: " . ($serviceInfo['has_token'] ? 'Yes' : 'No'));
            
        } catch (\Exception $e) {
            $this->error("❌ SSN Service test failed: " . $e->getMessage());
        }
        
        return 0;
    }
}
