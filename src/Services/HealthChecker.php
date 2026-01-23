<?php

namespace StuMason\Kick\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class HealthChecker
{
    /**
     * Run all health checks and return results.
     *
     * @return array{status: string, checks: array<string, array{status: string, message: string, latency_ms?: float}>, timestamp: string}
     */
    public function check(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        // Add Redis check if configured separately from cache
        if ($this->shouldCheckRedis()) {
            $checks['redis'] = $this->checkRedis();
        }

        $overallStatus = $this->determineOverallStatus($checks);

        return [
            'status' => $overallStatus,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Check database connectivity.
     *
     * @return array{status: string, message: string, latency_ms?: float}
     */
    public function checkDatabase(): array
    {
        $start = microtime(true);

        try {
            DB::connection()->getPdo();
            $latency = (microtime(true) - $start) * 1000;

            return [
                'status' => 'healthy',
                'message' => 'Database connection successful',
                'latency_ms' => round($latency, 2),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check cache connectivity.
     *
     * @return array{status: string, message: string, latency_ms?: float}
     */
    public function checkCache(): array
    {
        $start = microtime(true);
        $testKey = 'kick_health_check_'.uniqid();
        $testValue = 'test_'.time();

        try {
            Cache::put($testKey, $testValue, 10);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            if ($retrieved !== $testValue) {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Cache read/write verification failed',
                ];
            }

            $latency = (microtime(true) - $start) * 1000;

            return [
                'status' => 'healthy',
                'message' => 'Cache read/write successful',
                'latency_ms' => round($latency, 2),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache operation failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check storage writability.
     *
     * @return array{status: string, message: string, latency_ms?: float}
     */
    public function checkStorage(): array
    {
        $start = microtime(true);
        $testFile = 'kick_health_check_'.uniqid().'.txt';
        $testContent = 'health_check_'.time();

        try {
            $disk = Storage::disk('local');

            $disk->put($testFile, $testContent);
            $retrieved = $disk->get($testFile);
            $disk->delete($testFile);

            if ($retrieved !== $testContent) {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Storage read/write verification failed',
                ];
            }

            $latency = (microtime(true) - $start) * 1000;

            return [
                'status' => 'healthy',
                'message' => 'Storage read/write successful',
                'latency_ms' => round($latency, 2),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Storage operation failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Check Redis connectivity (when used separately from cache).
     *
     * @return array{status: string, message: string, latency_ms?: float}
     */
    public function checkRedis(): array
    {
        $start = microtime(true);

        try {
            $redis = Redis::connection();
            $redis->ping();
            $latency = (microtime(true) - $start) * 1000;

            return [
                'status' => 'healthy',
                'message' => 'Redis connection successful',
                'latency_ms' => round($latency, 2),
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Redis connection failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Determine if Redis should be checked separately.
     */
    protected function shouldCheckRedis(): bool
    {
        $cacheDriver = config('cache.default');
        $sessionDriver = config('session.driver');
        $queueConnection = config('queue.default');

        // Check Redis if it's used for session or queue but not cache
        // (if cache uses Redis, the cache check already covers it)
        if ($cacheDriver === 'redis') {
            return false;
        }

        return $sessionDriver === 'redis' || $queueConnection === 'redis';
    }

    /**
     * Determine overall health status from individual checks.
     *
     * @param  array<string, array{status: string, message: string}>  $checks
     */
    protected function determineOverallStatus(array $checks): string
    {
        foreach ($checks as $check) {
            if ($check['status'] === 'unhealthy') {
                return 'unhealthy';
            }
        }

        return 'healthy';
    }
}
