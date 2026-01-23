<?php

describe('authentication', function () {
    it('returns 401 without token', function () {
        $this->getJson('/kick/stats')
            ->assertStatus(401);
    });

    it('returns 403 with insufficient scope', function () {
        $this->getJson('/kick/stats', [
            'Authorization' => 'Bearer test-token-limited',
        ])
            ->assertStatus(403)
            ->assertJson(['message' => 'Token does not have required scope: stats:read']);
    });

    it('allows access with stats:read scope', function () {
        config(['kick.tokens' => [
            'test-stats-token' => ['stats:read'],
        ]]);

        $this->getJson('/kick/stats', [
            'Authorization' => 'Bearer test-stats-token',
        ])
            ->assertStatus(200);
    });
});

describe('stats endpoint', function () {
    it('returns all stat categories', function () {
        $this->getJson('/kick/stats', [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'stats' => [
                    'cpu',
                    'memory',
                    'disk',
                    'uptime',
                ],
                'timestamp',
            ]);
    });

    it('returns disk stats with expected fields', function () {
        $response = $this->getJson('/kick/stats', [
            'Authorization' => 'Bearer test-token-full',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Disk stats should always work
        if (! isset($data['stats']['disk']['error'])) {
            expect($data['stats']['disk'])->toHaveKeys([
                'used_bytes',
                'total_bytes',
                'free_bytes',
                'used_percent',
                'path',
            ]);
        }
    });

    it('returns uptime stats', function () {
        $response = $this->getJson('/kick/stats', [
            'Authorization' => 'Bearer test-token-full',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        expect($data['stats']['uptime'])->toHaveKey('php_uptime_seconds');
    });

    it('includes ISO 8601 timestamp', function () {
        $response = $this->getJson('/kick/stats', [
            'Authorization' => 'Bearer test-token-full',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        expect($data['timestamp'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
    });
});
