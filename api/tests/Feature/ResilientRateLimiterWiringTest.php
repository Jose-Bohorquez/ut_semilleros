<?php

namespace Tests\Feature;

use App\Support\ResilientRateLimiter;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\RateLimiter as RateLimiterFacade;
use Tests\TestCase;

class ResilientRateLimiterWiringTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    public function test_the_application_uses_the_resilient_limiter_for_throttle_and_login(): void
    {
        $this->assertInstanceOf(ResilientRateLimiter::class, app(RateLimiter::class));
        $this->assertInstanceOf(ResilientRateLimiter::class, RateLimiterFacade::getFacadeRoot());
    }

    public function test_it_still_counts_and_blocks_normally(): void
    {
        $key = 'wiring-' . uniqid();
        RateLimiterFacade::hit($key, 60);
        RateLimiterFacade::hit($key, 60);

        $this->assertTrue(RateLimiterFacade::tooManyAttempts($key, 2));
        $this->assertFalse(RateLimiterFacade::tooManyAttempts($key, 3));
        RateLimiterFacade::clear($key);
    }
}
