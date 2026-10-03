<?php

namespace Tests\Unit;

use App\Support\ResilientRateLimiter;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\TestCase;

class ResilientRateLimiterTest extends TestCase
{
    private function deadlock(): QueryException
    {
        return new QueryException('mysql', 'insert ignore into cache', [], new \Exception('SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock'));
    }

    private function limiterFailing(int $times, ?QueryException $e = null): ResilientRateLimiter
    {
        $e = $e ?? $this->deadlock();
        $store = new class($times, $e) extends Repository {
            public int $calls = 0;
            public function __construct(private int $times, private QueryException $e) { parent::__construct(new ArrayStore()); }
            public function add($key, $value, $ttl = null) { if ($this->calls++ < $this->times) { throw $this->e; } return parent::add($key, $value, $ttl); }
        };

        return new ResilientRateLimiter($store);
    }

    public function test_a_single_deadlock_is_retried_and_counts_normally(): void
    {
        $this->assertSame(1, $this->limiterFailing(1)->hit('k', 60));
    }

    public function test_a_persistent_deadlock_lets_the_request_through_instead_of_failing(): void
    {
        $this->assertSame(1, $this->limiterFailing(5)->hit('k', 60));
    }

    public function test_other_database_errors_are_not_swallowed(): void
    {
        $other = new QueryException('mysql', 'insert', [], new \Exception('SQLSTATE[42S02]: Base table or view not found'));
        $this->expectException(QueryException::class);
        $this->limiterFailing(1, $other)->hit('k', 60);
    }
}
