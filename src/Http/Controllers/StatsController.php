<?php

namespace StuMason\Kick\Http\Controllers;

use Illuminate\Http\JsonResponse;
use StuMason\Kick\Services\StatsCollector;

class StatsController
{
    public function __construct(
        protected StatsCollector $statsCollector
    ) {}

    /**
     * Get system/container statistics.
     */
    public function __invoke(): JsonResponse
    {
        $stats = $this->statsCollector->collect();

        return response()->json([
            'stats' => $stats,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
