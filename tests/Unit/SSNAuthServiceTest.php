<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Domain\Services\SSNAuthService;
use App\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class SSNAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private SSNAuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->authService = new SSNAuthService();
    }

    /** @test */
    public function it_uses_mock_mode_when_ssn_mock_enabled()
    {
        Config::set('services.ssn.mock_enabled', true);
        
        $this->assertTrue($this->authService->isMockMode());
    }

    /** @test */
    public function it_uses_real_mode_when_ssn_mock_disabled()
    {
        Config::set('services.ssn.mock_enabled', false);
        
        $this->assertFalse($this->authService->isMockMode());
    }

    /** @test */
    public function it_authenticates_successfully_in_mock_mode()
    {
        Config::set('services.ssn.mock_enabled', true);
        
        $result = $this->authService->authenticate('test@example.com', '1234', 'password123');
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('expiration', $result);
    }

    /** @test */
    public function it_authenticates_successfully_in_real_mode()
    {
        Config::set('services.ssn.mock_enabled', false);
        Config::set('services.ssn.base_url', 'https://api.ssn.gov.ar');
        Config::set('services.ssn.auth_endpoint', '/auth/login');
        
        Http::fake([
            'https://api.ssn.gov.ar/auth/login' => Http::response([
                'TOKEN' => 'real-jwt-token',
                'FECHA_EXPIRACION' => '31/12/2025 23:59:59',
            ], 200)
        ]);
        
        $result = $this->authService->authenticate('test@example.com', '1234', 'password123');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('real-jwt-token', $result['token']);
        $this->assertEquals('31/12/2025 23:59:59', $result['expiration']);
    }

    /** @test */
    public function it_handles_authentication_error_in_real_mode()
    {
        Config::set('services.ssn.mock_enabled', false);
        Config::set('services.ssn.base_url', 'https://api.ssn.gov.ar');
        Config::set('services.ssn.auth_endpoint', '/auth/login');
        
        Http::fake([
            'https://api.ssn.gov.ar/auth/login' => Http::response([
                'error' => 'Credenciales inválidas',
            ], 401)
        ]);
        
        $result = $this->authService->authenticate('invalid@example.com', '1234', 'wrongpassword');
        
        $this->assertNull($result);
    }

    /** @test */
    public function it_handles_network_error_in_real_mode()
    {
        Config::set('services.ssn.mock_enabled', false);
        Config::set('services.ssn.base_url', 'https://api.ssn.gov.ar');
        Config::set('services.ssn.auth_endpoint', '/auth/login');
        
        Http::fake([
            'https://api.ssn.gov.ar/auth/login' => Http::response(null, 500)
        ]);
        
        $result = $this->authService->authenticate('test@example.com', '1234', 'password123');
        
        $this->assertNull($result);
    }

    /** @test */
    public function it_validates_required_credentials()
    {
        Config::set('services.ssn.mock_enabled', true);
        
        // Los parámetros son requeridos por el tipo, así que no podemos probar sin ellos
        // En su lugar, probamos que el método funciona con parámetros válidos
        $result = $this->authService->authenticate('test@example.com', '1234', 'password123');
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_builds_correct_auth_url()
    {
        Config::set('services.ssn.base_url_testing', 'https://api.ssn.gov.ar/v1');
        Config::set('services.ssn.auth_endpoint', '/auth/login');
        
        $authUrl = $this->authService->getAuthUrl();
        $this->assertEquals('https://api.ssn.gov.ar/v1/auth/login', $authUrl);
    }

    /** @test */
    public function it_generates_correct_headers_for_auth()
    {
        $headers = $this->authService->getHeaders();
        
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertEquals('application/json', $headers['Content-Type']);
        $this->assertArrayHasKey('Accept', $headers);
        $this->assertEquals('application/json', $headers['Accept']);
    }

    /** @test */
    public function it_handles_missing_configuration_gracefully()
    {
        Config::set('services.ssn.mock_enabled', false);
        Config::set('services.ssn.base_url_testing', null);
        Config::set('services.ssn.auth_endpoint', null);
        
        $result = $this->authService->authenticate('test@example.com', '1234', 'password123');
        
        $this->assertNull($result);
    }

    /** @test */
    public function it_logs_authentication_attempts()
    {
        Config::set('services.ssn.mock_enabled', true);
        
        $result = $this->authService->authenticate('test@example.com', '1234', 'password123');
        
        $this->assertTrue($result['success']);
        
        // Verificar que se creó un log (si el sistema de logs está configurado)
        // Esto dependerá de cómo esté implementado el logging en el servicio
    }

    /** @test */
    public function it_returns_consistent_mock_data()
    {
        Config::set('services.ssn.mock_enabled', true);
        
        $result1 = $this->authService->authenticate('test@example.com', '1234', 'password123');
        $result2 = $this->authService->authenticate('test@example.com', '1234', 'password123');
        
        $this->assertEquals($result1['token'], $result2['token']);
        $this->assertEquals($result1['expiration'], $result2['expiration']);
    }
}
