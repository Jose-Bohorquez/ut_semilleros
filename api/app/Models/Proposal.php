<?php #archivo: backend/app/Models/Proposal.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{

    /* Vocabulario de la especificación (RF11, CU25 paso 5, CU26 paso 3) sobre los
       valores internos del enum. Migrar el enum es decisión de CU27; mientras tanto
       la API y la PWA muestran estas etiquetas (puente reversible). */
    public const STATUS_LABELS = [
        'PENDIENTE' => 'Recibida',
        'APROBADA'  => 'Viable',
        'RECHAZADA' => 'Archivada',
    ];

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