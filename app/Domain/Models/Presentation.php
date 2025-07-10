<?php

namespace App\Domain\Models;

use App\Domain\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Presentation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'codigo_compania',
        'cronograma',
        'tipo_entrega',
        'version',
        'estado',
        'ssn_response_id',
        'ssn_response_data',
        'presented_at',
        'confirmed_at',
        'rectification_requested_at',
        'rectification_approved_at',
        'original_file_path',
        'json_file_path',
        'original_filename',
        'validation_errors',
        'notes',
    ];

    protected $casts = [
        'ssn_response_data' => 'array',
        'validation_errors' => 'array',
        'presented_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'rectification_requested_at' => 'datetime',
        'rectification_approved_at' => 'datetime',
    ];

    // Estados disponibles
    const ESTADO_VACIO = 'VACIO';
    const ESTADO_CARGADO = 'CARGADO';
    const ESTADO_PRESENTADO = 'PRESENTADO';
    const ESTADO_RECTIFICACION_PENDIENTE = 'RECTIFICACION_PENDIENTE';
    const ESTADO_A_RECTIFICAR = 'A_RECTIFICAR';

    // Tipos de entrega
    const TIPO_SEMANAL = 'Semanal';
    const TIPO_MENSUAL = 'Mensual';

    /**
     * Relación con el usuario que creó la presentación
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con las operaciones semanales
     */
    public function weeklyOperations(): HasMany
    {
        return $this->hasMany(WeeklyOperation::class);
    }

    /**
     * Relación con los stocks mensuales
     */
    public function monthlyStocks(): HasMany
    {
        return $this->hasMany(MonthlyStock::class);
    }

    /**
     * Verificar si la presentación está vacía
     */
    public function isVacio(): bool
    {
        return $this->estado === self::ESTADO_VACIO;
    }

    /**
     * Verificar si la presentación está cargada
     */
    public function isCargado(): bool
    {
        return $this->estado === self::ESTADO_CARGADO;
    }

    /**
     * Verificar si la presentación está presentada
     */
    public function isPresentado(): bool
    {
        return $this->estado === self::ESTADO_PRESENTADO;
    }

    /**
     * Verificar si la presentación tiene rectificación pendiente
     */
    public function isRectificacionPendiente(): bool
    {
        return $this->estado === self::ESTADO_RECTIFICACION_PENDIENTE;
    }

    /**
     * Verificar si la presentación está a rectificar
     */
    public function isARectificar(): bool
    {
        return $this->estado === self::ESTADO_A_RECTIFICAR;
    }

    /**
     * Verificar si es presentación semanal
     */
    public function isSemanal(): bool
    {
        return $this->tipo_entrega === self::TIPO_SEMANAL;
    }

    /**
     * Verificar si es presentación mensual
     */
    public function isMensual(): bool
    {
        return $this->tipo_entrega === self::TIPO_MENSUAL;
    }

    /**
     * Formatear fecha para SSN (DDMMYYYY)
     */
    private function formatDateForSSN(?string $date): ?string
    {
        if (!$date) {
            return null;
        }
        
        try {
            $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
            if ($dateObj) {
                return $dateObj->format('dmY');
            }
            
            // Intentar otros formatos comunes
            $dateObj = \DateTime::createFromFormat('d/m/Y', $date);
            if ($dateObj) {
                return $dateObj->format('dmY');
            }
            
            return $date; // Si no se puede parsear, devolver como está
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Obtener el JSON para enviar a SSN
     */
    public function getSsnJson(): array
    {
        // Usar el servicio para generar el JSON correcto según el tipo de presentación
        if ($this->isMensual()) {
            $stocks = $this->monthlyStocks ? $this->monthlyStocks->toArray() : [];
            return app(\App\Services\ExcelProcessorService::class)->generateMonthlySsnJson($stocks, $this->cronograma);
        } else {
            $operations = $this->weeklyOperations ? $this->weeklyOperations->toArray() : [];
            return app(\App\Services\ExcelProcessorService::class)->generateSsnJson($operations, $this->cronograma);
        }
    }

    /**
     * Scope para presentaciones semanales
     */
    public function scopeSemanal($query)
    {
        return $query->where('tipo_entrega', self::TIPO_SEMANAL);
    }

    /**
     * Scope para presentaciones mensuales
     */
    public function scopeMensual($query)
    {
        return $query->where('tipo_entrega', self::TIPO_MENSUAL);
    }

    /**
     * Scope para un estado específico
     */
    public function scopeEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Obtener la siguiente versión disponible para una presentación
     */
    public static function getNextVersion($codigoCompania, $cronograma, $tipoEntrega): int
    {
        $maxVersion = self::where('codigo_compania', $codigoCompania)
            ->where('cronograma', $cronograma)
            ->where('tipo_entrega', $tipoEntrega)
            ->max('version');
        
        return ($maxVersion ?? 0) + 1;
    }

    /**
     * Crear una nueva versión de la presentación actual
     */
    public function createNewVersion(): self
    {
        $newVersion = self::getNextVersion($this->codigo_compania, $this->cronograma, $this->tipo_entrega);
        
        return self::create([
            'user_id' => $this->user_id,
            'codigo_compania' => $this->codigo_compania,
            'cronograma' => $this->cronograma,
            'tipo_entrega' => $this->tipo_entrega,
            'version' => $newVersion,
            'estado' => self::ESTADO_VACIO,
            'notes' => "Rectificación de la presentación versión {$this->version}",
        ]);
    }

    /**
     * Obtener todas las versiones de esta presentación
     */
    public function getAllVersions()
    {
        return self::where('codigo_compania', $this->codigo_compania)
            ->where('cronograma', $this->cronograma)
            ->where('tipo_entrega', $this->tipo_entrega)
            ->orderBy('version', 'desc')
            ->get();
    }

    /**
     * Verificar si es la versión más reciente
     */
    public function isLatestVersion(): bool
    {
        $maxVersion = self::where('codigo_compania', $this->codigo_compania)
            ->where('cronograma', $this->cronograma)
            ->where('tipo_entrega', $this->tipo_entrega)
            ->max('version');
        
        return $this->version == $maxVersion;
    }

    /**
     * Obtener el identificador único de la presentación (incluye versión)
     */
    public function getUniqueIdentifier(): string
    {
        return "{$this->codigo_compania}-{$this->cronograma}-{$this->tipo_entrega}-v{$this->version}";
    }
}
