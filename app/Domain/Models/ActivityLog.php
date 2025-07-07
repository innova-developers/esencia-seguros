<?php

namespace App\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class ActivityLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'description',
        'details',
        'ip_address',
        'user_agent',
        'status',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes para filtrado
    public function scopeAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeModule($query, $module)
    {
        return $query->where('module', $module);
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Métodos de formato
    public function getFormattedDate(): string
    {
        return $this->created_at->format('d/m/Y H:i:s');
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'success' => 'badge bg-success',
            'error' => 'badge bg-danger',
            'warning' => 'badge bg-warning',
            'info' => 'badge bg-info',
            default => 'badge bg-secondary',
        };
    }

    public function getActionIcon(): string
    {
        return match($this->action) {
            'login' => 'fas fa-sign-in-alt',
            'logout' => 'fas fa-sign-out-alt',
            'create' => 'fas fa-plus',
            'update' => 'fas fa-edit',
            'delete' => 'fas fa-trash',
            'view' => 'fas fa-eye',
            'download' => 'fas fa-download',
            'upload' => 'fas fa-upload',
            'send' => 'fas fa-paper-plane',
            'validate' => 'fas fa-check',
            default => 'fas fa-info-circle',
        };
    }

    public static function log(
        string $action,
        string $description,
        ?string $module = null,
        ?int $userId = null,
        array $details = [],
        string $status = 'info'
    ): self {
        return self::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'details' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'status' => $status,
        ]);
    }
} 