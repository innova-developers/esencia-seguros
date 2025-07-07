<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Domain\Models\User;
use App\Domain\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuditSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear usuario de prueba
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    /**
     * @test
     */
    public function it_redirects_to_login_when_not_authenticated()
    {
        $response = $this->get('/audit/logs');
        
        $response->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_shows_audit_logs_page_when_authenticated()
    {
        $response = $this->actingAs($this->user)
            ->get('/audit/logs');
        
        $response->assertStatus(200);
        $response->assertSee('Registros de Auditoría');
        $response->assertSee('Filtros de Búsqueda');
    }

    /**
     * @test
     */
    public function it_shows_audit_logs_with_filters()
    {
        // Crear algunos logs de actividad
        ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'LOGIN',
            'description' => 'Usuario inició sesión',
            'module' => 'auth',
            'ip_address' => '127.0.0.1',
        ]);

        ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'CREATE',
            'description' => 'Creó presentación mensual',
            'module' => 'monthly_presentations',
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/audit/logs');
        
        $response->assertStatus(200);
        $response->assertSee('Usuario inició sesión');
        $response->assertSee('Creó presentación mensual');
        $response->assertSee('auth');
        $response->assertSee('monthly_presentations');
    }

    /**
     * @test
     */
    public function it_filters_logs_by_module()
    {
        // Crear logs de diferentes módulos
        ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'LOGIN',
            'description' => 'Usuario inició sesión',
            'module' => 'auth',
            'ip_address' => '127.0.0.1',
        ]);

        ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'CREATE',
            'description' => 'Creó presentación mensual',
            'module' => 'monthly_presentations',
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/audit/logs?module=auth');
        
        $response->assertStatus(200);
        $response->assertSee('Usuario inició sesión');
        $response->assertDontSee('Creó presentación mensual');
    }

    /**
     * @test
     */
    public function it_filters_logs_by_user()
    {
        $user2 = User::factory()->create(['name' => 'User 2']);

        ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'LOGIN',
            'description' => 'Usuario 1 inició sesión',
            'module' => 'auth',
            'ip_address' => '127.0.0.1',
        ]);

        ActivityLog::create([
            'user_id' => $user2->id,
            'action' => 'LOGIN',
            'description' => 'Usuario 2 inició sesión',
            'module' => 'auth',
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/audit/logs?user_id=' . $user2->id);
        
        $response->assertStatus(200);
        $response->assertSee('Usuario 2 inició sesión');
        $response->assertDontSee('Usuario 1 inició sesión');
    }

    /**
     * @test
     */
    public function it_filters_logs_by_date_range()
    {
        // Crear log con fecha específica
        ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'LOGIN',
            'description' => 'Usuario inició sesión',
            'module' => 'auth',
            'ip_address' => '127.0.0.1',
            'created_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->user)
            ->get('/audit/logs?date_from=' . now()->subDays(10)->format('Y-m-d') . '&date_to=' . now()->format('Y-m-d'));
        
        $response->assertStatus(200);
        $response->assertSee('Usuario inició sesión');
    }

    /**
     * @test
     */
    public function it_shows_pagination_for_large_number_of_logs()
    {
        // Crear más de 50 logs para forzar paginación
        for ($i = 0; $i < 60; $i++) {
            ActivityLog::create([
                'user_id' => $this->user->id,
                'action' => 'LOGIN',
                'description' => "Login número {$i}",
                'module' => 'auth',
                'ip_address' => '127.0.0.1',
            ]);
        }

        $response = $this->actingAs($this->user)
            ->get('/audit/logs');
        
        $response->assertStatus(200);
        $response->assertSee('60 registros encontrados');
        // Verificar que hay paginación
        $response->assertSee('pagination');
    }

    /**
     * @test
     */
    public function it_shows_no_logs_message_when_empty()
    {
        $response = $this->actingAs($this->user)
            ->get('/audit/logs');
        
        $response->assertStatus(200);
        $response->assertSee('No se encontraron registros');
        $response->assertSee('Intenta ajustar los filtros de búsqueda');
    }

    /**
     * @test
     */
    public function it_has_audit_button_in_dashboard()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard');
        
        $response->assertStatus(200);
        $response->assertSee('Ver Registros de Auditoría');
        $response->assertSee('audit/logs');
    }
} 