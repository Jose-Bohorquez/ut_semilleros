<?php #archivo: backend/app/Models/Proposal.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{

    /* Vocabulario de la especificación (RF11, CU25 paso 5, CU26 paso 3). Desde la migración
       2026_10_04_000001 los valores internos del enum SON estos (antes PENDIENTE/APROBADA/RECHAZADA). */
    public const STATUS_LABELS = [
        'RECIBIDA'  => 'Recibida',
        'VIABLE'    => 'Viable',
        'ARCHIVADA' => 'Archivada',
    ];

    /** Nombres internos anteriores: la API los sigue aceptando como ENTRADA (clientes con caché antigua). */
    public const LEGACY_STATUSES = [
        'PENDIENTE' => 'RECIBIDA',
        'APROBADA'  => 'VIABLE',
        'RECHAZADA' => 'ARCHIVADA',
    ];

    /** Devuelve el estado en el vocabulario actual (acepta también los nombres anteriores) o null si no existe. */
    public static function normalizeStatus(?string $status): ?string
    {
        $s = strtoupper(trim((string) $status));

        return self::LEGACY_STATUSES[$s] ?? (isset(self::STATUS_LABELS[$s]) ? $s : null);
    }

    protected $appends = ['status_label'];

    protected $fillable = [
        'user_id',
        'program_id',
        'title',
        'description',
        'phone',
        'status'
    ];

    /* RNF03: dato personal cifrado en reposo con APP_KEY, mismo patrón que
       Coordinator/SeedbedMember. */
    protected function casts(): array
    {
        return [
            'phone' => 'encrypted',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? (string) $this->status;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Áreas de conocimiento (CU25: selección múltiple, antes area_id único).
     */
    public function areas()
    {
        return $this->belongsToMany(Area::class, 'proposal_area');
    }

    /** Quien evaluó la propuesta (CU27); `reviewed_by` es un id sin llave foránea. */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

}