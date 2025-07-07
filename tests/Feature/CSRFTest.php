<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CSRFTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_generates_csrf_token()
    {
        $response = $this->get('/login');
        
        $response->assertStatus(200);
        $response->assertSee('_token');
    }

    /** @test */
    public function it_validates_csrf_token_on_login()
    {
        // Obtener el token CSRF
        $response = $this->get('/login');
        $token = $this->extractCSRFToken($response->getContent());
        
        // Intentar hacer login con el token válido
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            '_token' => $token,
        ]);
        
        // Debería fallar por credenciales inválidas, no por CSRF
        $response->assertSessionHasErrors('email');
        $response->assertStatus(302); // Redirect con errores
    }

    /** @test */
    public function it_rejects_request_without_csrf_token()
    {
        $response = $this->withoutMiddleware()->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        
        $response->assertStatus(419);
    }

    private function extractCSRFToken($html)
    {
        preg_match('/name="_token" value="([^"]+)"/', $html, $matches);
        return $matches[1] ?? null;
    }
}
