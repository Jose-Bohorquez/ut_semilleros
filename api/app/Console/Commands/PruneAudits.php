<?php

namespace App\Console\Commands;

use App\Models\Audit;
use App\Support\AuditTrail;
use App\Support\Csv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Política de retención de la auditoría (RN07, RNF03).
 *
 * La tabla `audits` solo crece (cada LOGIN, cada cambio de cualquier tabla). Este comando elimina los registros
 * anteriores al periodo de retención (24 meses por defecto, `AUDIT_RETENTION_MONTHS`), dejando antes una copia en CSV
 * si se pide (`--archive=`) y registrando en la propia auditoría que se hizo la purga.
 *
 * NO está programado: se ejecuta a mano (o desde un cron cuando el Administrador lo decida).
 * Siempre conviene ver primero `--dry-run`. Con menos de 6 meses se niega, para evitar borrados accidentales.
 */
class PruneAudits extends Command
{
    protected $signature = 'audits:prune
                            {--months= : Meses a conservar (por defecto, audit.retention_months)}
                            {--archive= : Ruta de un CSV donde guardar los registros antes de borrarlos}
                            {--dry-run : Solo cuenta lo que se borraría}
                            {--force : No pide confirmación}';

    protected $description = 'Elimina los registros de auditoría anteriores al periodo de retención (opcionalmente archivándolos en CSV)';

    private const MIN_MONTHS = 6;

    public function handle(): int
    {
        $months = (int) ($this->option('months') ?: config('audit.retention_months', 24));
        if ($months < self::MIN_MONTHS) {
            $this->error('El periodo de retención mínimo es de ' . self::MIN_MONTHS . ' meses (se pidió ' . $months . ').');

            return self::INVALID;
        }

        $cutoff = now()->subMonths($months);
        $query  = Audit::where('created_at', '<', $cutoff);
        $total  = (clone $query)->count();

        $this->info("Conservando {$months} meses (se borran los registros anteriores a {$cutoff->toDateTimeString()} UTC): {$total} registro(s).");

        if ($total === 0 || $this->option('dry-run')) {
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("¿Eliminar {$total} registro(s) de auditoría? Esta acción no se puede deshacer.")) {
            $this->warn('Cancelado.');

            return self::SUCCESS;
        }

        // 1) Archivo previo: si no se puede escribir, NO se borra nada.
        if ($path = $this->option('archive')) {
            if (! $this->archive(clone $query, $path)) {
                $this->error("No se pudo escribir el archivo {$path}; no se eliminó nada.");

                return self::FAILURE;
            }
            $this->info("Copia guardada en {$path}");
        }

        // 2) Borrado por lotes (consulta directa: el modelo Audit es inmutable a propósito).
        $deleted = 0;
        do {
            $ids = (clone $query)->orderBy('id')->limit(1000)->pluck('id');
            if ($ids->isEmpty()) {
                break;
            }
            $deleted += DB::table('audits')->whereIn('id', $ids)->delete();
        } while (true);

        // 3) Constancia de la purga en la propia auditoría.
        AuditTrail::record('PRUNE', 'audits', 0, null, [
            'deleted' => $deleted, 'older_than' => $cutoff->toDateTimeString(), 'retention_months' => $months,
            'archived_to' => $this->option('archive') ? basename((string) $this->option('archive')) : null,
        ], null);

        $this->info("Eliminados {$deleted} registro(s).");

        return self::SUCCESS;
    }

    private function archive($query, string $path): bool
    {
        $dir = dirname($path);
        if (! is_dir($dir) || ! is_writable($dir)) {
            return false;
        }
        $out = @fopen($path, 'w');
        if (! $out) {
            return false;
        }
        fwrite($out, Csv::bom());
        fputcsv($out, ['ID', 'Fecha (UTC)', 'Usuario (id)', 'Acción', 'Colección', 'Documento', 'IP', 'Valores anteriores', 'Valores nuevos']);
        $query->orderBy('id')->chunkById(1000, function ($rows) use ($out) {
            foreach ($rows as $a) {
                fputcsv($out, array_map([Csv::class, 'cell'], [
                    $a->id, (string) $a->created_at, $a->user_id, $a->action, $a->table_name, $a->record_id, $a->ip_address ?? '',
                    $a->old_values ? json_encode($a->old_values, JSON_UNESCAPED_UNICODE) : '',
                    $a->new_values ? json_encode($a->new_values, JSON_UNESCAPED_UNICODE) : '',
                ]));
            }
        });

        return fclose($out);
    }
}
