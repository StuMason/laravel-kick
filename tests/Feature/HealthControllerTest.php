<?php

use Illuminate\Support\Facades\DB;

describe('authentication', function () {
    it('returns 401 without token', function () {
        $this->getJson('/kick/health')
            ->assertStatus(401);
    });

    it('returns 403 with insufficient scope', function () {
        $this->getJson('/kick/health', [
            'Authorization' => 'Bearer test-token-limited',
        ])
            ->assertStatus(403)
            ->assertJson(['message' => 'Token does not have required scope: health:read']);
    });

    it('allows access with health:read scope', function () {
        config(['kick.tokens' => [
            'test-health-token' => ['health:read'],
        ]]);

        $this->getJson('/kick/health', [
            'Authorization' => 'Bearer test-health-token',
        ])
            ->assertStatus(200);
    });
});

describe('health endpoint', function () {
    it('returns health status with all checks', function () {
        $this->getJson('/kick/health', [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'checks' => [
                    'database' => ['status', 'message'],
                    'cache' => ['status', 'message'],
                    'storage' => ['status', 'message'],
                ],
                'timestamp',
            ]);
    });

    it('returns healthy status when all checks pass', function () {
        $this->getJson('/kick/health', [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
            ]);
    });

    it('returns 503 when health check fails', function () {
        // Mock database to fail
        DB::shouldReceive('connection->getPdo')->andThrow(new Exception('Connection failed'));

        $this->getJson('/kick/health', [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
            ]);
    });

    it('includes latency measurements for healthy checks', function () {
        $response = $this->getJson('/kick/health', [
            'Authorization' => 'Bearer test-token-full',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        expect($data['checks']['database'])->toHaveKey('latency_ms');
        expect($data['checks']['cache'])->toHaveKey('latency_ms');
        expect($data['checks']['storage'])->toHaveKey('latency_ms');
    });
});
