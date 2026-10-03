<?php #archivo:backend/app/Http/Controllers/Api/AuditController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * CU30 «Consultar auditoría» (RF14, RN07, RNF03). Solo el Administrador del sistema (E2: 403 para
 * cualquier otro rol, lo garantiza el middleware `role:` de la ruta). Es de solo lectura: la
 * auditoría no se modifica ni se elimina.
 *
 * Fechas: se guardan en UTC; los filtros «desde/hasta» se interpretan como días de America/Bogota
 * (RN07/CU29 piden esa hora) y cada fila trae además `created_at_local` en esa zona.
 */
class AuditController extends Controller
{
    private const TZ = 'America/Bogota';

    private const MAX_EXPORT = 20000;

    private const MESSAGES = [
        'from.date_format'  => 'La fecha inicial no es válida (use AAAA-MM-DD).',
        'to.date_format'    => 'La fecha final no es válida (use AAAA-MM-DD).',
        'to.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        'per_page.max'      => 'Se pueden pedir como máximo 100 registros por página.',
    ];

    /** Paso 2-4: los registros más recientes, filtrables y paginados. */
    public function index(Request $request)
    {
        $perPage = (int) ($request->input('per_page') ?: 25);
        $page    = $this->filtered($request)->with('user:id,name')->orderByDesc('id')->paginate($perPage);

        $rows = $page->getCollection()->map(fn (Audit $a) => $this->row($a))->values();

        return response()->json([
            'audits'  => $rows,
            'meta'    => [
                'current_page' => $page->currentPage(),
                'last_page'    => $page->lastPage(),
                'per_page'     => $page->perPage(),
                'total'        => $page->total(),
            ],
            // E1: sin resultados
            'message' => $page->total() === 0 ? 'No hay registros para los filtros seleccionados' : null,
        ]);
    }

    /** Paso 5-6: un registro con la comparación de valores anteriores y nuevos. */
    public function show($id)
    {
        $audit = Audit::with('user:id,name')->findOrFail($id);

        return response()->json(['audit' => $this->row($audit) + ['changes' => $this->changes($audit)]]);
    }

    /** Datos para los selectores de filtro: solo lo que realmente existe en la auditoría. */
    public function options()
    {
        $userIds = Audit::whereNotNull('user_id')->distinct()->pluck('user_id');

        return response()->json([
            'actions' => Audit::distinct()->orderBy('action')->pluck('action'),
            'tables'  => Audit::distinct()->orderBy('table_name')->pluck('table_name'),
            'users'   => User::whereIn('id', $userIds)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Contadores para el panel principal (antes descargaba toda la tabla para contarlos). */
    public function summary()
    {
        $latest = Audit::with('user:id,name')->orderByDesc('id')->first();

        return response()->json([
            'total'       => Audit::count(),
            'last_7_days' => Audit::where('created_at', '>=', now()->subDays(7))->count(),
            'latest'      => $latest ? [
                'action'     => $latest->action,
                'table_name' => $latest->table_name,
                'user'       => $latest->user ? ['id' => $latest->user->id, 'name' => $latest->user->name] : null,
            ] : null,
        ]);
    }

    /** A1: exporta a CSV los resultados filtrados (UTF-8 con BOM para Excel). */
    public function export(Request $request)
    {
        $query = $this->filtered($request)->with('user:id,name')->orderByDesc('id')->limit(self::MAX_EXPORT);
        $name  = 'auditoria_' . now(self::TZ)->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID', 'Fecha (hora de Bogotá)', 'Usuario', 'Acción', 'Colección', 'Documento', 'IP', 'Valores anteriores', 'Valores nuevos']);
            foreach ($query->cursor() as $a) {
                fputcsv($out, array_map([$this, 'csvCell'], [
                    $a->id,
                    $this->local($a),
                    $a->user?->name ?? '',
                    $a->action,
                    $a->table_name,
                    $a->record_id,
                    $a->ip_address ?? '',
                    $a->old_values ? json_encode($a->old_values, JSON_UNESCAPED_UNICODE) : '',
                    $a->new_values ? json_encode($a->new_values, JSON_UNESCAPED_UNICODE) : '',
                ]));
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /* ───────────────────────────── helpers ───────────────────────────── */

    private function filtered(Request $request): Builder
    {
        $f = $request->validate([
            'user_id'  => 'nullable|integer',
            'table'    => 'nullable|string|max:100',
            'action'   => 'nullable|string|max:50',
            'from'     => 'nullable|date_format:Y-m-d',
            'to'       => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ], self::MESSAGES);

        return Audit::query()
            ->when($f['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($f['table'] ?? null,   fn ($q, $v) => $q->where('table_name', $v))
            ->when($f['action'] ?? null,  fn ($q, $v) => $q->where('action', $v))
            // Días completos de Bogotá convertidos a UTC (como se guardan).
            ->when($f['from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', Carbon::parse($v, self::TZ)->startOfDay()->utc()))
            ->when($f['to'] ?? null,   fn ($q, $v) => $q->where('created_at', '<=', Carbon::parse($v, self::TZ)->endOfDay()->utc()));
    }

    private function local(Audit $a): string
    {
        return $a->created_at ? $a->created_at->copy()->setTimezone(self::TZ)->format('Y-m-d H:i:s') : '';
    }

    private function row(Audit $a): array
    {
        return [
            'id'               => $a->id,
            'created_at'       => $a->created_at,
            'created_at_local' => $this->local($a),
            'user'             => $a->user ? ['id' => $a->user->id, 'name' => $a->user->name] : null,
            'user_id'          => $a->user_id,
            'action'           => $a->action,
            'table_name'       => $a->table_name,
            'record_id'        => $a->record_id,
            'ip_address'       => $a->ip_address,
            'old_values'       => $a->old_values,
            'new_values'       => $a->new_values,
        ];
    }

    /** Comparación campo a campo: [{field, old, new}] (vacía en filas anteriores a CU29). */
    private function changes(Audit $a): array
    {
        $old = $a->old_values ?? [];
        $new = $a->new_values ?? [];
        $out = [];
        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $field) {
            $out[] = ['field' => $field, 'old' => $old[$field] ?? null, 'new' => $new[$field] ?? null];
        }
        return $out;
    }

    /**
     * Neutraliza la inyección de fórmulas en CSV: una celda que empiece por = + - @ (o tabulador/
     * retorno de carro) se interpretaría como fórmula al abrirla en Excel/Calc. Se antepone una
     * comilla simple. Los nombres de usuario y los valores guardados vienen de entradas de usuarios.
     */
    private function csvCell($value)
    {
        if (is_string($value) && preg_match('/^[=+\-@\t\r]/', $value)) {
            return "'" . $value;
        }
        return $value;
    }
}
