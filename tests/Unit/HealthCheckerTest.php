<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use StuMason\Kick\Services\HealthChecker;

beforeEach(function () {
    $this->healthChecker = new HealthChecker;
});

describe('check method', function () {
    it('returns overall health status with all checks', function () {
        $result = $this->healthChecker->check();

        expect($result)->toHaveKeys(['status', 'checks', 'timestamp']);
        expect($result['checks'])->toHaveKeys(['database', 'cache', 'storage']);
        expect($result['status'])->toBeIn(['healthy', 'unhealthy']);
        expect($result['timestamp'])->toBeString();
    });

    it('returns unhealthy when any check fails', function () {
        // Mock database to fail
        DB::shouldReceive('connection->getPdo')->andThrow(new Exception('Connection failed'));

        $result = $this->healthChecker->check();

        expect($result['status'])->toBe('unhealthy');
        expect($result['checks']['database']['status'])->toBe('unhealthy');
    });
});

describe('checkDatabase', function () {
    it('returns healthy status on successful connection', function () {
        $result = $this->healthChecker->checkDatabase();

        expect($result['status'])->toBe('healthy');
        expect($result['message'])->toBe('Database connection successful');
        expect($result)->toHaveKey('latency_ms');
        expect($result['latency_ms'])->toBeFloat();
    });

    it('returns unhealthy status on connection failure', function () {
        DB::shouldReceive('connection->getPdo')->andThrow(new Exception('Connection refused'));

        $result = $this->healthChecker->checkDatabase();

        expect($result['status'])->toBe('unhealthy');
        expect($result['message'])->toContain('Connection refused');
    });
});

describe('checkCache', function () {
    it('returns healthy status on successful read/write', function () {
        $result = $this->healthChecker->checkCache();

        expect($result['status'])->toBe('healthy');
        expect($result['message'])->toBe('Cache read/write successful');
        expect($result)->toHaveKey('latency_ms');
    });

    it('returns unhealthy status on cache failure', function () {
        Cache::shouldReceive('put')->andThrow(new Exception('Cache unavailable'));

        $result = $this->healthChecker->checkCache();

        expect($result['status'])->toBe('unhealthy');
        expect($result['message'])->toContain('Cache unavailable');
    });
});

describe('checkStorage', function () {
    it('returns healthy status on successful read/write', function () {
        Storage::fake('local');

        $result = $this->healthChecker->checkStorage();

        expect($result['status'])->toBe('healthy');
        expect($result['message'])->toBe('Storage read/write successful');
        expect($result)->toHaveKey('latency_ms');
    });
});
