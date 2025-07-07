<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Domain\Models\Presentation;
use App\Domain\Models\MonthlyStock;
use App\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MonthlyPresentationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear usuario para los tests
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
    }

    /** @test */
    public function it_can_create_monthly_presentation()
    {
        $presentation = Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'VACIO',
        ]);

        $this->assertInstanceOf(Presentation::class, $presentation);
        $this->assertEquals('1234', $presentation->codigo_compania);
        $this->assertEquals('2025-01', $presentation->cronograma);
        $this->assertEquals('Mensual', $presentation->tipo_entrega);
        $this->assertEquals('VACIO', $presentation->estado);
    }

    /** @test */
    public function it_can_determine_if_presentation_is_monthly()
    {
        $presentation = Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'VACIO',
        ]);

        $this->assertTrue($presentation->isMensual());
        $this->assertFalse($presentation->isSemanal());
    }

    /** @test */
    public function it_can_check_presentation_states()
    {
        $presentation = Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'VACIO',
        ]);

        $this->assertTrue($presentation->isVacio());
        $this->assertFalse($presentation->isCargado());
        $this->assertFalse($presentation->isPresentado());

        // Cambiar estado
        $presentation->update(['estado' => 'CARGADO']);
        $this->assertTrue($presentation->isCargado());
        $this->assertFalse($presentation->isVacio());

        $presentation->update(['estado' => 'PRESENTADO']);
        $this->assertTrue($presentation->isPresentado());
    }

    /** @test */
    public function it_can_generate_ssn_json_for_monthly_presentation()
    {
        $presentation = Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'VACIO',
        ]);

        // Crear stocks de ejemplo
        MonthlyStock::create([
            'presentation_id' => $presentation->id,
            'tipo' => 'I',
            'tipo_especie' => 'TP',
            'codigo_especie' => 'TP001',
            'cantidad_devengado_especies' => 1000000.000000,
            'cantidad_percibido_especies' => 1000000.000000,
            'codigo_afectacion' => '999',
            'tipo_valuacion' => 'V',
            'con_cotizacion' => true,
            'libre_disponibilidad' => true,
            'emisor_grupo_economico' => false,
            'emisor_art_ret' => false,
            'prevision_desvalorizacion' => 0,
            'valor_contable' => 1050000,
            'en_custodia' => true,
            'financiera' => true,
            'valor_financiero' => 1050000,
        ]);

        $json = $presentation->getSsnJson();

        $this->assertIsArray($json);
        $this->assertEquals('1234', $json['codigoCompania']);
        $this->assertEquals('2025-01', $json['cronograma']);
        $this->assertEquals('Mensual', $json['tipoEntrega']);
        $this->assertArrayHasKey('stocks', $json);
        $this->assertCount(1, $json['stocks']);
        $this->assertEquals('I', $json['stocks'][0]['tipo']);
    }

    /** @test */
    public function it_can_use_scopes_for_monthly_presentations()
    {
        // Crear presentaciones semanales y mensuales
        Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Semanal',
            'estado' => 'VACIO',
        ]);

        Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'VACIO',
        ]);

        Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-02',
            'tipo_entrega' => 'Mensual',
            'estado' => 'CARGADO',
        ]);

        $monthlyPresentations = Presentation::mensual()->get();
        $this->assertCount(2, $monthlyPresentations);

        $loadedPresentations = Presentation::estado('CARGADO')->get();
        $this->assertCount(1, $loadedPresentations);
    }

    /** @test */
    public function it_can_have_unique_constraint_for_company_period_type()
    {
        // Crear primera presentación
        Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'VACIO',
        ]);

        // Intentar crear duplicado
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'VACIO',
        ]);
    }

    /** @test */
    public function it_can_track_presentation_timestamps()
    {
        $presentation = Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'VACIO',
        ]);

        $this->assertNotNull($presentation->created_at);
        $this->assertNotNull($presentation->updated_at);

        // Simular presentación
        $presentation->update([
            'estado' => 'PRESENTADO',
            'presented_at' => now(),
            'ssn_response_id' => 'SSN-123',
            'ssn_response_data' => ['status' => 'success'],
        ]);

        $this->assertNotNull($presentation->presented_at);
        $this->assertEquals('SSN-123', $presentation->ssn_response_id);
        $this->assertEquals(['status' => 'success'], $presentation->ssn_response_data);
    }
}
