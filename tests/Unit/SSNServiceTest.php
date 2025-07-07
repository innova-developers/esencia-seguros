<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SSNService;
use App\Domain\Models\Presentation;
use App\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class SSNServiceTest extends TestCase
{
    use RefreshDatabase;

    private SSNService $ssnService;
    private User $user;
    private Presentation $presentation;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->ssnService = new SSNService();
        
        // Crear usuario y presentación para los tests
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->presentation = Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'CARGADO',
        ]);
    }

    /** @test */
    public function it_uses_mock_mode_when_ssn_mock_enabled()
    {
        Config::set('ssn.mock_enabled', true);
        
        $service = new SSNService();
        $this->assertTrue($service->isAvailable());
    }

    /** @test */
    public function it_uses_real_mode_when_ssn_mock_disabled()
    {
        Config::set('ssn.mock_enabled', false);
        Config::set('app.env', 'production');
        Config::set('ssn.base_url_production', 'https://api.ssn.gov.ar');
        
        $service = new SSNService();
        $this->assertFalse($service->isAvailable()); // Sin token, no está disponible
    }

    /** @test */
    public function it_sends_monthly_presentation_successfully_in_mock_mode()
    {
        Config::set('ssn.mock_enabled', true);
        
        $result = $this->ssnService->sendMonthlyPresentation($this->presentation);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(200, $result['status']);
    }

    /** @test */
    public function it_sends_monthly_presentation_successfully_in_real_mode()
    {
        Config::set('ssn.mock_enabled', false);
        Config::set('ssn.base_url_production', 'https://api.ssn.gov.ar');
        
        Http::fake([
            'https://api.ssn.gov.ar/inv/entregaMensual*' => Http::response([
                'success' => true,
                'response_id' => 'SSN-REAL-456',
                'message' => 'Presentación procesada correctamente',
                'status' => 'PRESENTADO'
            ], 200)
        ]);
        
        $result = $this->ssnService->sendMonthlyPresentation($this->presentation);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(200, $result['status']);
    }

    /** @test */
    public function it_handles_ssn_api_error_in_real_mode()
    {
        Config::set('ssn.mock_enabled', false);
        Config::set('ssn.base_url_production', 'https://api.ssn.gov.ar');
        
        Http::fake([
            'https://api.ssn.gov.ar/api/inv/entregaMensual' => Http::response([
                'success' => false,
                'error' => 'Error de validación SSN',
                'details' => 'Datos incompletos'
            ], 400)
        ]);
        
        $result = $this->ssnService->sendMonthlyPresentation($this->presentation);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Error de validación', $result['error']);
    }

    /** @test */
    public function it_handles_network_error_in_real_mode()
    {
        Config::set('ssn.mock_enabled', false);
        Config::set('ssn.base_url_production', 'https://api.ssn.gov.ar');
        
        Http::fake([
            'https://api.ssn.gov.ar/api/inv/entregaMensual' => Http::response(null, 500)
        ]);
        
        $result = $this->ssnService->sendMonthlyPresentation($this->presentation);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Error', $result['error']);
    }

    /** @test */
    public function it_confirms_monthly_presentation_successfully()
    {
        Config::set('ssn.mock_enabled', true);
        
        $result = $this->ssnService->confirmMonthlyPresentation($this->presentation);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(200, $result['status']);
    }

    /** @test */
    public function it_queries_presentation_status_successfully()
    {
        Config::set('ssn.mock_enabled', true);
        
        $result = $this->ssnService->getPresentationStatus('1234', '2025-01', 'Mensual');
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(200, $result['status']);
    }

    /** @test */
    public function it_requests_rectification_successfully()
    {
        Config::set('ssn.mock_enabled', true);
        
        $result = $this->ssnService->requestRectification($this->presentation);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(200, $result['status']);
    }

    /** @test */
    public function it_handles_missing_configuration_gracefully()
    {
        Config::set('ssn.mock_enabled', false);
        Config::set('ssn.base_url_production', null);
        
        $service = new SSNService();
        $this->assertFalse($service->isAvailable()); // Sin configuración, no está disponible
    }

    /** @test */
    public function it_gets_service_info()
    {
        $info = $this->ssnService->getServiceInfo();
        
        $this->assertIsArray($info);
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('version', $info);
        $this->assertArrayHasKey('status', $info);
    }

    /** @test */
    public function it_sends_weekly_presentation_successfully()
    {
        $weeklyPresentation = Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-W01',
            'tipo_entrega' => 'Semanal',
            'estado' => 'CARGADO',
        ]);

        Config::set('ssn.mock_enabled', true);
        
        $result = $this->ssnService->sendWeeklyPresentation($weeklyPresentation);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(200, $result['status']);
    }

    /** @test */
    public function it_confirms_weekly_presentation_successfully()
    {
        $weeklyPresentation = Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-W01',
            'tipo_entrega' => 'Semanal',
            'estado' => 'CARGADO',
        ]);

        Config::set('ssn.mock_enabled', true);
        
        $result = $this->ssnService->confirmWeeklyPresentation($weeklyPresentation);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(200, $result['status']);
    }
}
