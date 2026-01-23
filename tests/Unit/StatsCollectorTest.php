<?php

use StuMason\Kick\Services\StatsCollector;

beforeEach(function () {
    $this->statsCollector = new StatsCollector;
});

describe('collect method', function () {
    it('returns all stat categories', function () {
        $result = $this->statsCollector->collect();

        expect($result)->toHaveKeys(['cpu', 'memory', 'disk', 'uptime']);
    });
});

describe('getCpuStats', function () {
    it('returns cpu statistics', function () {
        $result = $this->statsCollector->getCpuStats();

        // Should return either stats or an error
        expect($result)->toBeArray();

        // If we got stats, check for expected keys
        if (! isset($result['error'])) {
            // At minimum should have one of these keys on most systems
            $hasExpectedKey = isset($result['cores']) ||
                              isset($result['load_average']) ||
                              isset($result['usage_percent']);
            expect($hasExpectedKey)->toBeTrue();
        }
    });
});

describe('getMemoryStats', function () {
    it('returns memory statistics', function () {
        $result = $this->statsCollector->getMemoryStats();

        expect($result)->toBeArray();

        if (! isset($result['error'])) {
            expect($result)->toHaveKey('used_bytes');
        }
    });
});

describe('getDiskStats', function () {
    it('returns disk statistics', function () {
        $result = $this->statsCollector->getDiskStats();

        expect($result)->toBeArray();

        if (! isset($result['error'])) {
            expect($result)->toHaveKeys(['used_bytes', 'total_bytes', 'free_bytes', 'used_percent', 'path']);
            expect($result['used_percent'])->toBeFloat();
            expect($result['used_percent'])->toBeGreaterThanOrEqual(0);
            expect($result['used_percent'])->toBeLessThanOrEqual(100);
        }
    });

    it('calculates used percentage correctly', function () {
        $result = $this->statsCollector->getDiskStats();

        if (! isset($result['error'])) {
            $calculatedPercent = ($result['used_bytes'] / $result['total_bytes']) * 100;
            expect($result['used_percent'])->toBe(round($calculatedPercent, 2));
        }
    });
});

describe('getUptimeStats', function () {
    it('returns uptime statistics', function () {
        $result = $this->statsCollector->getUptimeStats();

        expect($result)->toBeArray();

        // PHP uptime should always be available
        expect($result)->toHaveKey('php_uptime_seconds');
        expect($result['php_uptime_seconds'])->toBeInt();
        expect($result['php_uptime_seconds'])->toBeGreaterThanOrEqual(0);
    });
});
