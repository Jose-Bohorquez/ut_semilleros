<?php

namespace Tests\Feature;

use App\Models\Audit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PruneAuditsTest extends TestCase
{
    use RefreshDatabase;

    private function audit(string $createdAt, string $action = 'LOGIN'): int
    {
        return DB::table('audits')->insertGetId([
            'user_id' => null, 'action' => $action, 'table_name' => 'users', 'record_id' => 1, 'created_at' => $createdAt,
        ]);
    }

    private function clean(): void
    {
        DB::table('audits')->delete();   // las fábricas de usuarios también auditan
    }

    public function test_dry_run_only_counts(): void
    {
        $this->clean();
        $this->audit(now()->subMonths(30)->toDateTimeString());

        $this->artisan('audits:prune --dry-run')->expectsOutputToContain('1 registro(s)')->assertExitCode(0);

        $this->assertSame(1, DB::table('audits')->count());
    }

    public function test_it_deletes_only_what_is_older_than_the_retention_period_and_leaves_a_trace(): void
    {
        $this->clean();
        $old  = $this->audit(now()->subMonths(30)->toDateTimeString());
        $edge = $this->audit(now()->subMonths(25)->toDateTimeString());
        $keep = $this->audit(now()->subMonths(23)->toDateTimeString());
        $new  = $this->audit(now()->toDateTimeString());

        $this->artisan('audits:prune --force')->assertExitCode(0);

        $this->assertDatabaseMissing('audits', ['id' => $old]);
        $this->assertDatabaseMissing('audits', ['id' => $edge]);
        $this->assertDatabaseHas('audits', ['id' => $keep]);
        $this->assertDatabaseHas('audits', ['id' => $new]);
        $trace = Audit::where('action', 'PRUNE')->firstOrFail();
        $this->assertSame(2, $trace->new_values['deleted']);
        $this->assertSame(24, $trace->new_values['retention_months']);
    }

    public function test_the_months_option_overrides_the_default(): void
    {
        $this->clean();
        $a = $this->audit(now()->subMonths(8)->toDateTimeString());
        $b = $this->audit(now()->subMonths(2)->toDateTimeString());

        $this->artisan('audits:prune --months=7 --force')->assertExitCode(0);

        $this->assertDatabaseMissing('audits', ['id' => $a]);
        $this->assertDatabaseHas('audits', ['id' => $b]);
    }

    public function test_it_refuses_a_retention_shorter_than_six_months(): void
    {
        $this->clean();
        $id = $this->audit(now()->subMonths(5)->toDateTimeString());

        $this->artisan('audits:prune --months=3 --force')->assertExitCode(2);

        $this->assertDatabaseHas('audits', ['id' => $id]);
    }

    public function test_the_archive_is_written_before_deleting(): void
    {
        $this->clean();
        $id = $this->audit(now()->subMonths(30)->toDateTimeString(), '=CMD()');
        $path = sys_get_temp_dir() . '/audits_archive_' . uniqid() . '.csv';

        $this->artisan("audits:prune --force --archive={$path}")->assertExitCode(0);

        $csv = file_get_contents($path);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString("'=CMD()", $csv, 'neutraliza fórmulas');
        $this->assertStringContainsString((string) $id, $csv);
        $this->assertDatabaseMissing('audits', ['id' => $id]);
        @unlink($path);
    }

    public function test_if_the_archive_cannot_be_written_nothing_is_deleted(): void
    {
        $this->clean();
        $id = $this->audit(now()->subMonths(30)->toDateTimeString());

        $this->artisan('audits:prune --force --archive=/ruta/que/no/existe/archivo.csv')->assertExitCode(1);

        $this->assertDatabaseHas('audits', ['id' => $id]);
    }

    public function test_nothing_to_delete_is_not_an_error(): void
    {
        $this->clean();
        $this->audit(now()->toDateTimeString());

        $this->artisan('audits:prune --force')->expectsOutputToContain('0 registro(s)')->assertExitCode(0);
    }
}
