<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Domain\Models\Presentation;
use App\Domain\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SimpleMonthlyPresentationTest extends TestCase
{
    use WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear usuario sin RefreshDatabase para evitar conflictos
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    /** @test */
    public function it_redirects_to_login_when_not_authenticated()
    {
        $response = $this->get('/monthly-presentations/list');
        
        $response->assertRedirect('/login');
    }

    /** @test */
    public function it_can_create_monthly_presentation_via_api()
    {
        $this->actingAs($this->user);

        $file = \Illuminate\Http\UploadedFile::fake()->create('stocks.xlsx', 100);

        $data = [
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'file' => $file,
        ];

        $response = $this->post('/monthly-presentations/store', $data);
        
        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('presentations', [
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'VACIO',
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->actingAs($this->user);

        $response = $this->post('/monthly-presentations/store', []);
        
        $response->assertSessionHasErrors(['codigo_compania', 'cronograma']);
    }

    /** @test */
    public function it_prevents_duplicate_presentations()
    {
        $this->actingAs($this->user);

        $file = \Illuminate\Http\UploadedFile::fake()->create('stocks.xlsx', 100);

        // Crear primera presentación
        $data = [
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'file' => $file,
        ];

        $this->post('/monthly-presentations/store', $data);

        // Intentar crear duplicado
        $file2 = \Illuminate\Http\UploadedFile::fake()->create('stocks2.xlsx', 100);
        $data2 = [
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'file' => $file2,
        ];

        $response = $this->post('/monthly-presentations/store', $data2);
        
        $response->assertSessionHasErrors(['cronograma']);
    }

    /** @test */
    public function it_can_access_dashboard_when_authenticated()
    {
        $this->actingAs($this->user);

        $response = $this->get('/dashboard');
        
        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_logout_successfully()
    {
        $this->actingAs($this->user);

        $response = $this->post('/logout');
        
        $response->assertRedirect('/login');
        $this->assertGuest();
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

        $file = \Illuminate\Http\UploadedFile::fake()->create('stocks.xlsx', 100);
        $data = [
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'file' => $file,
        ];

        $response = $this->post('/monthly-presentations/store', $data);
        $response->assertSessionHasErrors(['cronograma']);
    }
} 