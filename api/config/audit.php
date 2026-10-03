<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Retención de la auditoría
    |--------------------------------------------------------------------------
    | Meses que se conservan los registros de la tabla `audits`. Lo usa el comando
    | `php artisan audits:prune` (que NO está programado: se ejecuta a mano). Mínimo 6.
    */
    'retention_months' => (int) env('AUDIT_RETENTION_MONTHS', 24),

];
