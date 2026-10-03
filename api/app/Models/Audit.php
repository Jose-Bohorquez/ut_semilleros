<?php #archivo: backend/app/Models/Audit.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro de auditoría (CU29, RF14, RN07): solo se inserta. Nunca se modifica ni se elimina.
 * Se escribe únicamente a través de App\Support\AuditTrail.
 */
class Audit extends Model
{
    /** Sin `updated_at`: una fila de auditoría no cambia después de creada. */
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    protected static function booted(): void
    {
        /* RN07: «no se puede modificar ni eliminar». Garantía a nivel de aplicación; en la base de
           datos compartida de Hostinger no se pueden revocar UPDATE/DELETE al usuario de la app. */
        static::updating(fn () => throw new \LogicException('Los registros de auditoría no se pueden modificar (RN07).'));
        static::deleting(fn () => throw new \LogicException('Los registros de auditoría no se pueden eliminar (RN07).'));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
