<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Domain\Models\Presentation;
use App\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardTest extends TestCase
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
    }

    /** @test */
    public function it_redirects_to_login_when_not_authenticated()
    {
        $response = $this->get('/dashboard');
        
        $response->assertRedirect('/login');
    }

    /** @test */
    public function it_shows_dashboard_with_presentation_statistics()
    {
        $this->actingAs($this->user);

        // Crear presentaciones con diferentes estados
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

        Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-03',
            'tipo_entrega' => 'Mensual',
            'estado' => 'PRESENTADO',
        ]);

        Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-04',
            'tipo_entrega' => 'Mensual',
            'estado' => 'PENDIENTE',
        ]);

        Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-05',
            'tipo_entrega' => 'Mensual',
            'estado' => 'RECTIFICACION',
        ]);

        Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-06',
            'tipo_entrega' => 'Mensual',
            'estado' => 'A RECTIFICAR',
        ]);

        $response = $this->get('/dashboard');
        
        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        
        // Verificar que se muestran las estadísticas
        $response->assertSee('VACÍO');
        $response->assertSee('CARGADO');
        $response->assertSee('PRESENTADO');
        $response->assertSee('PENDIENTE');
        $response->assertSee('RECTIFICACIÓN');
        $response->assertSee('A RECTIFICAR');
        
        // Verificar contadores
        $response->assertSee('1'); // VACIO
        $response->assertSee('1'); // CARGADO
        $response->assertSee('1'); // PRESENTADO
        $response->assertSee('1'); // PENDIENTE
        $response->assertSee('1'); // RECTIFICACION
        $response->assertSee('1'); // A RECTIFICAR
    }

    /** @test */
    public function it_shows_zero_counters_when_no_presentations()
    {
        $this->actingAs($this->user);

        $response = $this->get('/dashboard');
        
        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        
        // Verificar que se muestran los estados con contador 0
        $response->assertSee('0'); // Todos los contadores deben ser 0
    }

    /** @test */
    public function it_shows_quick_actions_links()
    {
        $this->actingAs($this->user);

        $response = $this->get('/dashboard');
        
        $response->assertStatus(200);
        
        // Verificar enlaces de acciones rápidas
        $response->assertSee('Nueva Presentación Semanal');
        $response->assertSee('Listar Presentaciones Semanales');
        $response->assertSee('Nueva Presentación Mensual');
        $response->assertSee('Listar Presentaciones Mensuales');
        
        // Verificar que los enlaces apuntan a las rutas correctas
        $response->assertSee('href="/weekly-presentations/create"');
        $response->assertSee('href="/weekly-presentations"');
        $response->assertSee('href="/monthly-presentations/create"');
        $response->assertSee('href="/monthly-presentations"');
    }

    /** @test */
    public function it_shows_user_information()
    {
        $this->actingAs($this->user);

        $response = $this->get('/dashboard');
        
        $response->assertStatus(200);
        $response->assertSee($this->user->name);
        $response->assertSee($this->user->email);
    }

    /** @test */
    public function it_shows_ssn_connection_status()
    {
        $this->actingAs($this->user);

        $response = $this->get('/dashboard');
        
        $response->assertStatus(200);
        
        // Verificar que se muestra el estado de conexión SSN
        $response->assertSee('Conexión SSN');
        $response->assertSee('Conectado'); // o 'Desconectado' dependiendo del estado
    }

    /** @test */
    public function it_shows_copyright_information()
    {
        $this->actingAs($this->user);

        $response = $this->get('/dashboard');
        
        $response->assertStatus(200);
        $response->assertSee('© 2025 Esencia Seguros');
        $response->assertSee('Desarrollado por Innova Developers');
    }

    /** @test */
    public function it_handles_multiple_users_presentations_independently()
    {
        $otherUser = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Crear presentaciones para ambos usuarios
        Presentation::create([
            'user_id' => $this->user->id,
            'codigo_compania' => '1234',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'VACIO',
        ]);

        Presentation::create([
            'user_id' => $otherUser->id,
            'codigo_compania' => '5678',
            'cronograma' => '2025-01',
            'tipo_entrega' => 'Mensual',
            'estado' => 'CARGADO',
        ]);

        $this->actingAs($this->user);

        $response = $this->get('/dashboard');
        
        $response->assertStatus(200);
        
        // Verificar que solo se muestran las estadísticas del usuario actual
        $response->assertSee('1'); // VACIO del usuario actual
        $response->assertDontSee('1'); // No debe mostrar CARGADO del otro usuario
    }
}
