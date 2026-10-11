<?php

namespace App\Console\Commands;

use App\Models\Faculty;
use App\Models\Program;
use App\Models\Seedbed;
use Illuminate\Console\Command;

/**
 * Hallazgo real (2026-10-11): antes de cargar el catálogo oficial (catalog:idead /
 * catalog:presencial), los 3 semilleros originales de prueba quedaron apuntando a una
 * facultad "Ingenieria" y un programa "Ingeniería de Sistemas" inventados (sin código,
 * no están en la oferta oficial de la Universidad del Tolima). "Ingeniería de Sistemas"
 * es en realidad un programa del IDEAD (código 0854) — esta facultad/programa falsos son
 * un duplicado. Este comando reasigna esos semilleros al programa real y deja la
 * facultad/programa falsos INACTIVOS (RN01: no se borran físicamente).
 *
 * Idempotente: si ya no hay semilleros en el programa falso, solo inactiva lo que falte.
 */
class FixLegacyIngenieriaProgram extends Command
{
    protected $signature = 'catalog:fix-legacy-ingenieria {--dry-run : Solo muestra qué haría}';

    protected $description = 'Reasigna los semilleros de la facultad/programa "Ingenieria" inventados al programa real del IDEAD';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $fakeFaculty = Faculty::whereNull('code')->where('name', 'Ingenieria')->first();
        $fakeProgram = Program::whereNull('code')->where('name', 'Ingeniería de Sistemas')->first();
        $realProgram = Program::where('code', '0854')->first();

        if (! $fakeProgram || ! $realProgram) {
            $this->info('Nada que hacer: no se encontró el programa falso o el real (0854).');

            return self::SUCCESS;
        }

        $affected = $fakeProgram->seedbeds()->get();
        $this->line("Semilleros en el programa falso: {$affected->count()}");

        foreach ($affected as $seedbed) {
            $this->line("  - #{$seedbed->id} {$seedbed->name}");
            if (! $dry) {
                $seedbed->programs()->detach($fakeProgram->id);
                $seedbed->programs()->syncWithoutDetaching([$realProgram->id]);
            }
        }

        if (! $dry) {
            $fakeProgram->update(['status' => 'INACTIVO']);
            if ($fakeFaculty) {
                $fakeFaculty->update(['status' => 'INACTIVO']);
            }
        }

        $this->info(($dry ? '[simulación] ' : '') . 'Listo: semilleros reasignados a Ingeniería de Sistemas (IDEAD, 0854); facultad y programa falsos quedaron INACTIVOS.');

        return self::SUCCESS;
    }
}
