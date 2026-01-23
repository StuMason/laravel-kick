<?php

namespace StuMason\Kick\Services;

class StatsCollector
{
    /**
     * Collect all system/container statistics.
     *
     * @return array{cpu: array, memory: array, disk: array, uptime: array}
     */
    public function collect(): array
    {
        return [
            'cpu' => $this->getCpuStats(),
            'memory' => $this->getMemoryStats(),
            'disk' => $this->getDiskStats(),
            'uptime' => $this->getUptimeStats(),
        ];
    }

    /**
     * Get CPU statistics.
     *
     * @return array{usage_percent?: float, cores?: float|int, load_average?: array<string, float>, error?: string}
     */
    public function getCpuStats(): array
    {
        // Try cgroups v2 first (modern Docker/Kubernetes)
        if ($stats = $this->getCgroupsV2CpuStats()) {
            return $stats;
        }

        // Try cgroups v1
        if ($stats = $this->getCgroupsV1CpuStats()) {
            return $stats;
        }

        // Fall back to system stats
        return $this->getSystemCpuStats();
    }

    /**
     * Get memory statistics.
     *
     * @return array{used_bytes?: int, total_bytes?: int, used_percent?: float, available_bytes?: int, error?: string}
     */
    public function getMemoryStats(): array
    {
        // Try cgroups v2 first
        if ($stats = $this->getCgroupsV2MemoryStats()) {
            return $stats;
        }

        // Try cgroups v1
        if ($stats = $this->getCgroupsV1MemoryStats()) {
            return $stats;
        }

        // Fall back to system stats
        return $this->getSystemMemoryStats();
    }

    /**
     * Get disk statistics.
     *
     * @return array{used_bytes?: int, total_bytes?: int, free_bytes?: int, used_percent?: float, path?: string, error?: string}
     */
    public function getDiskStats(): array
    {
        $path = base_path();

        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        if ($total === false || $free === false) {
            return ['error' => 'Unable to read disk statistics'];
        }

        $used = $total - $free;
        $usedPercent = $total > 0 ? ($used / $total) * 100 : 0;

        return [
            'used_bytes' => (int) $used,
            'total_bytes' => (int) $total,
            'free_bytes' => (int) $free,
            'used_percent' => round($usedPercent, 2),
            'path' => $path,
        ];
    }

    /**
     * Get uptime statistics.
     *
     * @return array{system_uptime_seconds?: int, php_uptime_seconds?: int, error?: string}
     */
    public function getUptimeStats(): array
    {
        $stats = [];

        // System uptime from /proc/uptime
        if (is_readable('/proc/uptime')) {
            $uptime = @file_get_contents('/proc/uptime');
            if ($uptime !== false) {
                $parts = explode(' ', trim($uptime));
                $stats['system_uptime_seconds'] = (int) floor((float) $parts[0]);
            }
        }

        // PHP process start time
        $stats['php_uptime_seconds'] = (int) (time() - ($_SERVER['REQUEST_TIME'] ?? time()));

        return $stats;
    }

    /**
     * Get CPU stats from cgroups v2.
     *
     * @return array{usage_percent?: float, throttled_periods?: int, throttled_time_ns?: int}|null
     */
    protected function getCgroupsV2CpuStats(): ?array
    {
        $cpuStatPath = '/sys/fs/cgroup/cpu.stat';

        if (! is_readable($cpuStatPath)) {
            return null;
        }

        $content = @file_get_contents($cpuStatPath);
        if ($content === false) {
            return null;
        }

        $stats = [];
        foreach (explode("\n", $content) as $line) {
            if (empty($line)) {
                continue;
            }
            $parts = explode(' ', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }
            [$key, $value] = $parts;
            $stats[$key] = (int) $value;
        }

        $result = [];

        if (isset($stats['usage_usec'])) {
            // Get CPU quota if set
            $quotaPath = '/sys/fs/cgroup/cpu.max';
            if (is_readable($quotaPath)) {
                $quota = @file_get_contents($quotaPath);
                if ($quota !== false && ! str_starts_with($quota, 'max')) {
                    [$limit, $period] = explode(' ', trim($quota));
                    $cores = (int) $limit / (int) $period;
                    $result['cores'] = round($cores, 2);
                }
            }
        }

        if (isset($stats['nr_throttled'])) {
            $result['throttled_periods'] = $stats['nr_throttled'];
        }

        if (isset($stats['throttled_usec'])) {
            $result['throttled_time_ns'] = $stats['throttled_usec'] * 1000;
        }

        // Add load average if available
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if ($load !== false) {
                $result['load_average'] = [
                    '1m' => round($load[0], 2),
                    '5m' => round($load[1], 2),
                    '15m' => round($load[2], 2),
                ];
            }
        }

        return ! empty($result) ? $result : null;
    }

    /**
     * Get CPU stats from cgroups v1.
     *
     * @return array{usage_percent?: float, cores?: float, load_average?: array}|null
     */
    protected function getCgroupsV1CpuStats(): ?array
    {
        $cpuAcctPath = '/sys/fs/cgroup/cpu,cpuacct/cpuacct.usage';
        $cpuAltPath = '/sys/fs/cgroup/cpuacct/cpuacct.usage';

        $path = is_readable($cpuAcctPath) ? $cpuAcctPath : (is_readable($cpuAltPath) ? $cpuAltPath : null);

        if ($path === null) {
            return null;
        }

        $result = [];

        // Check for CPU quota
        $quotaPath = dirname($path).'/cpu.cfs_quota_us';
        $periodPath = dirname($path).'/cpu.cfs_period_us';

        if (is_readable($quotaPath) && is_readable($periodPath)) {
            $quota = (int) trim((string) @file_get_contents($quotaPath));
            $period = (int) trim((string) @file_get_contents($periodPath));

            if ($quota > 0 && $period > 0) {
                $result['cores'] = round($quota / $period, 2);
            }
        }

        // Add load average
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if ($load !== false) {
                $result['load_average'] = [
                    '1m' => round($load[0], 2),
                    '5m' => round($load[1], 2),
                    '15m' => round($load[2], 2),
                ];
            }
        }

        return ! empty($result) ? $result : null;
    }

    /**
     * Get system CPU stats (non-containerized).
     *
     * @return array{cores?: int, load_average?: array, error?: string}
     */
    protected function getSystemCpuStats(): array
    {
        $result = [];

        // Try to get CPU count
        if (is_readable('/proc/cpuinfo')) {
            $cpuinfo = @file_get_contents('/proc/cpuinfo');
            if ($cpuinfo !== false) {
                $result['cores'] = substr_count($cpuinfo, 'processor');
            }
        }

        // Load average
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if ($load !== false) {
                $result['load_average'] = [
                    '1m' => round($load[0], 2),
                    '5m' => round($load[1], 2),
                    '15m' => round($load[2], 2),
                ];
            }
        }

        if (empty($result)) {
            return ['error' => 'Unable to read CPU statistics'];
        }

        return $result;
    }

    /**
     * Get memory stats from cgroups v2.
     *
     * @return array{used_bytes?: int, total_bytes?: int, used_percent?: float, available_bytes?: int}|null
     */
    protected function getCgroupsV2MemoryStats(): ?array
    {
        $currentPath = '/sys/fs/cgroup/memory.current';
        $maxPath = '/sys/fs/cgroup/memory.max';

        if (! is_readable($currentPath)) {
            return null;
        }

        $current = @file_get_contents($currentPath);
        if ($current === false) {
            return null;
        }

        $used = (int) trim($current);
        $result = ['used_bytes' => $used];

        if (is_readable($maxPath)) {
            $max = trim((string) @file_get_contents($maxPath));
            if ($max !== 'max' && is_numeric($max)) {
                $total = (int) $max;
                $result['total_bytes'] = $total;
                $result['available_bytes'] = $total - $used;
                $result['used_percent'] = $total > 0 ? round(($used / $total) * 100, 2) : 0;
            }
        }

        return $result;
    }

    /**
     * Get memory stats from cgroups v1.
     *
     * @return array{used_bytes?: int, total_bytes?: int, used_percent?: float, available_bytes?: int}|null
     */
    protected function getCgroupsV1MemoryStats(): ?array
    {
        $usagePath = '/sys/fs/cgroup/memory/memory.usage_in_bytes';
        $limitPath = '/sys/fs/cgroup/memory/memory.limit_in_bytes';

        if (! is_readable($usagePath)) {
            return null;
        }

        $usage = @file_get_contents($usagePath);
        if ($usage === false) {
            return null;
        }

        $used = (int) trim($usage);
        $result = ['used_bytes' => $used];

        if (is_readable($limitPath)) {
            $limit = (int) trim((string) @file_get_contents($limitPath));
            // Very high limit means no limit set (usually ~9223372036854771712)
            if ($limit > 0 && $limit < 9000000000000000000) {
                $result['total_bytes'] = $limit;
                $result['available_bytes'] = $limit - $used;
                $result['used_percent'] = round(($used / $limit) * 100, 2);
            }
        }

        return $result;
    }

    /**
     * Get system memory stats from /proc/meminfo.
     *
     * @return array{used_bytes?: int, total_bytes?: int, used_percent?: float, available_bytes?: int, error?: string}
     */
    protected function getSystemMemoryStats(): array
    {
        if (! is_readable('/proc/meminfo')) {
            return ['error' => 'Unable to read memory statistics'];
        }

        $meminfo = @file_get_contents('/proc/meminfo');
        if ($meminfo === false) {
            return ['error' => 'Unable to read memory statistics'];
        }

        $stats = [];
        foreach (explode("\n", $meminfo) as $line) {
            if (preg_match('/^(\w+):\s+(\d+)/', $line, $matches)) {
                $stats[$matches[1]] = (int) $matches[2] * 1024; // Convert from kB to bytes
            }
        }

        if (! isset($stats['MemTotal'])) {
            return ['error' => 'Unable to parse memory statistics'];
        }

        $total = $stats['MemTotal'];
        $available = $stats['MemAvailable'] ?? ($stats['MemFree'] ?? 0);
        $used = $total - $available;

        return [
            'used_bytes' => $used,
            'total_bytes' => $total,
            'available_bytes' => $available,
            'used_percent' => $total > 0 ? round(($used / $total) * 100, 2) : 0,
        ];
    }
}
