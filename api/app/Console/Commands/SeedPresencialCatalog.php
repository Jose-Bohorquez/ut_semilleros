<?php

namespace App\Console\Commands;

use App\Models\Faculty;
use App\Models\Program;
use Illuminate\Console\Command;

/**
 * Catálogo presencial de la Universidad del Tolima: sus facultades y los 29 programas de pregrado de la oferta 2026 B,
 * cada uno asociado a su facultad (el listado oficial no la trae; se asignó por el código del programa y su área).
 *
 * Idempotente (busca por código y, para las facultades, también por nombre) y no toca lo que ya existe. `--dry-run` solo cuenta.
 */
class SeedPresencialCatalog extends Command
{
    protected $signature = 'catalog:presencial {--dry-run : Solo muestra qué crearía}';

    protected $description = 'Crea (si faltan) las facultades presenciales y sus programas de pregrado';

    /** código de facultad => [nombre, [código SNIES interno del programa => nombre]] */
    public const FACULTIES = [
        'FIA' => ['Facultad de Ingeniería Agronómica', ['0300' => 'Ingeniería Agronómica', '0301' => 'Ingeniería Agroindustrial', '0305' => 'Profesional en Gastronomía']],
        'FMVZ' => ['Facultad de Medicina Veterinaria y Zootecnia', ['0101' => 'Medicina Veterinaria y Zootecnia']],
        'FIF' => ['Facultad de Ingeniería Forestal', ['0201' => 'Ingeniería Forestal']],
        'FCEA' => ['Facultad de Ciencias Económicas y Administrativas', ['0401' => 'Administración de Empresas', '0402' => 'Economía', '0514' => 'Negocios Internacionales']],
        'FCE' => ['Facultad de Ciencias de la Educación', [
            '0511' => 'Licenciatura en Matemáticas', '0516' => 'Licenciatura en Ciencias Sociales',
            '0517' => 'Licenciatura en Ciencias Naturales y Educación Ambiental', '0518' => 'Licenciatura en Educación Física, Recreación y Deportes',
            '0520' => 'Licenciatura en Literatura y Lengua Castellana', '0521' => 'Licenciatura en Lenguas Extranjeras con Énfasis en Inglés',
        ]],
        'FT' => ['Facultad de Tecnologías', [
            '0603' => 'Arquitectura', '0604' => 'Tecnología en Levantamientos Topográficos',
            '0605' => 'Tecnología en Modelación de Proyectos de Arquitectura e Ingeniería', '0606' => 'Diseño Interactivo y Multimedia', '0607' => 'Ingeniería Civil',
        ]],
        'FC' => ['Facultad de Ciencias', ['0701' => 'Biología', '0702' => 'Matemáticas con Énfasis en Estadística']],
        'FCS' => ['Facultad de Ciencias de la Salud', ['1001' => 'Enfermería', '1002' => 'Medicina']],
        'FCHA' => ['Facultad de Ciencias Humanas y Artes', [
            '0512' => 'Comunicación Social-Periodismo', '1101' => 'Artes Plásticas y Visuales', '1102' => 'Historia',
            '1103' => 'Sociología', '1104' => 'Derecho', '1105' => 'Ciencia Política',
        ]],
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $created = ['facultades' => 0, 'programas' => 0];
        $existing = 0;

        foreach (self::FACULTIES as $fCode => [$fName, $programs]) {
            $faculty = Faculty::where('code', $fCode)->first() ?? Faculty::where('name', $fName)->first();
            if (! $faculty) {
                $created['facultades']++;
                $this->line("  + facultad {$fName}");
                if (! $dry) {
                    $faculty = Faculty::create(['code' => $fCode, 'name' => $fName, 'status' => 'ACTIVO']);
                }
            }
            foreach ($programs as $code => $name) {
                if (Program::where('code', $code)->exists()) { $existing++; continue; }
                $created['programas']++;
                $this->line("    + programa {$code} {$name}");
                if (! $dry) {
                    Program::create(['code' => $code, 'name' => $name, 'type' => 'PREGRADO', 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
                }
            }
        }

        $this->info(($dry ? '[simulación] ' : '') . "Creados: {$created['facultades']} facultades, {$created['programas']} programas. Ya existían: {$existing} programas.");

        return self::SUCCESS;
    }
}
