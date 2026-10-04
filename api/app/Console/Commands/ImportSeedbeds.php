<?php

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\Coordinator;
use App\Models\Faculty;
use App\Models\Seedbed;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Carga masiva de semilleros y sus coordinadores desde un JSON (listado entregado por el IDEAD).
 *
 * El archivo trae datos personales (correo y celular de cada coordinador): NO está en el repositorio, se pasa por ruta.
 * Formato: [{"code","title","objective"|null,"coordinator","email","phone"|null,"areas":["Humanidades",…]}]
 *
 * Reglas:
 *  - Idempotente: un semillero cuyo código ya existe NO se toca; un coordinador cuyo correo ya existe se reutiliza.
 *  - Los programas de cada semillero son TODOS los de la facultad indicada (--faculty, por defecto IDEAD); el listado no
 *    dice cuáles aplican a cada uno. Se pueden ajustar luego desde «Editar» en Semilleros.
 *  - Sin objetivo general (obligatorio) se pone un texto provisional y el semillero queda INACTIVO (borrador).
 *  - Por defecto todos quedan INACTIVOS (no visibles para los estudiantes) hasta que alguien los revise; con --publish
 *    quedan ACTIVOS los que vienen completos.
 *  - La referencia de aprobación (RN03) no viene en el listado: se usa --approval-ref (provisional).
 */
class ImportSeedbeds extends Command
{
    protected $signature = 'seedbeds:import {file : Ruta del JSON}
                            {--faculty=IDEAD : Código de la facultad cuyos programas se asocian}
                            {--approval-ref=Pendiente de acta (carga masiva IDEAD) : Referencia de aprobación provisional}
                            {--publish : Deja ACTIVOS los semilleros que vienen completos}
                            {--dry-run : Solo muestra qué haría}';

    protected $description = 'Importa semilleros y coordinadores desde un JSON (idempotente)';

    private const PLACEHOLDER_OBJECTIVE = 'Objetivo general por definir con el coordinador del semillero.';

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        if (! is_file($path)) {
            $this->error("No existe el archivo {$path}");

            return self::FAILURE;
        }
        $rows = json_decode((string) file_get_contents($path), true);
        if (! is_array($rows) || $rows === []) {
            $this->error('El archivo no es un JSON válido o está vacío.');

            return self::FAILURE;
        }

        $faculty = Faculty::where('code', $this->option('faculty'))->first();
        if (! $faculty) {
            $this->error("No existe la facultad '{$this->option('faculty')}'. Ejecuta antes: php artisan catalog:idead");

            return self::FAILURE;
        }
        $programIds = $faculty->programs()->where('status', 'ACTIVO')->pluck('id')->all();
        if (! $programIds) {
            $this->error('La facultad no tiene programas activos. Ejecuta antes: php artisan catalog:idead');

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $stats = ['semilleros' => 0, 'existentes' => 0, 'coordinadores' => 0, 'incompletos' => 0, 'sin_area' => 0];

        foreach ($rows as $i => $r) {
            $code = trim((string) ($r['code'] ?? ''));
            $title = trim((string) ($r['title'] ?? ''));
            $email = Str::lower(trim((string) ($r['email'] ?? '')));
            if ($code === '' || $title === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->warn('  ! fila ' . ($i + 1) . ' omitida (falta código, título o correo válido)');
                continue;
            }

            if (Seedbed::where('code', $code)->exists()) {
                $stats['existentes']++;
                $this->line("  = {$code} ya existe: no se toca");
                continue;
            }

            $objective = trim((string) ($r['objective'] ?? ''));
            $complete = mb_strlen($objective) >= 10;
            $status = ($complete && $this->option('publish')) ? 'ACTIVO' : 'INACTIVO';
            if (! $complete) { $stats['incompletos']++; $objective = self::PLACEHOLDER_OBJECTIVE; }

            $areaIds = Area::whereIn('name', (array) ($r['areas'] ?? []))->pluck('id')->all();
            if (! $areaIds) { $stats['sin_area']++; }

            $coordinator = Coordinator::where('email', $email)->first();
            if (! $coordinator) {
                $stats['coordinadores']++;
                if (! $dry) {
                    $coordinator = Coordinator::create([
                        'name' => trim((string) $r['coordinator']), 'email' => $email,
                        'phone' => ($r['phone'] ?? null) ?: null, 'status' => 'ACTIVO',
                    ]);
                }
            }

            $stats['semilleros']++;
            $this->line(sprintf('  + %s %s  [%s%s]  coord: %s', $code, Str::limit($title, 60), $status, $complete ? '' : ', sin objetivo', trim((string) $r['coordinator'])));

            if ($dry) {
                continue;
            }
            if (! $areaIds) {
                $this->warn("    (sin área reconocida para {$code}: se debe asignar a mano; no se crea)");
                $stats['semilleros']--;
                continue;
            }

            $seedbed = new Seedbed();
            $seedbed->forceFill([
                'code' => $code, 'name' => $title, 'objetivo_general' => $objective,
                'authorization_reference' => (string) $this->option('approval-ref'),
                'coordinator_id' => $coordinator->id, 'status' => $status,
            ])->save();
            $seedbed->programs()->sync($programIds);
            $seedbed->areas()->sync($areaIds);
        }

        $this->info(($dry ? '[simulación] ' : '')
            . "Semilleros nuevos: {$stats['semilleros']} · ya existían: {$stats['existentes']} · coordinadores nuevos: {$stats['coordinadores']} · sin objetivo (borrador): {$stats['incompletos']}"
            . ($stats['sin_area'] ? " · sin área: {$stats['sin_area']}" : ''));

        return self::SUCCESS;
    }
}
