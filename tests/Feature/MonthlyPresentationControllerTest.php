<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Domain\Models\Presentation;
use App\Domain\Models\User;
use App\Services\SSNService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Mockery;

class MonthlyPresentationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        Storage::fake('local');
    }

    /** @test */
    public function it_redirects_to_login_when_not_authenticated()
    {
        $response = $this->get('/monthly-presentations/list');
        
        $response->assertRedirect('/login');
    }

    /** @test */
    public function it_shows_monthly_presentations_index()
    {
        $this->actingAs($this->user);

        $response = $this->get('/monthly-presentations/list');
        
        $response->assertStatus(200);
        $response->assertViewIs('monthly-presentations.index');
        $response->assertViewHas('presentations');
    }

    /** @test */
    public function it_shows_create_monthly_presentation_form()
    {
        $this->actingAs($this->user);

        $response = $this->get('/monthly-presentations/new');
        
        $response->assertStatus(200);
        $response->assertViewIs('monthly-presentations.create');
    }

    /** @test */
    public function it_creates_monthly_presentation_successfully()
    {
        $this->actingAs($this->user);

        $data = [
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
        ];

        $response = $this->post('/monthly-presentations/store', $data);
        
        $response->assertRedirect('/monthly-presentations/list');
        $response->assertSessionHas('success', 'Presentación mensual creada exitosamente');
        
        $this->assertDatabaseHas('presentations', [
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'VACIO',
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating()
    {
        $this->actingAs($this->user);

        $response = $this->post('/monthly-presentations/store', []);
        
        $response->assertSessionHasErrors(['codigo_compania', 'cronograma']);
    }

    /** @test */
    public function it_validates_cronograma_format()
    {
        $this->actingAs($this->user);

        $data = [
            'codigo_compania' => '1234',
            'cronograma' => 'invalid-format',
        ];

        $response = $this->post('/monthly-presentations/store', $data);
        
        $response->assertSessionHasErrors(['cronograma']);
    }

    /** @test */
    public function it_prevents_duplicate_presentations()
    {
        $this->actingAs($this->user);

        // Crear primera presentación
        Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'VACIO',
        ]);

        // Intentar crear duplicado
        $data = [
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
        ];

        $response = $this->post('/monthly-presentations/store', $data);
        
        $response->assertSessionHasErrors(['cronograma']);
    }

    /** @test */
    public function it_shows_monthly_presentation_details()
    {
        $this->actingAs($this->user);

        $presentation = Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'VACIO',
        ]);

        $response = $this->get("/monthly-presentations/{$presentation->id}");
        
        $response->assertStatus(200);
        $response->assertViewIs('monthly-presentations.show');
        $response->assertViewHas('presentation', $presentation);
    }

    /** @test */
    public function it_prevents_access_to_other_users_presentations()
    {
        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => bcrypt('password123'),
        ]);

        $presentation = Presentation::create([
            'user_id' => $otherUser->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'VACIO',
        ]);

        $this->actingAs($this->user);

        $response = $this->get("/monthly-presentations/{$presentation->id}");
        
        $response->assertStatus(404);
    }

    /** @test */
    public function it_processes_excel_file_successfully()
    {
        $this->actingAs($this->user);

        $presentation = Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'VACIO',
        ]);

        $file = UploadedFile::fake()->create('stocks.xlsx', 100);

        $response = $this->post("/monthly-presentations/{$presentation->id}/process", [
            'excel_file' => $file,
        ]);
        
        $response->assertRedirect("/monthly-presentations/{$presentation->id}");
        $response->assertSessionHas('success', 'Archivo Excel procesado exitosamente. La presentación está lista para ser enviada a SSN.');
        
        // Verificar que se actualizó el estado
        $this->assertDatabaseHas('presentations', [
            'id' => $presentation->id,
            'estado' => 'CARGADO',
        ]);
    }

    /** @test */
    public function it_validates_excel_file_upload()
    {
        $this->actingAs($this->user);

        $presentation = Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'VACIO',
        ]);

        // Sin archivo
        $response = $this->post("/monthly-presentations/{$presentation->id}/process", []);
        $response->assertSessionHasErrors(['excel_file']);

        // Archivo inválido
        $invalidFile = UploadedFile::fake()->create('document.txt', 100);
        $response = $this->post("/monthly-presentations/{$presentation->id}/process", [
            'excel_file' => $invalidFile,
        ]);
        $response->assertSessionHasErrors(['excel_file']);
    }

    /** @test */
    public function it_sends_presentation_to_ssn_successfully()
    {
        $this->actingAs($this->user);

        $presentation = Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'CARGADO',
        ]);

        $response = $this->post("/monthly-presentations/{$presentation->id}/send-ssn");
        
        $response->assertRedirect("/monthly-presentations/{$presentation->id}");
        $response->assertSessionHas('success', 'Presentación enviada a SSN exitosamente.');
        
        // Verificar que se actualizó el estado
        $this->assertDatabaseHas('presentations', [
            'id' => $presentation->id,
            'estado' => 'PRESENTADO',
        ]);
    }

    /** @test */
    public function it_handles_ssn_error_when_sending()
    {
        $this->actingAs($this->user);

        $presentation = Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'CARGADO',
        ]);

        // Mock del servicio SSN para simular error
        $this->mock(SSNService::class, function ($mock) {
            $mock->shouldReceive('sendMonthlyPresentation')
                ->once()
                ->andReturn([
                    'success' => false,
                    'error' => 'Error de validación SSN',
                ]);
        });

        $response = $this->post("/monthly-presentations/{$presentation->id}/send-ssn");
        
        $response->assertRedirect("/monthly-presentations/{$presentation->id}");
        $response->assertSessionHas('error', 'Error al enviar presentación: Error de validación SSN');
        
        // Verificar que NO se actualizó el estado
        $this->assertDatabaseHas('presentations', [
            'id' => $presentation->id,
            'estado' => 'CARGADO',
        ]);
    }

    /** @test */
    public function it_only_allows_sending_loaded_presentations()
    {
        $this->actingAs($this->user);

        $presentation = Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'VACIO', // No cargado
        ]);

        $response = $this->post("/monthly-presentations/{$presentation->id}/send-ssn");
        
        $response->assertRedirect("/monthly-presentations/{$presentation->id}");
        $response->assertSessionHas('error', 'Solo se pueden enviar presentaciones cargadas');
    }

    /** @test */
    public function it_logs_activity_for_all_operations()
    {
        $this->actingAs($this->user);

        // Crear presentación
        $response = $this->post('/monthly-presentations/store', [
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'CREATE_MONTHLY_PRESENTATION',
            'description' => 'Creación de presentación mensual',
            'status' => 'success'
        ]);

        $presentation = Presentation::where('codigo_compania', '1234')->first();

        // Procesar archivo
        $file = UploadedFile::fake()->create('stocks.xlsx', 100);
        $this->post("/monthly-presentations/{$presentation->id}/process", [
            'excel_file' => $file,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'PROCESS_MONTHLY_PRESENTATION',
            'description' => 'Procesamiento de archivo Excel para presentación mensual',
            'status' => 'success'
        ]);

        // Actualizar estado a cargado para poder enviar
        $presentation->update(['estado' => 'CARGADO']);

        // Enviar a SSN
        Config::set('services.ssn.mock_enabled', true);
        $this->post("/monthly-presentations/{$presentation->id}/send-ssn");

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->user->id,
            'action' => 'SEND_MONTHLY_PRESENTATION',
            'description' => 'Envío de presentación mensual a SSN',
            'status' => 'success'
        ]);
    }

    /** @test */
    public function it_prevents_creating_presentation_for_blocked_month()
    {
        $this->actingAs($this->user);

        // Crear presentación bloqueante
        Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'PRESENTADO',
        ]);

        $data = [
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
        ];

        $response = $this->post('/monthly-presentations/store', $data);
        $response->assertSessionHasErrors(['cronograma']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
