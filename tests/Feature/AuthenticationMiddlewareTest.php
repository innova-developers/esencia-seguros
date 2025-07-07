<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthenticationMiddlewareTest extends TestCase
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
    public function it_redirects_unauthenticated_users_to_login()
    {
        $protectedRoutes = [
            '/dashboard',
            '/monthly-presentations',
            '/monthly-presentations/create',
            '/weekly-presentations',
            '/weekly-presentations/create',
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }
    }

    /** @test */
    public function it_allows_authenticated_users_to_access_protected_routes()
    {
        $this->actingAs($this->user);

        $protectedRoutes = [
            '/dashboard',
            '/monthly-presentations',
            '/monthly-presentations/create',
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function it_redirects_authenticated_users_away_from_login()
    {
        $this->actingAs($this->user);

        $response = $this->get('/login');
        
        $response->assertRedirect('/dashboard');
    }

    /** @test */
    public function it_handles_logout_properly()
    {
        $this->actingAs($this->user);

        // Verificar que está autenticado
        $this->assertAuthenticated();

        // Hacer logout
        $response = $this->post('/logout');
        
        $response->assertRedirect('/login');
        
        // Verificar que ya no está autenticado
        $this->assertGuest();
    }

    /** @test */
    public function it_requires_csrf_token_for_logout()
    {
        $this->actingAs($this->user);

        // Intentar logout sin CSRF token
        $response = $this->withoutMiddleware()->post('/logout');
        
        // Debería fallar por falta de CSRF token
        $response->assertStatus(419);
    }

    /** @test */
    public function it_redirects_root_to_dashboard_when_authenticated()
    {
        $this->actingAs($this->user);

        $response = $this->get('/');
        
        $response->assertRedirect('/dashboard');
    }

    /** @test */
    public function it_redirects_root_to_login_when_not_authenticated()
    {
        $response = $this->get('/');
        
        $response->assertRedirect('/login');
    }

    /** @test */
    public function it_maintains_session_after_authentication()
    {
        $this->actingAs($this->user);

        // Hacer varias requests
        $this->get('/dashboard');
        $this->get('/monthly-presentations');
        $this->get('/dashboard');

        // Verificar que sigue autenticado
        $this->assertAuthenticated();
        $this->assertAuthenticatedAs($this->user);
    }

    /** @test */
    public function it_handles_session_timeout()
    {
        $this->actingAs($this->user);

        // Simular expiración de sesión
        $this->app['session']->flush();

        $response = $this->get('/dashboard');
        
        $response->assertRedirect('/login');
    }
}
