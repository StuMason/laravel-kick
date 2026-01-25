<?php

namespace StuMason\Kick\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use SplFileInfo;

class LogReader
{
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

        $allLines = $this->readLines($path);
        $totalLines = count($allLines);

        // Apply filters
        $filtered = collect($allLines)
            ->when($search !== null, fn ($c) => $c->filter(
                fn ($line) => stripos($line, $search) !== false
            ))
            ->when($level !== null, fn ($c) => $c->filter(
                fn ($line) => $this->matchesLevel($line, $level)
            ));

        $totalFiltered = $filtered->count();

        // Apply pagination (from end of file, most recent first)
        $entries = $filtered
            ->reverse()
            ->skip($offset)
            ->take($lines)
            ->map(fn ($content, $lineNum) => [
                'line' => $lineNum + 1,
                'content' => $this->scrubber->scrub($content),
            ])
            ->values()
            ->all();

        return [
            'entries' => $entries,
            'total_lines' => $totalFiltered,
            'has_more' => ($offset + $lines) < $totalFiltered,
        ];
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
     * Read all lines from a file.
     *
     * @return array<int, string>
     *
     * @throws InvalidArgumentException If the file cannot be read
     */
    protected function readLines(string $path): array
    {
        $content = @file_get_contents($path);

        if ($content === false) {
            $error = error_get_last();
            throw new InvalidArgumentException(
                sprintf('Unable to read log file: %s', $error['message'] ?? 'Unknown error')
            );
        }

        return explode("\n", $content);
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
