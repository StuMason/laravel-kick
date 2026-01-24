<?php

describe('authentication', function () {
    it('returns 401 without token for queue overview', function () {
        $this->getJson('/kick/queue')
            ->assertStatus(401);
    });

    it('returns 403 with insufficient scope for queue overview', function () {
        $this->getJson('/kick/queue', [
            'Authorization' => 'Bearer test-token-limited',
        ])
            ->assertStatus(403)
            ->assertJson(['message' => 'Token does not have required scope: queue:read']);
    });

    it('returns 403 with insufficient scope for retry', function () {
        config(['kick.tokens' => [
            'test-queue-read' => ['queue:read'],
        ]]);

        $this->postJson('/kick/queue/retry/1', [], [
            'Authorization' => 'Bearer test-queue-read',
        ])
            ->assertStatus(403)
            ->assertJson(['message' => 'Token does not have required scope: queue:retry']);
    });
});

describe('queue overview endpoint', function () {
    it('returns queue overview', function () {
        $this->getJson('/kick/queue', [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'connection',
                'queues',
                'failed_count',
            ]);
    });

    it('returns connection name', function () {
        $response = $this->getJson('/kick/queue', [
            'Authorization' => 'Bearer test-token-full',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        expect($data['connection'])->toBe(config('queue.default'));
    });
});

describe('failed jobs endpoint', function () {
    it('returns failed jobs list when available', function () {
        $mockInspector = Mockery::mock(\StuMason\Kick\Services\QueueInspector::class);
        $mockInspector->shouldReceive('getFailedJobs')->with(50)->andReturn([]);
        $this->app->instance(\StuMason\Kick\Services\QueueInspector::class, $mockInspector);

        $this->getJson('/kick/queue/failed', [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'failed_jobs',
                'count',
            ]);
    });

    it('returns 503 when failed jobs unavailable', function () {
        $mockInspector = Mockery::mock(\StuMason\Kick\Services\QueueInspector::class);
        $mockInspector->shouldReceive('getFailedJobs')->andReturn(null);
        $this->app->instance(\StuMason\Kick\Services\QueueInspector::class, $mockInspector);

        $this->getJson('/kick/queue/failed', [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(503)
            ->assertJsonStructure([
                'failed_jobs',
                'count',
                'error',
            ]);
    });

    it('respects limit parameter', function () {
        $mockInspector = Mockery::mock(\StuMason\Kick\Services\QueueInspector::class);
        $mockInspector->shouldReceive('getFailedJobs')->with(5)->andReturn([]);
        $this->app->instance(\StuMason\Kick\Services\QueueInspector::class, $mockInspector);

        $this->getJson('/kick/queue/failed?limit=5', [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(200);
    });

    it('caps limit at 100', function () {
        $mockInspector = Mockery::mock(\StuMason\Kick\Services\QueueInspector::class);
        $mockInspector->shouldReceive('getFailedJobs')->with(100)->andReturn([]);
        $this->app->instance(\StuMason\Kick\Services\QueueInspector::class, $mockInspector);

        $response = $this->getJson('/kick/queue/failed?limit=200', [
            'Authorization' => 'Bearer test-token-full',
        ]);

        $response->assertStatus(200);
        // The limit is applied internally, we just check it doesn't error
    });
});

describe('retry endpoints', function () {
    it('retry endpoint returns result', function () {
        $mockInspector = Mockery::mock(\StuMason\Kick\Services\QueueInspector::class);
        $mockInspector->shouldReceive('retryJob')->with('nonexistent-id')->andReturn([
            'success' => false,
            'message' => 'Job not found.',
        ]);
        $this->app->instance(\StuMason\Kick\Services\QueueInspector::class, $mockInspector);

        $this->postJson('/kick/queue/retry/nonexistent-id', [], [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(400)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    });

    it('retry-all endpoint returns result', function () {
        $mockInspector = Mockery::mock(\StuMason\Kick\Services\QueueInspector::class);
        $mockInspector->shouldReceive('retryAllJobs')->andReturn([
            'success' => true,
            'message' => 'No failed jobs to retry.',
            'count' => 0,
        ]);
        $this->app->instance(\StuMason\Kick\Services\QueueInspector::class, $mockInspector);

        $this->postJson('/kick/queue/retry-all', [], [
            'Authorization' => 'Bearer test-token-full',
        ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'count',
            ]);
    });
});
