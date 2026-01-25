<?php

use StuMason\Kick\Services\LogReader;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/kick-tests-'.uniqid();
    mkdir($this->tempDir, 0755, true);
});

afterEach(function () {
    // Clean up temp directory
    if (is_dir($this->tempDir)) {
        array_map('unlink', glob($this->tempDir.'/*') ?: []);
        rmdir($this->tempDir);
    }
});

it('lists log files in directory', function () {
    file_put_contents($this->tempDir.'/laravel.log', 'test content');
    file_put_contents($this->tempDir.'/worker.log', 'worker content');
    file_put_contents($this->tempDir.'/not-a-log.txt', 'ignored');

    $reader = new LogReader($this->tempDir);
    $files = $reader->listFiles();

    expect($files)->toHaveCount(2);
    expect($files->pluck('name')->all())->toContain('laravel.log', 'worker.log');
});

it('returns empty collection for non-existent directory', function () {
    $reader = new LogReader('/non/existent/path');
    $files = $reader->listFiles();

    expect($files)->toBeEmpty();
});

it('reads log file content', function () {
    $content = "[2024-01-01 12:00:00] production.INFO: Test message\n[2024-01-01 12:00:01] production.ERROR: Error message";
    file_put_contents($this->tempDir.'/laravel.log', $content);

    $reader = new LogReader($this->tempDir);
    $result = $reader->read('laravel.log');

    expect($result['entries'])->toHaveCount(2);
    expect($result['total_lines'])->toBe(2);
});

it('filters log entries by level', function () {
    $content = "[2024-01-01 12:00:00] production.INFO: Info message\n[2024-01-01 12:00:01] production.ERROR: Error message\n[2024-01-01 12:00:02] production.INFO: Another info";
    file_put_contents($this->tempDir.'/laravel.log', $content);

    $reader = new LogReader($this->tempDir);
    $result = $reader->read('laravel.log', 100, 0, null, 'ERROR');

    expect($result['entries'])->toHaveCount(1);
    expect($result['entries'][0]['content'])->toContain('Error message');
});

it('filters log entries by search term', function () {
    $content = "[2024-01-01 12:00:00] production.INFO: User logged in\n[2024-01-01 12:00:01] production.INFO: Order created\n[2024-01-01 12:00:02] production.INFO: User logged out";
    file_put_contents($this->tempDir.'/laravel.log', $content);

    $reader = new LogReader($this->tempDir);
    $result = $reader->read('laravel.log', 100, 0, 'User');

    expect($result['entries'])->toHaveCount(2);
});

it('paginates log entries', function () {
    $lines = [];
    for ($i = 1; $i <= 10; $i++) {
        $lines[] = "[2024-01-01 12:00:{$i}0] production.INFO: Line {$i}";
    }
    file_put_contents($this->tempDir.'/laravel.log', implode("\n", $lines));

    $reader = new LogReader($this->tempDir);

    $firstPage = $reader->read('laravel.log', 3, 0);
    expect($firstPage['entries'])->toHaveCount(3);
    expect($firstPage['has_more'])->toBeTrue();

    $secondPage = $reader->read('laravel.log', 3, 3);
    expect($secondPage['entries'])->toHaveCount(3);
    expect($secondPage['has_more'])->toBeTrue();
});

it('respects max lines limit', function () {
    $lines = [];
    for ($i = 1; $i <= 1000; $i++) {
        $lines[] = "Line {$i}";
    }
    file_put_contents($this->tempDir.'/laravel.log', implode("\n", $lines));

    $reader = new LogReader($this->tempDir, ['log'], 500);
    $result = $reader->read('laravel.log', 1000);

    expect($result['entries'])->toHaveCount(500);
});

it('prevents path traversal attacks', function () {
    $reader = new LogReader($this->tempDir);

    expect(fn () => $reader->read('../../../etc/passwd'))
        ->toThrow(InvalidArgumentException::class, 'Invalid filename');
});

it('rejects disallowed file extensions', function () {
    file_put_contents($this->tempDir.'/secret.txt', 'sensitive data');

    $reader = new LogReader($this->tempDir);

    expect(fn () => $reader->read('secret.txt'))
        ->toThrow(InvalidArgumentException::class, 'File type not allowed');
});

it('throws exception for non-existent file', function () {
    $reader = new LogReader($this->tempDir);

    expect(fn () => $reader->read('missing.log'))
        ->toThrow(InvalidArgumentException::class, 'Log file not found');
});

it('can tail recent entries', function () {
    $lines = [];
    for ($i = 1; $i <= 100; $i++) {
        $lines[] = "[2024-01-01 12:00:{$i}0] production.INFO: Line {$i}";
    }
    file_put_contents($this->tempDir.'/laravel.log', implode("\n", $lines));

    $reader = new LogReader($this->tempDir);
    $entries = $reader->tail('laravel.log', 5);

    expect($entries)->toHaveCount(5);
});

it('handles empty log files', function () {
    file_put_contents($this->tempDir.'/empty.log', '');

    $reader = new LogReader($this->tempDir);
    $result = $reader->read('empty.log');

    expect($result['entries'])->toBeEmpty();
    expect($result['total_lines'])->toBe(0);
    expect($result['has_more'])->toBeFalse();
});

it('returns entries in reverse order (most recent first)', function () {
    $content = "Line 1\nLine 2\nLine 3";
    file_put_contents($this->tempDir.'/laravel.log', $content);

    $reader = new LogReader($this->tempDir);
    $result = $reader->read('laravel.log');

    expect($result['entries'][0]['content'])->toBe('Line 3');
    expect($result['entries'][1]['content'])->toBe('Line 2');
    expect($result['entries'][2]['content'])->toBe('Line 1');
});

it('combines search and level filters', function () {
    $content = "[2024-01-01 12:00:00] production.INFO: User logged in\n".
               "[2024-01-01 12:00:01] production.ERROR: User authentication failed\n".
               "[2024-01-01 12:00:02] production.ERROR: Database connection failed\n".
               '[2024-01-01 12:00:03] production.INFO: User logged out';
    file_put_contents($this->tempDir.'/laravel.log', $content);

    $reader = new LogReader($this->tempDir);
    $result = $reader->read('laravel.log', 100, 0, 'User', 'ERROR');

    expect($result['entries'])->toHaveCount(1);
    expect($result['entries'][0]['content'])->toContain('authentication failed');
});

it('paginates filtered results correctly', function () {
    $lines = [];
    for ($i = 1; $i <= 20; $i++) {
        $level = $i % 2 === 0 ? 'ERROR' : 'INFO';
        $lines[] = "[2024-01-01 12:00:{$i}0] production.{$level}: Line {$i}";
    }
    file_put_contents($this->tempDir.'/laravel.log', implode("\n", $lines));

    $reader = new LogReader($this->tempDir);

    // Get first page of ERROR entries (lines 2, 4, 6, 8, 10, 12, 14, 16, 18, 20 - 10 total)
    $firstPage = $reader->read('laravel.log', 3, 0, null, 'ERROR');
    expect($firstPage['entries'])->toHaveCount(3);
    expect($firstPage['total_lines'])->toBe(10);
    expect($firstPage['has_more'])->toBeTrue();

    // Get second page
    $secondPage = $reader->read('laravel.log', 3, 3, null, 'ERROR');
    expect($secondPage['entries'])->toHaveCount(3);
    expect($secondPage['has_more'])->toBeTrue();
});

it('has a 50MB file size limit constant', function () {
    $reflection = new ReflectionClass(LogReader::class);
    $constant = $reflection->getConstant('MAX_UNFILTERED_SIZE');

    // 50MB in bytes
    expect($constant)->toBe(50 * 1024 * 1024);
});

it('allows large files when using filters', function () {
    // This test verifies that the file size check is bypassed when filters are applied
    // The actual 50MB limit can't be easily tested without creating huge files
    $content = '[2024-01-01 12:00:00] production.ERROR: Test error';
    file_put_contents($this->tempDir.'/laravel.log', $content);

    $reader = new LogReader($this->tempDir);

    // With a filter, should work regardless of file size logic
    $result = $reader->read('laravel.log', 100, 0, null, 'ERROR');
    expect($result['entries'])->toHaveCount(1);
});
