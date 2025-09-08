<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\SSNService;

class TestSSNBundle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ssn:test-bundle {--url= : Specific URL to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SSN with certificate bundle';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Testing SSN with certificate bundle...');
        
        $certPath = config('services.ssn.cert_path');
        $fullPath = base_path($certPath);
        $testUrl = $this->option('url') ?: 'https://ri.ssn.gob.ar/api/test';
        
        if (!file_exists($fullPath)) {
            $this->error("❌ Certificate not found: {$fullPath}");
            return 1;
        }
        
        $this->info("✅ Certificate found: {$fullPath}");
        $this->line("Testing URL: {$testUrl}");
        
        // Test bundle creation
        $this->testBundleCreation($fullPath);
        
        // Test with bundle
        $this->testWithBundle($testUrl, $fullPath);
        
        // Test SSN service
        $this->testSSNService();
        
        return 0;
    }
    
    private function testBundleCreation($certPath)
    {
        $this->info('🔍 Testing bundle creation...');
        
        try {
            $ssnService = app(SSNService::class);
            $reflection = new \ReflectionClass($ssnService);
            $method = $reflection->getMethod('createCertificateBundle');
            $method->setAccessible(true);
            
            $bundlePath = $method->invoke($ssnService, $certPath);
            
            if (file_exists($bundlePath)) {
                $this->info("✅ Bundle created successfully: {$bundlePath}");
                $this->line("Bundle size: " . filesize($bundlePath) . " bytes");
                $this->line("Bundle last modified: " . date('Y-m-d H:i:s', filemtime($bundlePath)));
                
                // Show first few lines of bundle
                $bundleContent = file_get_contents($bundlePath);
                $lines = explode("\n", $bundleContent);
                $this->line("First 5 lines of bundle:");
                for ($i = 0; $i < min(5, count($lines)); $i++) {
                    $this->line("  " . substr($lines[$i], 0, 80) . "...");
                }
            } else {
                $this->error("❌ Bundle creation failed");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Bundle creation error: " . $e->getMessage());
        }
    }
    
    private function testWithBundle($url, $certPath)
    {
        $this->info('🔍 Testing with bundle...');
        
        try {
            // Create bundle manually
            $bundlePath = storage_path('app/ssn_ca_bundle.crt');
            
            // Try to find system CA
            $systemCaPaths = [
                '/etc/ssl/certs/ca-certificates.crt',
                '/etc/ssl/certs/ca-bundle.crt',
                '/etc/pki/tls/certs/ca-bundle.crt',
                '/usr/local/share/ca-certificates/ca-certificates.crt',
                '/etc/ssl/cert.pem',
            ];
            
            $bundle = '';
            $systemCaFound = false;
            
            foreach ($systemCaPaths as $systemPath) {
                if (file_exists($systemPath)) {
                    $bundle .= file_get_contents($systemPath) . "\n";
                    $systemCaFound = true;
                    $this->line("Using system CA: {$systemPath}");
                    break;
                }
            }
            
            if (!$systemCaFound) {
                $this->warn("⚠️  No system CA found, using only SSN certificate");
            }
            
            $bundle .= file_get_contents($certPath) . "\n";
            file_put_contents($bundlePath, $bundle);
            
            // Test with bundle
            $response = Http::withOptions([
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_CAINFO => $bundlePath,
                    CURLOPT_TIMEOUT => 30,
                ],
            ])->get($url);
            
            $this->info("✅ Bundle test successful! Status: {$response->status()}");
            
        } catch (\Exception $e) {
            $this->error("❌ Bundle test failed: " . $e->getMessage());
        }
    }
    
    private function testSSNService()
    {
        $this->info('🌐 Testing SSN Service with bundle...');
        
        try {
            $ssnService = app(SSNService::class);
            $serviceInfo = $ssnService->getServiceInfo();
            
            $this->line("Service status: " . $serviceInfo['status']);
            $this->line("Service mode: " . $serviceInfo['mode']);
            $this->line("Has token: " . ($serviceInfo['has_token'] ? 'Yes' : 'No'));
            
        } catch (\Exception $e) {
            $this->error("❌ SSN Service test failed: " . $e->getMessage());
        }
    }
}
