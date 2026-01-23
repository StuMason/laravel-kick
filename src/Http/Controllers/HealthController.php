<?php

namespace StuMason\Kick\Http\Controllers;

use Illuminate\Http\JsonResponse;
use StuMason\Kick\Services\HealthChecker;

class HealthController
{
    public function __construct(
        protected HealthChecker $healthChecker
    ) {}

    /**
     * Get application health status.
     */
    public function __invoke(): JsonResponse
    {
        $health = $this->healthChecker->check();

        $statusCode = $health['status'] === 'healthy' ? 200 : 503;

        return response()->json($health, $statusCode);
    }
}
