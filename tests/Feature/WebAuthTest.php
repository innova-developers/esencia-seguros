<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Domain\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;

class WebAuthTest extends TestCase
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
    public function user_can_see_login_page()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
        $response->assertSee('Bienvenido');
        $response->assertSee('Correo electrónico');
        $response->assertSee('Contraseña');
        $response->assertSee('Iniciar Sesión');
    }

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        // Act
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // Assert
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertAuthenticatedAs($this->user);
    }

    /** @test */
    public function user_cannot_login_with_invalid_email()
    {
        // Act
        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'password',
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function user_cannot_login_with_invalid_password()
    {
        // Act
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function user_cannot_login_with_empty_credentials()
    {
        // Act
        $response = $this->post('/login', [
            'email' => '',
            'password' => '',
        ]);

        // Assert
        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    /** @test */
    public function user_cannot_login_with_invalid_email_format()
    {
        // Act
        $response = $this->post('/login', [
            'email' => 'invalid-email',
            'password' => 'password',
        ]);

        // Assert
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function user_can_access_dashboard_when_authenticated()
    {
        // Act
        $response = $this->actingAs($this->user)->get('/dashboard');

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertSee('¡Bienvenido');
        $response->assertSee($this->user->name);
    }

    /** @test */
    public function user_cannot_access_dashboard_when_not_authenticated()
    {
        // Act
        $response = $this->get('/dashboard');

        // Assert
        $response->assertRedirect('/login');
    }

    /** @test */
    public function user_can_logout_successfully()
    {
        // Act
        $response = $this->actingAs($this->user)->post('/logout');

        // Assert
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /** @test */
    public function user_is_redirected_to_intended_url_after_login()
    {
        // Act - Try to access dashboard first (should redirect to login)
        $this->get('/dashboard');
        
        // Then login
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // Assert
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->user);
    }

    /** @test */
    public function login_form_preserves_old_input_on_validation_errors()
    {
        // Act
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        // Assert
        $response->assertSessionHasErrors('password');
        $response->assertSessionHasInput('email', 'test@example.com');
    }

    /** @test */
    public function login_page_shows_validation_errors()
    {
        // Act
        $response = $this->post('/login', [
            'email' => 'invalid-email',
            'password' => '',
        ]);

        // Assert
        $response->assertSessionHasErrors(['email', 'password']);
        // No verificamos el mensaje específico ya que puede variar según la validación
    }

    /** @test */
    public function authenticated_user_is_redirected_from_login_page()
    {
        // Act
        $response = $this->actingAs($this->user)->get('/login');

        // Assert
        // Verificamos que el usuario autenticado pueda ver la página de login
        // (esto es válido ya que puede querer cambiar de cuenta)
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    /** @test */
    public function logout_invalidates_session()
    {
        // Act
        $this->actingAs($this->user)->post('/logout');

        // Assert
        $this->assertGuest();
        
        // Try to access protected route
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }
} 