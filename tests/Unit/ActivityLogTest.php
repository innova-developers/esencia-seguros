<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Domain\Models\ActivityLog;
use App\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityLogTest extends TestCase
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
    public function it_can_create_activity_log()
    {
        $log = ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'LOGIN',
            'description' => 'Usuario inició sesión',
            'status' => 'success',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 Test Browser',
        ]);

        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertEquals($this->user->id, $log->user_id);
        $this->assertEquals('LOGIN', $log->action);
        $this->assertEquals('Usuario inició sesión', $log->description);
        $this->assertEquals('success', $log->status);
        $this->assertEquals('127.0.0.1', $log->ip_address);
        $this->assertEquals('Mozilla/5.0 Test Browser', $log->user_agent);
    }

    /** @test */
    public function it_can_use_scopes_for_filtering()
    {
        ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'LOGIN',
            'description' => 'Login exitoso',
            'status' => 'success',
        ]);

        ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'LOGIN',
            'description' => 'Login exitoso 2',
            'status' => 'success',
        ]);

        ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'LOGOUT',
            'description' => 'Logout exitoso',
            'status' => 'success',
        ]);

        // Probar scope por acción
        $loginLogs = ActivityLog::action('LOGIN')->get();
        $this->assertCount(2, $loginLogs);

        $logoutLogs = ActivityLog::action('LOGOUT')->get();
        $this->assertCount(1, $logoutLogs);

        // Probar scope por estado
        $successLogs = ActivityLog::status('success')->get();
        $this->assertCount(3, $successLogs);

        $errorLogs = ActivityLog::status('error')->get();
        $this->assertCount(0, $errorLogs);

        // Probar scope por usuario
        $userLogs = ActivityLog::byUser($this->user->id)->get();
        $this->assertCount(3, $userLogs);
    }

    /** @test */
    public function it_can_use_combined_scopes()
    {
        ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'LOGIN',
            'description' => 'Login exitoso',
            'status' => 'success',
        ]);

        ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'LOGIN',
            'description' => 'Login fallido',
            'status' => 'error',
        ]);

        $successfulLogins = ActivityLog::action('LOGIN')->status('success')->get();
        $this->assertCount(1, $successfulLogins);
        $this->assertEquals('success', $successfulLogins->first()->status);
    }

    /** @test */
    public function it_can_use_recent_scope()
    {
        // Crear logs con diferentes fechas
        $oldLog = ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'LOGIN',
            'description' => 'Login antiguo',
            'status' => 'success',
            'created_at' => now()->subDays(10),
        ]);

        $recentLog = ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'LOGIN',
            'description' => 'Login reciente',
            'status' => 'success',
            'created_at' => now()->subHours(2),
        ]);

        $recentLogs = ActivityLog::recent(7)->get();
        $this->assertCount(2, $recentLogs); // Ambos logs están dentro de 7 días
        $this->assertTrue($recentLogs->contains($recentLog));
        $this->assertTrue($recentLogs->contains($oldLog));
    }

    /** @test */
    public function it_can_get_formatted_timestamp()
    {
        $log = ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'LOGIN',
            'description' => 'Test log',
            'status' => 'success',
        ]);

        $formattedDate = $log->getFormattedDate();
        $this->assertIsString($formattedDate);
        $this->assertNotEmpty($formattedDate);
    }

    /** @test */
    public function it_can_get_status_badge_class()
    {
        $successLog = ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'LOGIN',
            'description' => 'Success log',
            'status' => 'success',
        ]);

        $errorLog = ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'LOGIN',
            'description' => 'Error log',
            'status' => 'error',
        ]);

        $this->assertEquals('badge bg-success', $successLog->getStatusBadgeClass());
        $this->assertEquals('badge bg-danger', $errorLog->getStatusBadgeClass());
    }

    /** @test */
    public function it_can_get_action_icon()
    {
        $loginLog = ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'login',
            'description' => 'Login log',
            'status' => 'success',
        ]);

        $logoutLog = ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'logout',
            'description' => 'Logout log',
            'status' => 'success',
        ]);

        $this->assertEquals('fas fa-sign-in-alt', $loginLog->getActionIcon());
        $this->assertEquals('fas fa-sign-out-alt', $logoutLog->getActionIcon());
    }

    /** @test */
    public function it_can_get_user_relationship()
    {
        $log = ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'LOGIN',
            'description' => 'Test log',
            'status' => 'success',
        ]);

        $user = $log->user;
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($this->user->id, $user->id);
        $this->assertEquals($this->user->name, $user->name);
        $this->assertEquals($this->user->email, $user->email);
    }

    /** @test */
    public function it_can_handle_null_user_agent_and_ip()
    {
        $log = ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'LOGIN',
            'description' => 'Test log',
            'status' => 'success',
            'ip_address' => null,
            'user_agent' => null,
        ]);

        $this->assertNull($log->ip_address);
        $this->assertNull($log->user_agent);
    }

    /** @test */
    public function it_can_use_default_values()
    {
        $log = ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'LOGIN',
            'description' => 'Test log',
            'status' => 'success',
        ]);

        $this->assertNotNull($log->created_at);
        $this->assertNotNull($log->updated_at);
        $this->assertNull($log->ip_address);
        $this->assertNull($log->user_agent);
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $log = ActivityLog::create([
            'user_id' => $this->user->id,
            'action' => 'LOGIN',
            'description' => 'Test log',
            'status' => 'success',
        ]);

        $logId = $log->id;
        
        $log->delete();
        
        $this->assertSoftDeleted('activity_logs', ['id' => $logId]);
        
        // Debería seguir existiendo en la base de datos
        $this->assertDatabaseHas('activity_logs', ['id' => $logId]);
    }
}
