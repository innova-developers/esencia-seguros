<?php

namespace Tests\Feature;

use App\Domain\Models\User;
use App\Domain\Services\SSNAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SSNIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_login_with_ssn_integration()
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Act
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        
        // Verificar que la sesión SSN se haya establecido (puede fallar si no hay credenciales configuradas)
        // Solo verificamos si las credenciales están configuradas
        if (config('services.ssn.username') && config('services.ssn.cia') && config('services.ssn.password')) {
            $this->assertTrue(session()->has('ssn_token'));
            $this->assertTrue(session()->has('ssn_expiration'));
        }
    }

    /** @test */
    public function dashboard_shows_ssn_connection_status()
    {
        // Arrange
        $user = User::factory()->create();
        
        // Simular sesión SSN
        session([
            'ssn_token' => 'mock-token-123',
            'ssn_expiration' => '31/12/2025 23:59:59',
            'ssn_mock' => true,
        ]);

        // Act
        $response = $this->actingAs($user)->get('/dashboard');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('SSN Conectado');
        $response->assertSee('Modo Testing');
        $response->assertSee('31/12/2025 23:59:59');
    }

    /** @test */
    public function dashboard_shows_ssn_not_connected_when_no_token()
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)->get('/dashboard');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('SSN No Conectado');
        $response->assertSee('No se pudo establecer conexión');
    }

    /** @test */
    public function ssn_service_returns_mock_data_when_enabled()
    {
        // Arrange
        $ssnService = app(SSNAuthService::class);

        // Act
        $result = $ssnService->authenticate('test', '1234', 'password');

        // Assert
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertTrue($result['mock']);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('expiration', $result);
    }

    /** @test */
    public function ssn_token_is_cached_after_successful_authentication()
    {
        // Arrange
        $ssnService = app(SSNAuthService::class);

        // Act
        $result = $ssnService->authenticate('test', '1234', 'password');

        // Assert
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        
        // Verificar que el token esté en cache
        $cachedToken = $ssnService->getCachedToken();
        $this->assertEquals($result['token'], $cachedToken);
    }

    /** @test */
    public function ssn_token_is_cleared_on_logout()
    {
        // Arrange
        $user = User::factory()->create();
        $ssnService = app(SSNAuthService::class);
        
        // Simular token en cache
        $ssnService->cacheToken('test-token', '31/12/2025 23:59:59');

        // Act
        $response = $this->actingAs($user)->post('/logout');

        // Assert
        $response->assertRedirect('/login');
        $this->assertGuest();
        
        // Verificar que el token SSN se haya limpiado
        $this->assertNull($ssnService->getCachedToken());
    }

    /** @test */
    public function ssn_service_validates_token_correctly()
    {
        // Arrange
        $ssnService = app(SSNAuthService::class);

        // Act & Assert
        $this->assertTrue($ssnService->isTokenValid('mock-token'));
        $this->assertFalse($ssnService->isTokenValid(null));
        $this->assertFalse($ssnService->isTokenValid(''));
    }

    /** @test */
    public function ssn_service_returns_token_info()
    {
        // Arrange
        $ssnService = app(SSNAuthService::class);
        $mockToken = config('services.ssn.mock_token');

        // Act
        $info = $ssnService->getTokenInfo($mockToken);

        // Assert
        $this->assertNotNull($info);
        $this->assertArrayHasKey('token', $info);
        $this->assertArrayHasKey('expiration', $info);
        $this->assertTrue($info['mock']);
        $this->assertEquals($mockToken, $info['token']);
    }
} 