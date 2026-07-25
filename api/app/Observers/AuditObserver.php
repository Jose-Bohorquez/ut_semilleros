<?php

namespace App\Observers;

use App\Models\Audit;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Auth;

class AuditObserver
{
    /**
     * Registrar CREATE.
     */
    public function created(Model $model): void
    {
        $this->storeAudit('CREATE', $model);
    }

    /**
     * Registrar UPDATE.
     */
    public function updated(Model $model): void
    {
        $this->storeAudit('UPDATE', $model);
    }

    /**
     * Registrar DELETE.
     */
    public function deleted(Model $model): void
    {
        $this->storeAudit('DELETE', $model);
    }

    /**
     * Registrar RESTORE.
     */
    public function restored(Model $model): void
    {
        $this->storeAudit('RESTORE', $model);
    }

    /**
     * Persistir auditoría.
     */
    private function storeAudit(
        string $action,
        Model $model
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Evitar recursión infinita
        |--------------------------------------------------------------------------
        */

        if ($model instanceof Audit) {
            return;
        }

        Audit::create([

            'user_id' => Auth::id(),

            'action' => $action,

            'table_name' => $model->getTable(),

            'record_id' => $model->id

        ]);
    }
}