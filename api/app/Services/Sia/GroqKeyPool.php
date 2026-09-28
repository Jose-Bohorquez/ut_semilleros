<?php

namespace App\Services\Sia;

use App\Models\SiaMessage;
use Illuminate\Support\Facades\Cache;

/**
 * Rotación de cuentas de Groq para SIA. En BD solo se guarda la etiqueta
 * (cta_01…), nunca la key. Orden: la cuenta menos usada hoy primero, saltando
 * las que están en enfriamiento (429/401 reciente) o llegaron a su tope diario.
 */
class GroqKeyPool
{
    /** @return array<int, array{label:string, key:string}> */
    public function keys(): array
    {
        $raw  = (string) config('services.groq.keys', '');
        $keys = [];
        foreach (array_filter(array_map('trim', explode(',', $raw))) as $i => $item) {
            [$label, $key] = str_contains($item, '=') ? explode('=', $item, 2) : [sprintf('cta_%02d', $i + 1), $item];
            if (trim($key) !== '') $keys[] = ['label' => substr(trim($label), 0, 20), 'key' => trim($key)];
        }
        if (!$keys && config('services.groq.key')) {
            $keys[] = ['label' => 'cta_01', 'key' => (string) config('services.groq.key')];
        }
        return $keys;
    }

    /** @return array<int, array{label:string, key:string}> cuentas utilizables, menos usadas primero */
    public function candidates(int $perKeyPerDay): array
    {
        $usage = $this->usageToday();
        $list  = array_filter($this->keys(), fn ($k) => !$this->isCooling($k['label']) && ($usage[$k['label']] ?? 0) < $perKeyPerDay);
        usort($list, fn ($a, $b) => ($usage[$a['label']] ?? 0) <=> ($usage[$b['label']] ?? 0));
        return array_values($list);
    }

    /** @return array<string,int> */
    public function usageToday(): array
    {
        return SiaMessage::where('role', 'assistant')->where('source', 'API')->where('status', 'OK')
            ->where('created_at', '>=', now()->startOfDay())->whereNotNull('key_label')
            ->selectRaw('key_label, count(*) c')->groupBy('key_label')->pluck('c', 'key_label')->map(fn ($v) => (int) $v)->all();
    }

    public function cool(string $label, int $seconds): void
    {
        Cache::put("sia:cool:{$label}", now()->addSeconds($seconds)->timestamp, $seconds);
    }

    public function isCooling(string $label): bool
    {
        return Cache::has("sia:cool:{$label}");
    }
}
