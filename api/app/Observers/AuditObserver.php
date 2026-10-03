<?php

namespace App\Observers;

use App\Support\AuditTrail;
use Illuminate\Database\Eloquent\Model;

/**
 * CU29: registra automáticamente la creación, modificación, cambio de estado, eliminación y
 * restauración de los modelos auditables (ver AppServiceProvider::registerAuditObservers).
 * Toda la lógica de qué guardar, enmascarar y qué hacer ante una falla está en AuditTrail.
 */
class AuditObserver
{
    public function created(Model $model): void
    {
        AuditTrail::record(
            'CREATE', $model->getTable(), $model->getKey(),
            null, AuditTrail::sanitize($model, $model->getAttributes())
        );
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        $keys    = array_values(array_diff(array_keys($changes), AuditTrail::NOISE));

        // Solo cambió last_login_at o similares: el inicio de sesión ya queda como evento LOGIN.
        if (!$keys) {
            return;
        }

        $old = $new = [];
        foreach ($keys as $key) {
            $old[$key] = $model->getRawOriginal($key);
            $new[$key] = $changes[$key];
        }

        // RN07 nombra «cambio de estado» como acción propia, distinta de «modificado».
        $action = in_array('status', $keys, true) ? 'STATUS_CHANGE' : 'UPDATE';

        AuditTrail::record(
            $action, $model->getTable(), $model->getKey(),
            AuditTrail::sanitize($model, $old), AuditTrail::sanitize($model, $new)
        );
    }

    public function deleted(Model $model): void
    {
        AuditTrail::record(
            'DELETE', $model->getTable(), $model->getKey(),
            AuditTrail::sanitize($model, $model->getRawOriginal()), null
        );
    }

    public function restored(Model $model): void
    {
        AuditTrail::record('RESTORE', $model->getTable(), $model->getKey());
    }
}
