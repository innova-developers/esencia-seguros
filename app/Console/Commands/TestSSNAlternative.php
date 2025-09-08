<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestSSNAlternative extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ssn:test-alternative {--url= : Specific URL to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test SSN with alternative certificate configurations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Testing SSN with alternative certificate configurations...');
        
        $certPath = config('services.ssn.cert_path');
        $fullPath = base_path($certPath);
        $testUrl = $this->option('url') ?: 'https://ri.ssn.gob.ar/api/test';
        
        if (!file_exists($fullPath)) {
            $this->error("❌ Certificate not found: {$fullPath}");
            return 1;
        }
        
        $this->info("✅ Certificate found: {$fullPath}");
        $this->line("Testing URL: {$testUrl}");
        
        // Test 1: Using only CURLOPT_CAINFO
        $this->testMethod1($testUrl, $fullPath);
        
        // Test 2: Using verify with certificate
        $this->testMethod2($testUrl, $fullPath);
        
        // Test 3: Using custom CA bundle
        $this->testMethod3($testUrl, $fullPath);
        
        // Test 4: Using system CA bundle + custom cert
        $this->testMethod4($testUrl, $fullPath);
        
        return 0;
    }
    
    private function testMethod1($url, $certPath)
    {
        $this->info('🔍 Test 1: Using only CURLOPT_CAINFO...');
        
        try {
            $response = Http::withOptions([
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_CAINFO => $certPath,
                    CURLOPT_TIMEOUT => 30,
                ],
            ])->get($url);
            
            $this->info("✅ Method 1 successful! Status: {$response->status()}");
            
        } catch (\Exception $e) {
            $this->error("❌ Method 1 failed: " . $e->getMessage());
        }
    }
    
    private function testMethod2($url, $certPath)
    {
        $this->info('🔍 Test 2: Using verify with certificate...');
        
        try {
            $response = Http::withOptions([
                'verify' => $certPath,
                'timeout' => 30,
            ])->get($url);
            
            $this->info("✅ Method 2 successful! Status: {$response->status()}");
            
        } catch (\Exception $e) {
            $this->error("❌ Method 2 failed: " . $e->getMessage());
        }
    }
    
    private function testMethod3($url, $certPath)
    {
        $this->info('🔍 Test 3: Using custom CA bundle...');
        
        try {
            // Create a temporary bundle with system CA + custom cert
            $systemCaPath = '/etc/ssl/certs/ca-certificates.crt';
            $bundlePath = storage_path('app/temp_ca_bundle.crt');
            
            if (file_exists($systemCaPath)) {
                $bundle = file_get_contents($systemCaPath) . "\n" . file_get_contents($certPath);
                file_put_contents($bundlePath, $bundle);
                
                $response = Http::withOptions([
                    'verify' => $bundlePath,
                    'timeout' => 30,
                ])->get($url);
                
                $this->info("✅ Method 3 successful! Status: {$response->status()}");
                
                // Clean up
                unlink($bundlePath);
                
            } else {
                $this->warn("⚠️  System CA bundle not found, skipping method 3");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Method 3 failed: " . $e->getMessage());
        }
    }
    
    private function testMethod4($url, $certPath)
    {
        $this->info('🔍 Test 4: Using system CA + custom cert in curl options...');
        
        try {
            $response = Http::withOptions([
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSL_VERIFYHOST => 2,
                    CURLOPT_CAINFO => $certPath,
                    CURLOPT_CAPATH => '/etc/ssl/certs',
                    CURLOPT_TIMEOUT => 30,
                ],
            ])->get($url);
            
            $this->info("✅ Method 4 successful! Status: {$response->status()}");
            
        } catch (\Exception $e) {
            $this->error("❌ Method 4 failed: " . $e->getMessage());
        }
    }
}
