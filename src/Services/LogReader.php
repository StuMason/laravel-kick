<?php

namespace StuMason\Kick\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;
use SplFileInfo;
use SplFileObject;

class LogReader
{
    /**
     * Maximum file size in bytes for unfiltered reads (50MB).
     */
    protected const MAX_UNFILTERED_SIZE = 50 * 1024 * 1024;

    /**
     * @param  array<string>  $allowedExtensions
     */
    public function __construct(
        protected string $basePath,
        protected array $allowedExtensions = ['log'],
        protected int $maxLines = 500,
        protected ?PiiScrubber $scrubber = null
    ) {
        $this->scrubber = $scrubber ?? new PiiScrubber;
    }

    /**
     * List all available log files.
     *
     * @return Collection<int, array{name: string, size: int, modified: int}>
     */
    public function listFiles(): Collection
    {
        if (! is_dir($this->basePath)) {
            return collect();
        }

        $files = collect(scandir($this->basePath) ?: [])
            ->filter(fn ($file) => $this->isAllowedFile($file))
            ->map(function ($file) {
                $path = $this->basePath.DIRECTORY_SEPARATOR.$file;
                $info = new SplFileInfo($path);

                return [
                    'name' => $file,
                    'size' => (int) $info->getSize(),
                    'modified' => (int) $info->getMTime(),
                ];
            })
            ->sortByDesc('modified')
            ->values();

        return $files;
    }

    /**
     * Read entries from a log file.
     *
     * @return array{entries: array<int, array{line: int, content: string}>, total_lines: int, has_more: bool}
     */
    public function read(string $filename, int $lines = 100, int $offset = 0, ?string $search = null, ?string $level = null): array
    {
        $this->validateFilename($filename);

        $path = $this->basePath.DIRECTORY_SEPARATOR.$filename;

        if (! file_exists($path)) {
            throw new InvalidArgumentException("Log file not found: {$filename}");
        }

        $lines = min($lines, $this->maxLines);
        $hasFilters = $search !== null || $level !== null;
        $fileSize = filesize($path);

        if ($fileSize === false) {
            throw new RuntimeException(
                sprintf('Unable to determine file size for: %s', $filename)
            );
        }

        // Check file size for unfiltered reads
        if (! $hasFilters && $fileSize > self::MAX_UNFILTERED_SIZE) {
            $sizeMb = round($fileSize / (1024 * 1024), 1);
            throw new RuntimeException(
                "Log file too large ({$sizeMb} MB). Use search or level filter to read large files."
            );
        }

        // Choose strategy based on filters
        if ($hasFilters) {
            return $this->readWithGrep($path, $lines, $offset, $search, $level);
        }

        return $this->readWithSplFileObject($path, $lines, $offset);
    }

    /**
     * Tail the most recent entries from a log file.
     *
     * @return array<int, array{line: int, content: string}>
     */
    public function tail(string $filename, int $lines = 50): array
    {
        $result = $this->read($filename, $lines, 0);

        return $result['entries'];
    }

    /**
     * Read last N lines using SplFileObject (memory-efficient).
     *
     * @return array{entries: array<int, array{line: int, content: string}>, total_lines: int, has_more: bool}
     */
    protected function readWithSplFileObject(string $path, int $lines, int $offset): array
    {
        try {
            $file = new SplFileObject($path, 'r');
        } catch (\Exception $e) {
            throw new InvalidArgumentException(
                sprintf('Unable to read log file: %s', $e->getMessage())
            );
        }

        // Get total line count by seeking to end
        // seek(PHP_INT_MAX) moves to the last line, key() returns its 0-based index
        $file->seek(PHP_INT_MAX);
        $lastLineIndex = $file->key();

        // Check if file is empty (key() returns 0 for empty file or single-line file)
        $file->rewind();
        $firstLine = $file->current();
        if ($firstLine === false || (string) $firstLine === '') {
            return [
                'entries' => [],
                'total_lines' => 0,
                'has_more' => false,
            ];
        }

        // Total lines is last index + 1
        $totalLines = $lastLineIndex + 1;

        // Calculate which lines to read (from end, accounting for offset)
        $startLine = max(0, $totalLines - $offset - $lines);
        $endLine = $totalLines - $offset;

        if ($endLine <= 0) {
            return [
                'entries' => [],
                'total_lines' => $totalLines,
                'has_more' => false,
            ];
        }

        $entries = [];
        $file->seek($startLine);

        while ($file->key() < $endLine && ! $file->eof()) {
            $lineNum = $file->key();
            $content = rtrim((string) $file->current(), "\r\n");

            if ($content !== '') {
                $entries[] = [
                    'line' => $lineNum + 1,
                    'content' => $this->scrubber->scrub($content),
                ];
            }

            $file->next();
        }

        // Reverse to show most recent first
        $entries = array_reverse($entries);

        return [
            'entries' => $entries,
            'total_lines' => $totalLines,
            'has_more' => ($offset + $lines) < $totalLines,
        ];
    }

    /**
     * Read filtered lines using grep (handles large files efficiently).
     *
     * @return array{entries: array<int, array{line: int, content: string}>, total_lines: int, has_more: bool}
     */
    protected function readWithGrep(string $path, int $lines, int $offset, ?string $search, ?string $level): array
    {
        // Build grep pattern
        $patterns = [];

        if ($level !== null) {
            $patterns[] = '\b'.strtoupper($level).'\b';
        }

        if ($search !== null) {
            $patterns[] = preg_quote($search, '/');
        }

        // Try system grep first
        if ($this->isGrepAvailable()) {
            return $this->executeGrep($path, $patterns, $lines, $offset, $search, $level);
        }

        // Fallback to PHP-based filtering (still uses SplFileObject for memory efficiency)
        return $this->readWithPhpFilter($path, $lines, $offset, $search, $level);
    }

    /**
     * Execute grep command using proc_open for safety.
     *
     * @param  array<string>  $patterns
     * @return array{entries: array<int, array{line: int, content: string}>, total_lines: int, has_more: bool}
     */
    protected function executeGrep(string $path, array $patterns, int $lines, int $offset, ?string $search, ?string $level): array
    {
        $pattern = implode('.*', $patterns);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        // Build grep command with -n for line numbers, -i for case-insensitive, -E for extended regex
        $cmd = [
            'grep',
            '-n',
            '-i',
            '-E',
            $pattern,
            $path,
        ];

        $process = proc_open($cmd, $descriptors, $pipes);

        if (! is_resource($process)) {
            // Fallback to PHP filtering if grep fails to start
            return $this->readWithPhpFilter($path, $lines, $offset, $search, $level);
        }

        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        // Check for stream read failures
        if ($output === false) {
            return $this->readWithPhpFilter($path, $lines, $offset, $search, $level);
        }

        // Exit code 0 = matches found, 1 = no matches (both OK), 2+ = error
        if ($exitCode >= 2) {
            // Grep encountered an error, fall back to PHP filtering
            // stderr contains: $stderr (available for debugging if needed)
            return $this->readWithPhpFilter($path, $lines, $offset, $search, $level);
        }

        // Parse grep output (format: line_number:content)
        $allMatches = [];
        foreach (explode("\n", $output) as $line) {
            if (empty($line)) {
                continue;
            }

            $colonPos = strpos($line, ':');
            if ($colonPos !== false) {
                $lineNum = (int) substr($line, 0, $colonPos);
                $content = substr($line, $colonPos + 1);

                $allMatches[] = [
                    'line' => $lineNum,
                    'content' => $this->scrubber->scrub($content),
                ];
            }
        }

        $totalFiltered = count($allMatches);

        // Reverse for most recent first, then paginate
        $allMatches = array_reverse($allMatches);
        $entries = array_slice($allMatches, $offset, $lines);

        return [
            'entries' => $entries,
            'total_lines' => $totalFiltered,
            'has_more' => ($offset + $lines) < $totalFiltered,
        ];
    }

    /**
     * Check if system grep is available.
     */
    protected function isGrepAvailable(): bool
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(['which', 'grep'], $descriptors, $pipes);

        if (! is_resource($process)) {
            return false;
        }

        fclose($pipes[0]);
        $output = trim(stream_get_contents($pipes[1]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return $exitCode === 0 && ! empty($output);
    }

    /**
     * PHP-based filtering using SplFileObject (fallback when grep unavailable).
     *
     * @return array{entries: array<int, array{line: int, content: string}>, total_lines: int, has_more: bool}
     */
    protected function readWithPhpFilter(string $path, int $lines, int $offset, ?string $search, ?string $level): array
    {
        try {
            $file = new SplFileObject($path, 'r');
        } catch (\Exception $e) {
            throw new InvalidArgumentException(
                sprintf('Unable to read log file: %s', $e->getMessage())
            );
        }

        $matches = [];

        while (! $file->eof()) {
            $content = rtrim((string) $file->current(), "\r\n");
            $lineNum = $file->key();

            if ($content !== '' && $this->lineMatchesFilters($content, $search, $level)) {
                $matches[] = [
                    'line' => $lineNum + 1,
                    'content' => $this->scrubber->scrub($content),
                ];
            }

            $file->next();
        }

        $totalFiltered = count($matches);

        // Reverse for most recent first, then paginate
        $matches = array_reverse($matches);
        $entries = array_slice($matches, $offset, $lines);

        return [
            'entries' => $entries,
            'total_lines' => $totalFiltered,
            'has_more' => ($offset + $lines) < $totalFiltered,
        ];
    }

    /**
     * Check if a line matches the provided filters.
     */
    protected function lineMatchesFilters(string $line, ?string $search, ?string $level): bool
    {
        if ($search !== null && stripos($line, $search) === false) {
            return false;
        }

        if ($level !== null && ! $this->matchesLevel($line, $level)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a file has an allowed extension.
     */
    protected function isAllowedFile(string $filename): bool
    {
        if ($filename === '.' || $filename === '..') {
            return false;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return in_array(strtolower($extension), $this->allowedExtensions, true);
    }

    /**
     * Validate the filename to prevent path traversal.
     */
    protected function validateFilename(string $filename): void
    {
        // Prevent path traversal
        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            throw new InvalidArgumentException('Invalid filename.');
        }

        if (! $this->isAllowedFile($filename)) {
            throw new InvalidArgumentException('File type not allowed.');
        }
    }

    /**
     * Check if a log line matches the specified level.
     */
    protected function matchesLevel(string $line, string $level): bool
    {
        $pattern = '/\b'.preg_quote(strtoupper($level), '/').'\b/';

        return (bool) preg_match($pattern, $line);
    }
}
