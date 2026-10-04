<?php #archivo:backend/app/Http/Controllers/Api/ReportController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cat;
use App\Support\Csv;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * CU28 «Consultar reportes y estadísticas» (RF15, RN06, RNF07).
 *
 * Cuatro indicadores calculados con consultas de agregación sobre la base de datos:
 *   1. semilleros activos por facultad
 *   2. solicitudes por semillero y estado
 *   3. las 10 áreas con más propuestas
 *   4. integrantes activos por programa y nivel
 * con filtros por rango de fechas y CAT, y exportación a CSV de cada reporte.
 *
 * Actores: Administrativo y Administrador del sistema ven todo; A1: el Líder solo los de sus
 * semilleros (RN06); el Estudiante no tiene acceso (403, lo garantiza la ruta).
 *
 * Qué filtra cada filtro (se muestra también en la pantalla):
 *  - Rango de fechas: la fecha de registro de cada elemento contado (semillero, solicitud,
 *    propuesta, integrante), en días completos de America/Bogota.
 *  - CAT: el CAT del semillero. No aplica a las propuestas, que no pertenecen a un semillero.
 * «Semilleros por facultad»: un semillero con programas de varias facultades cuenta en cada una.
 */
class ReportController extends Controller
{
    private const TZ = 'America/Bogota';

    private const TOP_AREAS = 10;

    /** Reportes exportables: clave => [título, columnas]. */
    private const REPORTS = [
        'seedbeds_by_faculty'      => ['Semilleros activos por facultad', ['Facultad', 'Semilleros activos']],
        'requests_by_seedbed'      => ['Solicitudes por semillero y estado', ['Semillero', 'Pendientes', 'Aprobadas', 'Rechazadas', 'Total']],
        'top_areas'                => ['Áreas con más propuestas', ['Área', 'Propuestas']],
        'members_by_program_level' => ['Integrantes activos por programa y nivel', ['Programa', 'Nivel', 'Integrantes activos']],
    ];

    private const LEVELS = ['PR' => 'Pregrado', 'PG' => 'Posgrado'];

    /* ───────────────────────────── endpoints ───────────────────────────── */

    /** Pasos 2 y 4: calcula los cuatro indicadores con los filtros. */
    public function index(Request $request)
    {
        [$filters, $scope] = $this->context($request);

        $reports = $this->guarded(fn () => [
            'seedbeds_by_faculty'      => $this->seedbedsByFaculty($filters, $scope),
            'requests_by_seedbed'      => $this->requestsBySeedbed($filters, $scope),
            'top_areas'                => $this->topAreas($filters, $scope),
            'members_by_program_level' => $this->membersByProgramLevel($filters, $scope),
        ]);

        $empty = collect($reports)->every(fn ($r) => $r['rows'] === []);

        return response()->json([
            'reports' => $reports,
            // E1: sin datos en el rango
            'message' => $empty ? 'Sin datos para el periodo' : null,
        ]);
    }

    /** CATs que se pueden elegir como filtro (el Líder, solo los de sus semilleros). */
    public function options()
    {
        $scope = $this->scopeSeedbedIds();

        $cats = Cat::query()
            ->when($scope !== null, fn ($q) => $q->whereIn('id', DB::table('seedbeds')->whereIn('id', $scope)->whereNotNull('cat_id')->select('cat_id')))
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['cats' => $cats]);
    }

    /** Pasos 5-6: «Exportar CSV» del reporte seleccionado, con los filtros vigentes. */
    public function export(Request $request)
    {
        $request->validate(
            ['report' => ['required', Rule::in(array_keys(self::REPORTS))]],
            ['report.required' => 'Indica qué reporte quieres exportar.', 'report.in' => 'El reporte solicitado no existe.']
        );

        [$filters, $scope] = $this->context($request);
        $key    = $request->input('report');
        $report = $this->guarded(fn () => match ($key) {
            'seedbeds_by_faculty'      => $this->seedbedsByFaculty($filters, $scope),
            'requests_by_seedbed'      => $this->requestsBySeedbed($filters, $scope),
            'top_areas'                => $this->topAreas($filters, $scope),
            'members_by_program_level' => $this->membersByProgramLevel($filters, $scope),
        });

        $name = "reporte_{$key}_" . now(self::TZ)->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($key, $report) {
            $out = fopen('php://output', 'w');
            fwrite($out, Csv::bom());
            fputcsv($out, self::REPORTS[$key][1]);
            foreach ($report['rows'] as $row) {
                fputcsv($out, array_map([Csv::class, 'cell'], $this->csvRow($key, $row)));
            }
            // Fila de total: los totales deben coincidir con los conteos directos (RF15).
            fputcsv($out, $this->csvTotal($key, $report));
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /* ───────────────────────────── contexto y filtros ───────────────────────────── */

    /** @return array{0: array, 1: ?array} filtros validados y alcance de semilleros (null = todos). */
    private function context(Request $request): array
    {
        $f = $request->validate([
            'from'   => 'nullable|date_format:Y-m-d',
            'to'     => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'cat_id' => 'nullable|integer',
        ], [
            'from.date_format'  => 'La fecha inicial no es válida (use AAAA-MM-DD).',
            'to.date_format'    => 'La fecha final no es válida (use AAAA-MM-DD).',
            'to.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ]);

        return [[
            'from'   => isset($f['from']) ? Carbon::parse($f['from'], self::TZ)->startOfDay()->utc() : null,
            'to'     => isset($f['to'])   ? Carbon::parse($f['to'], self::TZ)->endOfDay()->utc() : null,
            'cat_id' => $f['cat_id'] ?? null,
        ], $this->scopeSeedbedIds()];
    }

    /** A1 / RN06: ids de los semilleros que lidera el actor (solo para el Líder); null = sin restricción. */
    private function scopeSeedbedIds(): ?array
    {
        $user = auth()->user();
        if ($user->role !== 'LIDER_SEMILLERO') {
            return null;
        }
        return DB::table('seedbed_user')->where('user_id', $user->id)->where('role', 'LIDER')->pluck('seedbed_id')->all();
    }

    private function dates($query, string $column, array $f)
    {
        return $query
            ->when($f['from'], fn ($q, $v) => $q->where($column, '>=', $v))
            ->when($f['to'],   fn ($q, $v) => $q->where($column, '<=', $v));
    }

    /* ───────────────────────────── indicadores ───────────────────────────── */

    private function seedbedsByFaculty(array $f, ?array $scope): array
    {
        $q = DB::table('seedbeds')
            ->join('seedbed_program', 'seedbed_program.seedbed_id', '=', 'seedbeds.id')
            ->join('programs', 'programs.id', '=', 'seedbed_program.program_id')
            ->join('faculties', 'faculties.id', '=', 'programs.faculty_id')
            ->where('seedbeds.status', 'ACTIVO')
            ->when($f['cat_id'], fn ($q, $v) => $q->where('seedbeds.cat_id', $v))
            ->when($scope !== null, fn ($q) => $q->whereIn('seedbeds.id', $scope));
        $this->dates($q, 'seedbeds.created_at', $f);

        $rows = $q->selectRaw('faculties.id as faculty_id, faculties.name as faculty, COUNT(DISTINCT seedbeds.id) as total')
            ->groupBy('faculties.id', 'faculties.name')
            ->orderByDesc('total')->orderBy('faculties.name')
            ->get()
            ->map(fn ($r) => ['faculty_id' => (int) $r->faculty_id, 'faculty' => $r->faculty, 'total' => (int) $r->total])
            ->all();

        // Semilleros distintos (uno con programas de varias facultades cuenta una vez aquí).
        $distinct = DB::table('seedbeds')->where('status', 'ACTIVO')
            ->when($f['cat_id'], fn ($q, $v) => $q->where('cat_id', $v))
            ->when($scope !== null, fn ($q) => $q->whereIn('id', $scope))
            ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('seedbed_program')->whereColumn('seedbed_program.seedbed_id', 'seedbeds.id'));
        $this->dates($distinct, 'created_at', $f);

        return ['rows' => $rows, 'total' => $distinct->count()];
    }

    private function requestsBySeedbed(array $f, ?array $scope): array
    {
        $q = DB::table('requests')
            ->join('seedbeds', 'seedbeds.id', '=', 'requests.seedbed_id')
            ->where('requests.status', '!=', 'CANCELADA')   // retiradas por el estudiante: no cuentan
            ->when($f['cat_id'], fn ($q, $v) => $q->where('seedbeds.cat_id', $v))
            ->when($scope !== null, fn ($q) => $q->whereIn('requests.seedbed_id', $scope));
        $this->dates($q, 'requests.created_at', $f);

        $rows = $q->selectRaw(
                "seedbeds.id as seedbed_id, seedbeds.name as seedbed,
                 SUM(CASE WHEN requests.status = 'PENDIENTE' THEN 1 ELSE 0 END) as pendientes,
                 SUM(CASE WHEN requests.status = 'APROBADA'  THEN 1 ELSE 0 END) as aprobadas,
                 SUM(CASE WHEN requests.status = 'RECHAZADA' THEN 1 ELSE 0 END) as rechazadas,
                 COUNT(*) as total"
            )
            ->groupBy('seedbeds.id', 'seedbeds.name')
            ->orderByDesc('total')->orderBy('seedbeds.name')
            ->get()
            ->map(fn ($r) => [
                'seedbed_id' => (int) $r->seedbed_id, 'seedbed' => $r->seedbed,
                'pendientes' => (int) $r->pendientes, 'aprobadas' => (int) $r->aprobadas,
                'rechazadas' => (int) $r->rechazadas, 'total' => (int) $r->total,
            ])->all();

        return ['rows' => $rows, 'total' => array_sum(array_column($rows, 'total'))];
    }

    private function topAreas(array $f, ?array $scope): array
    {
        $q = DB::table('proposal_area')
            ->join('proposals', 'proposals.id', '=', 'proposal_area.proposal_id')
            ->join('areas', 'areas.id', '=', 'proposal_area.area_id')
            // A1: el Líder solo ve las áreas de sus semilleros. El CAT no aplica (la propuesta no tiene semillero).
            ->when($scope !== null, fn ($q) => $q->whereIn('proposal_area.area_id', DB::table('seedbed_area')->whereIn('seedbed_id', $scope)->select('area_id')));
        $this->dates($q, 'proposals.created_at', $f);

        $rows = $q->selectRaw('areas.id as area_id, areas.name as area, COUNT(DISTINCT proposals.id) as total')
            ->groupBy('areas.id', 'areas.name')
            ->orderByDesc('total')->orderBy('areas.name')
            ->limit(self::TOP_AREAS)
            ->get()
            ->map(fn ($r) => ['area_id' => (int) $r->area_id, 'area' => $r->area, 'total' => (int) $r->total])
            ->all();

        return ['rows' => $rows, 'total' => array_sum(array_column($rows, 'total'))];
    }

    private function membersByProgramLevel(array $f, ?array $scope): array
    {
        $q = DB::table('seedbed_members')
            ->join('programs', 'programs.id', '=', 'seedbed_members.program_id')
            ->join('seedbeds', 'seedbeds.id', '=', 'seedbed_members.seedbed_id')
            ->where('seedbed_members.status', 'ACTIVO')
            ->when($f['cat_id'], fn ($q, $v) => $q->where('seedbeds.cat_id', $v))
            ->when($scope !== null, fn ($q) => $q->whereIn('seedbed_members.seedbed_id', $scope));
        $this->dates($q, 'seedbed_members.created_at', $f);

        $rows = $q->selectRaw('programs.id as program_id, programs.name as program, seedbed_members.level as level, COUNT(*) as total')
            ->groupBy('programs.id', 'programs.name', 'seedbed_members.level')
            ->orderBy('programs.name')->orderBy('seedbed_members.level')
            ->get()
            ->map(fn ($r) => [
                'program_id' => (int) $r->program_id, 'program' => $r->program,
                'level' => $r->level, 'level_label' => self::LEVELS[$r->level] ?? $r->level, 'total' => (int) $r->total,
            ])->all();

        return ['rows' => $rows, 'total' => array_sum(array_column($rows, 'total'))];
    }

    /* ───────────────────────────── CSV ───────────────────────────── */

    private function csvRow(string $key, array $r): array
    {
        return match ($key) {
            'seedbeds_by_faculty'      => [$r['faculty'], $r['total']],
            'requests_by_seedbed'      => [$r['seedbed'], $r['pendientes'], $r['aprobadas'], $r['rechazadas'], $r['total']],
            'top_areas'                => [$r['area'], $r['total']],
            'members_by_program_level' => [$r['program'], $r['level_label'], $r['total']],
        };
    }

    private function csvTotal(string $key, array $report): array
    {
        return match ($key) {
            'seedbeds_by_faculty'      => ['Total (semilleros distintos)', $report['total']],
            'requests_by_seedbed'      => [
                'Total',
                array_sum(array_column($report['rows'], 'pendientes')),
                array_sum(array_column($report['rows'], 'aprobadas')),
                array_sum(array_column($report['rows'], 'rechazadas')),
                $report['total'],
            ],
            'top_areas'                => ['Total', $report['total']],
            'members_by_program_level' => ['Total', '', $report['total']],
        };
    }

    /* ───────────────────────────── E2: consulta > 10 s ───────────────────────────── */

    /**
     * E2: limita la consulta a 10 s en la base de datos y, si se supera, responde con el aviso de
     * reducir el rango (503). MySQL 5.7+/8 usa `max_execution_time` (ms); MariaDB, `max_statement_time` (s).
     */
    private function guarded(callable $fn)
    {
        $this->limitQueryTime();
        try {
            return $fn();
        } catch (QueryException $e) {
            if (self::isTimeout($e)) {
                abort(response()->json(['message' => 'La consulta superó los 10 segundos y se canceló. Reduce el rango de fechas.'], 503));
            }
            throw $e;
        }
    }

    private function limitQueryTime(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }
        try {
            DB::statement('SET SESSION max_execution_time = 10000');
        } catch (\Throwable) {
            try { DB::statement('SET SESSION max_statement_time = 10'); } catch (\Throwable) { /* sin límite disponible */ }
        }
    }

    public static function isTimeout(\Throwable $e): bool
    {
        return (bool) preg_match(
            '/max_execution_time|max_statement_time|maximum statement execution time|query execution was interrupted|execution time exceeded/i',
            $e->getMessage()
        );
    }
}
