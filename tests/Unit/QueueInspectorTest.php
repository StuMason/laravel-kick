<?php

use Illuminate\Queue\Failed\FailedJobProviderInterface;
use StuMason\Kick\Services\QueueInspector;

beforeEach(function () {
    $this->mockFailedProvider = Mockery::mock(FailedJobProviderInterface::class);
    $this->queueInspector = new QueueInspector($this->mockFailedProvider);
});

describe('getOverview', function () {
    it('returns queue overview with connection and failed count', function () {
        $this->mockFailedProvider->shouldReceive('all')->andReturn([]);

        $result = $this->queueInspector->getOverview();

        expect($result)->toHaveKeys(['connection', 'queues', 'failed_count']);
        expect($result['connection'])->toBeString();
        expect($result['queues'])->toBeArray();
        expect($result['failed_count'])->toBeInt();
    });
});

describe('getFailedJobCount', function () {
    it('returns count of failed jobs', function () {
        $failedJobs = [
            (object) ['id' => 1],
            (object) ['id' => 2],
            (object) ['id' => 3],
        ];
        $this->mockFailedProvider->shouldReceive('all')->andReturn($failedJobs);

        $result = $this->queueInspector->getFailedJobCount();

        expect($result)->toBe(3);
    });

    it('returns zero when no failed jobs', function () {
        $this->mockFailedProvider->shouldReceive('all')->andReturn([]);

        $result = $this->queueInspector->getFailedJobCount();

        expect($result)->toBe(0);
    });

    it('returns null on exception', function () {
        $this->mockFailedProvider->shouldReceive('all')->andThrow(new Exception('Database error'));

        $result = $this->queueInspector->getFailedJobCount();

        expect($result)->toBeNull();
    });
});

describe('getFailedJobs', function () {
    it('returns list of failed jobs', function () {
        $failedJobs = [
            (object) [
                'id' => 1,
                'connection' => 'redis',
                'queue' => 'default',
                'failed_at' => '2026-01-23 12:00:00',
                'exception' => 'Error message',
            ],
        ];
        $this->mockFailedProvider->shouldReceive('all')->andReturn($failedJobs);

        $result = $this->queueInspector->getFailedJobs();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
        expect($result[0])->toHaveKeys(['id', 'connection', 'queue', 'failed_at', 'exception']);
    });

    it('respects limit parameter', function () {
        $failedJobs = [];
        for ($i = 0; $i < 10; $i++) {
            $failedJobs[] = (object) [
                'id' => $i,
                'connection' => 'redis',
                'queue' => 'default',
                'failed_at' => '2026-01-23 12:00:00',
                'exception' => 'Error',
            ];
        }
        $this->mockFailedProvider->shouldReceive('all')->andReturn($failedJobs);

        $result = $this->queueInspector->getFailedJobs(5);

        expect($result)->toHaveCount(5);
    });

    it('truncates long exception messages', function () {
        $longException = str_repeat('A', 1000);
        $failedJobs = [
            (object) [
                'id' => 1,
                'connection' => 'redis',
                'queue' => 'default',
                'failed_at' => '2026-01-23 12:00:00',
                'exception' => $longException,
            ],
        ];
        $this->mockFailedProvider->shouldReceive('all')->andReturn($failedJobs);

        $result = $this->queueInspector->getFailedJobs();

        expect(strlen($result[0]['exception']))->toBeLessThan(1000);
        expect($result[0]['exception'])->toEndWith('...');
    });
});
