<?php

namespace StuMason\Kick\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use StuMason\Kick\Services\QueueInspector;

class QueueController
{
    public function __construct(
        protected QueueInspector $queueInspector
    ) {}

    /**
     * Get queue overview with job counts.
     */
    public function index(): JsonResponse
    {
        $overview = $this->queueInspector->getOverview();

        return response()->json($overview);
    }

    /**
     * Get list of failed jobs.
     */
    public function failed(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 50);
        $limit = min($limit, 100); // Cap at 100

        $jobs = $this->queueInspector->getFailedJobs($limit);

        if ($jobs === null) {
            return response()->json([
                'failed_jobs' => [],
                'count' => 0,
                'error' => 'Unable to retrieve failed jobs',
            ], 503);
        }

        return response()->json([
            'failed_jobs' => $jobs,
            'count' => count($jobs),
        ]);
    }

    /**
     * Retry a specific failed job.
     */
    public function retry(string $id): JsonResponse
    {
        $result = $this->queueInspector->retryJob($id);

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * Retry all failed jobs.
     */
    public function retryAll(): JsonResponse
    {
        $result = $this->queueInspector->retryAllJobs();

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }
}
