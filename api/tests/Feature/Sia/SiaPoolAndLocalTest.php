<?php

namespace Tests\Feature\Sia;

use Tests\TestCase;
use App\Models\SiaKnowledge;
use App\Models\SiaMessage;
use App\Models\SiaConversation;
use App\Services\Sia\GroqKeyPool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;

/** SIA: rotación de cuentas de Groq y respuestas locales sin API (2026-09-28). */
class SiaPoolAndLocalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.groq.keys' => 'cta_01=k1,cta_02=k2,cta_03=k3', 'services.groq.key' => null]);
        Cache::flush();
        foreach (['h', 'd'] as $p) RateLimiter::clear("sia:ip:{$p}:" . hash('sha256', '127.0.0.1|' . config('app.key')));
    }

    private function ok(): array
    {
        return ['choices' => [['message' => ['content' => 'ok desde API']]], 'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 20]];
    }

    public function test_rotates_to_next_account_on_429_and_records_label(): void
    {
        Http::fake(fn ($req) => $req->hasHeader('Authorization', 'Bearer k1')
            ? Http::response(['error' => 'rate'], 429, ['retry-after' => '30'])
            : Http::response($this->ok()));
        $this->postJson('/api/sia/chat', ['message' => '¿cómo instalo la aplicación en el celular?'])->assertStatus(200)->assertJson(['answer' => 'ok desde API']);
        $this->assertDatabaseHas('sia_messages', ['role' => 'assistant', 'source' => 'API', 'key_label' => 'cta_02']);
        $this->assertTrue(app(GroqKeyPool::class)->isCooling('cta_01'));
    }

    public function test_invalid_key_is_cooled_and_skipped(): void
    {
        Http::fake(fn ($req) => $req->hasHeader('Authorization', 'Bearer k1')
            ? Http::response(['error' => 'invalid'], 401)
            : Http::response($this->ok()));
        $this->postJson('/api/sia/chat', ['message' => '¿cómo instalo la aplicación?'])->assertStatus(200);
        $this->assertTrue(app(GroqKeyPool::class)->isCooling('cta_01'));
    }

    public function test_all_accounts_exhausted_returns_503(): void
    {
        Http::fake(['*' => Http::response(['error' => 'rate'], 429, ['x-ratelimit-remaining-requests' => '0'])]);
        $this->postJson('/api/sia/chat', ['message' => '¿cómo instalo la aplicación?'])->assertStatus(503);
        Http::assertSentCount(3);
        foreach (['cta_01', 'cta_02', 'cta_03'] as $l) $this->assertTrue(app(GroqKeyPool::class)->isCooling($l));
    }

    public function test_least_used_account_first_and_per_key_cap(): void
    {
        $c = SiaConversation::create(['token' => str_repeat('z', 48), 'ip_hash' => 'h']);
        foreach (['cta_01', 'cta_01', 'cta_02'] as $l) SiaMessage::create(['conversation_id' => $c->id, 'role' => 'assistant', 'content' => 'x', 'key_label' => $l]);
        $order = array_column(app(GroqKeyPool::class)->candidates(800), 'label');
        $this->assertSame(['cta_03', 'cta_02', 'cta_01'], $order);
        $this->assertSame(['cta_03', 'cta_02'], array_column(app(GroqKeyPool::class)->candidates(2), 'label'));
    }

    public function test_simple_intents_answer_locally_without_api(): void
    {
        Http::fake();
        foreach (['hola', '¿Quién eres?', 'quien te creo', '¿Cómo hablo con los creadores?', 'muchas gracias', 'chao'] as $q) {
            $r = $this->postJson('/api/sia/chat', ['message' => $q])->assertStatus(200)->assertJson(['local' => true]);
            $this->assertNotEmpty($r->json('answer'));
        }
        Http::assertNothingSent();
        $this->assertSame(6, SiaMessage::where('role', 'assistant')->where('source', 'LOCAL')->count());
        $this->assertStringContainsString('José Bohórquez', $this->postJson('/api/sia/chat', ['message' => '¿quién te creó?'])->json('answer'));
    }

    public function test_greeting_with_real_question_goes_to_api(): void
    {
        Http::fake(['*' => Http::response($this->ok())]);
        $this->postJson('/api/sia/chat', ['message' => 'hola, ¿cómo me postulo a un semillero desde la app?'])->assertStatus(200)->assertJsonMissing(['local' => true]);
        Http::assertSentCount(1);
    }

    public function test_curated_answer_is_used_directly_without_api(): void
    {
        Http::fake();
        SiaKnowledge::create(['question' => '¿Cuál es el horario de atención del CAT Kennedy?', 'answer' => 'De lunes a viernes de 8 a 5.', 'active' => true]);
        $this->postJson('/api/sia/chat', ['message' => 'cual es el horario de atencion del CAT Kennedy'])->assertStatus(200)->assertJson(['answer' => 'De lunes a viernes de 8 a 5.', 'local' => true]);
        $this->assertDatabaseHas('sia_messages', ['role' => 'assistant', 'source' => 'FAQ']);
        Http::assertNothingSent();
    }

    public function test_local_answers_do_not_consume_person_limits(): void
    {
        Http::fake();
        \App\Models\SiaSetting::create(['key' => 'guest_per_hour', 'value' => '1']);
        for ($i = 0; $i < 3; $i++) $this->postJson('/api/sia/chat', ['message' => 'hola'])->assertStatus(200);
    }
}
