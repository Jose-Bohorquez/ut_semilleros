<?php

namespace App\Console\Commands;

use App\Models\Cat;
use App\Models\Faculty;
use App\Models\Program;
use Illuminate\Console\Command;

/**
 * Catálogo del IDEAD (Instituto de Educación a Distancia, Universidad del Tolima): su facultad, sus 12 programas de
 * pregrado y los 21 CAT (centros de atención tutorial) donde se ofrecen. Datos públicos de la oferta 2026 B.
 *
 * Es idempotente (se puede repetir sin duplicar: busca por código) y no toca lo que ya existe. Con `--dry-run` solo cuenta.
 * No usa el DatabaseSeeder a propósito: en producción no se ejecuta `db:seed`; se corre este comando.
 */
class SeedIdeadCatalog extends Command
{
    protected $signature = 'catalog:idead {--dry-run : Solo muestra qué crearía}';

    protected $description = 'Crea (si faltan) la facultad IDEAD, sus programas de pregrado y sus CAT';

    public const FACULTY = ['code' => 'IDEAD', 'name' => 'Instituto de Educación a Distancia (IDEAD)'];

    /** código SNIES interno del programa => nombre */
    public const PROGRAMS = [
        '0803' => 'Administración Financiera',
        '0838' => 'Tecnología en Protección y Recuperación de Ecosistemas Forestales',
        '0845' => 'Tecnología en Regencia de Farmacia',
        '0846' => 'Licenciatura en Ciencias Naturales y Educación Ambiental',
        '0847' => 'Licenciatura en Educación Artística',
        '0850' => 'Seguridad y Salud en el Trabajo',
        '0851' => 'Licenciatura en Literatura y Lengua Castellana',
        '0852' => 'Licenciatura en Educación Infantil',
        '0853' => 'Ingeniería en Agroecología',
        '0854' => 'Ingeniería de Sistemas',
        '0855' => 'Contaduría Pública',
        '0856' => 'Administración de Empresas Turísticas y Hoteleras',
    ];

    /** código => nombre de la ciudad/sede */
    public const CATS = [
        'IBAGUE' => 'Ibagué', 'CHAPARRAL' => 'Chaparral', 'BARRANQUILLA' => 'Barranquilla',
        'BOG-KENNEDY' => 'Bogotá Kennedy', 'BOG-SUBA' => 'Bogotá Suba', 'BOG-TUNAL' => 'Bogotá Tunal', 'BOG-SIBATE' => 'Bogotá Sibaté',
        'CALI' => 'Cali', 'GIRARDOT' => 'Girardot', 'HONDA' => 'Honda', 'ICONONZO' => 'Icononzo', 'MARIQUITA' => 'Mariquita',
        'MEDELLIN' => 'Medellín', 'MELGAR' => 'Melgar', 'MOCOA' => 'Mocoa', 'NEIVA' => 'Neiva', 'PEREIRA' => 'Pereira',
        'PLANADAS' => 'Planadas', 'POPAYAN' => 'Popayán', 'PURIFICACION' => 'Purificación', 'URABA' => 'Urabá',
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $created = ['facultad' => 0, 'programas' => 0, 'cat' => 0];
        $existing = ['programas' => 0, 'cat' => 0];

        $faculty = Faculty::where('code', self::FACULTY['code'])->first()
            ?? Faculty::where('name', 'like', '%Distancia%')->first();
        if (! $faculty) {
            $created['facultad']++;
            $this->line('  + facultad ' . self::FACULTY['name']);
            if (! $dry) {
                $faculty = Faculty::create(self::FACULTY + ['status' => 'ACTIVO']);
            }
        } else {
            $this->line("  = facultad existente: {$faculty->name} (#{$faculty->id})");
        }

        foreach (self::PROGRAMS as $code => $name) {
            if (Program::where('code', $code)->exists()) { $existing['programas']++; continue; }
            $created['programas']++;
            $this->line("  + programa {$code} {$name}");
            if (! $dry) {
                Program::create(['code' => $code, 'name' => $name, 'type' => 'PREGRADO', 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
            }
        }

        foreach (self::CATS as $code => $city) {
            if (Cat::where('code', $code)->exists()) { $existing['cat']++; continue; }
            $created['cat']++;
            $this->line("  + CAT {$city}");
            if (! $dry) {
                Cat::create(['code' => $code, 'name' => "CAT {$city}", 'city' => $city, 'status' => 'ACTIVO']);
            }
        }

        $this->info(($dry ? '[simulación] ' : '') . "Creados: {$created['facultad']} facultad, {$created['programas']} programas, {$created['cat']} CAT. Ya existían: {$existing['programas']} programas, {$existing['cat']} CAT.");

        return self::SUCCESS;
    }
}
