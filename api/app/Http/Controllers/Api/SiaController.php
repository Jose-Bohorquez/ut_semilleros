<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiaConversation;
use App\Models\SiaKnowledge;
use App\Models\SiaMessage;
use App\Models\SiaSetting;
use App\Services\Sia\GroqKeyPool;
use App\Services\Sia\SiaAssistant;
use App\Services\Sia\SiaLocalResponder;
use App\Services\Sia\SiaUnavailableException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * SIA — Sistema Integrado de Asistencia (RF17 propuesto, 2026-09-28).
 * chat/close son públicos (también se usan en el login) con límites por IP,
 * por usuario, por conversación y un tope global diario.
 */
class SiaController extends Controller
{
    public function __construct(private SiaAssistant $assistant, private SiaLocalResponder $local, private GroqKeyPool $pool) {}

    private function ipHash(Request $request): string
    {
        return hash('sha256', $request->ip() . '|' . config('app.key'));
    }

    /** Usuario con sesión si mandó un token válido y está activo; si no, visitante. */
    private function currentUser()
    {
        $user = auth('sanctum')->user();
        return ($user && $user->status === 'ACTIVO') ? $user : null;
    }

    public function chat(Request $request)
    {
        $cfg = SiaSetting::all_values();

        $data = $request->validate([
            'message' => 'required|string|max:' . $cfg['max_question_chars'],
            'token'   => 'nullable|string|max:64',
            'page'    => 'nullable|string|max:120',
        ]);

        $limited = fn (string $msg) => response()->json(['message' => $msg, 'limited' => true], 429);

        if (!$cfg['enabled']) {
            return $limited('SIA no está disponible en este momento. Para reportar un error usa el botón de WhatsApp.');
        }

        $user   = $this->currentUser();
        $ipHash = $this->ipHash($request);

        /* Preguntas simples o ya resueltas por el admin: respuesta local, sin gastar
           cupo de Groq ni de la persona (Jose, 2026-09-28). */
        if ($hit = $this->local->match($data['message'])) {
            $conv = $this->resolveConversation($data, $user, $ipHash);
            if ($conv->message_count >= $cfg['max_messages']) {
                return response()->json(['message' => 'Esta conversación llegó al máximo de preguntas. Finalízala y califícala para iniciar una nueva.', 'limited' => true, 'must_close' => true, 'token' => $conv->token], 429);
            }
            SiaMessage::create(['conversation_id' => $conv->id, 'role' => 'user', 'content' => $data['message'], 'source' => $hit['source']]);
            SiaMessage::create(['conversation_id' => $conv->id, 'role' => 'assistant', 'content' => $hit['answer'], 'source' => $hit['source']]);
            $conv->increment('message_count');
            return response()->json(['token' => $conv->token, 'answer' => $hit['answer'], 'remaining' => max(0, $cfg['max_messages'] - $conv->message_count), 'local' => true]);
        }

        /* Tope global diario (protege el cupo gratis de Groq) */
        $today = SiaMessage::where('role', 'assistant')->where('status', 'OK')->where('source', 'API')->where('created_at', '>=', now()->startOfDay());
        if ((clone $today)->count() >= $cfg['global_requests_per_day']
            || (clone $today)->sum(DB::raw('prompt_tokens + completion_tokens')) >= $cfg['global_tokens_per_day']) {
            return $limited('SIA alcanzó su límite de consultas por hoy. Vuelve a intentarlo mañana; si es un error del sistema, repórtalo por WhatsApp.');
        }

        /* Límites por persona */
        if ($user) {
            $keys = [["sia:u:d:{$user->id}", $cfg['user_per_day'], 86400]];
        } else {
            $keys = [["sia:ip:h:{$ipHash}", $cfg['guest_per_hour'], 3600], ["sia:ip:d:{$ipHash}", $cfg['guest_per_day'], 86400]];
        }
        foreach ($keys as [$key, $max, $decay]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                $mins = (int) ceil(RateLimiter::availableIn($key) / 60);
                return $limited("Llegaste al límite de preguntas a SIA. Podrás preguntar de nuevo en unos {$mins} minutos." . ($user ? '' : ' Si inicias sesión tienes un cupo mayor.'));
            }
        }

        $conv = $this->resolveConversation($data, $user, $ipHash);
        if ($conv->message_count >= $cfg['max_messages']) {
            return response()->json([
                'message' => 'Esta conversación llegó al máximo de preguntas. Finalízala y califícala para iniciar una nueva.',
                'limited' => true, 'must_close' => true, 'token' => $conv->token,
            ], 429);
        }

        foreach ($keys as [$key, $max, $decay]) {
            RateLimiter::hit($key, $decay);
        }

        $history = $conv->messages()->where('status', 'OK')->orderBy('id')->get(['role', 'content'])
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])->all();

        SiaMessage::create(['conversation_id' => $conv->id, 'role' => 'user', 'content' => $data['message']]);
        $conv->increment('message_count');

        try {
            $r = $this->assistant->ask($data['message'], $history, $cfg['max_answer_tokens'], $cfg['per_key_requests_per_day'], $user?->role);
        } catch (SiaUnavailableException|\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('[SIA] ' . $e->getMessage());
            SiaMessage::create(['conversation_id' => $conv->id, 'role' => 'assistant', 'content' => '', 'status' => 'ERROR']);
            return response()->json([
                'message' => 'SIA no pudo responder en este momento. Intenta de nuevo en unos minutos.',
                'token'   => $conv->token,
            ], 503);
        }

        SiaMessage::create([
            'conversation_id'   => $conv->id,
            'role'              => 'assistant',
            'content'           => $r['answer'],
            'prompt_tokens'     => $r['prompt_tokens'],
            'completion_tokens' => $r['completion_tokens'],
            'latency_ms'        => $r['latency_ms'],
            'model'             => $r['model'],
            'key_label'         => $r['key_label'],
            'source'            => 'API',
        ]);

        return response()->json([
            'token'     => $conv->token,
            'answer'    => $r['answer'],
            'remaining' => max(0, $cfg['max_messages'] - $conv->message_count),
        ]);
    }

    /** Conversación existente y abierta del cliente, o una nueva. */
    private function resolveConversation(array $data, $user, string $ipHash): SiaConversation
    {
        $conv = !empty($data['token'])
            ? SiaConversation::where('token', $data['token'])->where('status', 'OPEN')->first()
            : null;
        return $conv ?? SiaConversation::create([
            'token'   => Str::random(48),
            'user_id' => $user?->id,
            'ip_hash' => $ipHash,
            'page'    => $data['page'] ?? null,
        ]);
    }

    /** Finaliza la conversación con la calificación (1–5 caritas) y el comentario, o la omite. */
    public function close(Request $request)
    {
        $data = $request->validate([
            'token'    => 'required|string|max:64',
            'rating'   => 'nullable|integer|between:1,5',
            'feedback' => 'nullable|string|max:2000',
            'skipped'  => 'sometimes|boolean',
        ]);

        $conv = SiaConversation::where('token', $data['token'])->first();
        if (!$conv) {
            return response()->json(['message' => 'Conversación no encontrada'], 404);
        }
        if ($conv->status === 'CLOSED') {
            return response()->json(['message' => 'La conversación ya estaba finalizada'], 409);
        }

        $skipped = (bool) ($data['skipped'] ?? false) || empty($data['rating']);
        $conv->update([
            'status'         => 'CLOSED',
            'closed_at'      => now(),
            'rating'         => $skipped ? null : $data['rating'],
            'rating_skipped' => $skipped,
            'feedback'       => $data['feedback'] ?? null,
        ]);

        return response()->json(['message' => '¡Gracias! Tu opinión nos ayuda a mejorar SIA.']);
    }

    /* ======================= Panel del administrador ======================= */

    public function stats()
    {
        $cfg   = SiaSetting::all_values();
        $range = fn ($from) => SiaMessage::where('role', 'assistant')->where('source', 'API')->where('created_at', '>=', $from);

        $sum = function ($q) {
            return [
                'requests' => (clone $q)->where('status', 'OK')->count(),
                'errors'   => (clone $q)->where('status', 'ERROR')->count(),
                'tokens'   => (int) (clone $q)->sum(DB::raw('prompt_tokens + completion_tokens')),
                'avg_ms'   => (int) (clone $q)->where('status', 'OK')->avg('latency_ms'),
            ];
        };
        $today = $sum($range(now()->startOfDay()));
        $month = $sum($range(now()->startOfMonth()));

        $closed = SiaConversation::where('status', 'CLOSED');
        $dist   = (clone $closed)->whereNotNull('rating')->select('rating', DB::raw('count(*) c'))->groupBy('rating')->pluck('c', 'rating');

        $localToday = SiaMessage::where('role', 'assistant')->whereIn('source', ['LOCAL', 'FAQ'])->where('created_at', '>=', now()->startOfDay())->count();
        $usage = $this->pool->usageToday();
        $keys  = array_map(fn ($k) => [
            'label'    => $k['label'],
            'requests' => $usage[$k['label']] ?? 0,
            'cap'      => $cfg['per_key_requests_per_day'],
            'cooling'  => $this->pool->isCooling($k['label']),
        ], $this->pool->keys());

        return response()->json([
            'today'  => $today + ['requests_cap' => $cfg['global_requests_per_day'], 'tokens_cap' => $cfg['global_tokens_per_day'], 'local' => $localToday],
            'keys'   => $keys,
            'month'  => $month,
            'conversations' => [
                'total'      => SiaConversation::count(),
                'open'       => SiaConversation::where('status', 'OPEN')->count(),
                'rated'      => (clone $closed)->whereNotNull('rating')->count(),
                'skipped'    => (clone $closed)->where('rating_skipped', true)->count(),
                'unreviewed' => (clone $closed)->whereNull('reviewed_at')->where(fn ($q) => $q->where('rating', '<=', 2)->orWhereNotNull('feedback'))->count(),
                'avg_rating' => round((float) (clone $closed)->whereNotNull('rating')->avg('rating'), 2),
                'distribution' => collect(range(1, 5))->mapWithKeys(fn ($r) => [$r => (int) ($dist[$r] ?? 0)]),
            ],
        ]);
    }

    public function conversations(Request $request)
    {
        $q = SiaConversation::with('user:id,name,email,role')->withCount('messages')->latest('id');
        match ($request->query('filter')) {
            'low'        => $q->where('rating', '<=', 2),
            'feedback'   => $q->whereNotNull('feedback'),
            'unreviewed' => $q->where('status', 'CLOSED')->whereNull('reviewed_at'),
            default      => null,
        };
        return response()->json($q->paginate(20));
    }

    public function conversation($id)
    {
        $conv = SiaConversation::with(['user:id,name,email,role', 'messages:id,conversation_id,role,content,status,prompt_tokens,completion_tokens,latency_ms,created_at'])->findOrFail($id);
        return response()->json(['conversation' => $conv]);
    }

    public function review(Request $request, $id)
    {
        $data = $request->validate(['admin_note' => 'nullable|string|max:2000']);
        $conv = SiaConversation::findOrFail($id);
        $conv->update(['admin_note' => $data['admin_note'] ?? null, 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        return response()->json(['message' => 'Revisión guardada', 'conversation' => $conv]);
    }

    public function knowledgeIndex()
    {
        return response()->json(['knowledge' => SiaKnowledge::latest('id')->get()]);
    }

    public function knowledgeStore(Request $request)
    {
        $data = $request->validate(['question' => 'required|string|max:300', 'answer' => 'required|string|max:3000']);
        $k = SiaKnowledge::create($data + ['active' => true, 'created_by' => auth()->id()]);
        return response()->json(['message' => 'Respuesta agregada al conocimiento de SIA', 'knowledge' => $k], 201);
    }

    public function knowledgeUpdate(Request $request, $id)
    {
        $data = $request->validate(['question' => 'sometimes|string|max:300', 'answer' => 'sometimes|string|max:3000', 'active' => 'sometimes|boolean']);
        $k = SiaKnowledge::findOrFail($id);
        $k->update($data);
        return response()->json(['message' => 'Actualizado', 'knowledge' => $k]);
    }

    public function settings()
    {
        return response()->json(['settings' => SiaSetting::all_values(), 'model' => config('services.groq.model'), 'configured' => count($this->pool->keys()) > 0, 'accounts' => count($this->pool->keys())]);
    }

    public function settingsUpdate(Request $request)
    {
        $rules = [];
        foreach (array_keys(SiaSetting::DEFAULTS) as $k) {
            $rules[$k] = $k === 'enabled' ? 'sometimes|boolean' : 'sometimes|integer|min:1|max:' . ($k === 'global_tokens_per_day' ? 5000000 : 5000);
        }
        $data = $request->validate($rules);
        foreach ($data as $k => $v) {
            SiaSetting::updateOrCreate(['key' => $k], ['value' => (string) (int) $v]);
        }
        return response()->json(['message' => 'Límites actualizados', 'settings' => SiaSetting::all_values()]);
    }
}
